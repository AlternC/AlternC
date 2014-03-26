<?php


class system_bind {
  var $ZONE_TEMPLATE ="/etc/alternc/templates/bind/templates/zone.template";
  var $NAMED_TEMPLATE ="/etc/alternc/templates/bind/templates/named.template";
  var $NAMED_CONF ="/var/lib/alternc/bind/automatic.conf";
  var $RNDC ="/usr/sbin/rndc";

  var $dkim_trusted_host_file = "/etc/opendkim/TrustedHosts";
  var $dkim_keytable_file = "/etc/opendkim/KeyTable";
  var $dkim_signingtable_file = "/etc/opendkim/SigningTable";

  var $cache_conf_db = array();
  var $cache_get_persistent = array();
  var $cache_zone_file = array();
  var $cache_domain_summary = array();
  var $zone_file_directory = '/var/lib/alternc/bind/zones/';

  /**
   * 
   */
  function system_bind() {
    // Constructeur
  }

  /**
   * Return the part of the conf we got from the database
   * 
   * @global m_mysql $db
   * @param string $domain
   * @return array $this->cache_conf_db
   */
  function conf_from_db($domain=false) {
    global $db;
    // Use cache, fill cache if empty
    if (empty($this->cache_conf_db)) {
      $db->query("
        select 
          sd.domaine, 
          replace(replace(dt.entry,'%TARGET%',sd.valeur), '%SUB%', if(length(sd.sub)>0,sd.sub,'@')) as entry 
        from 
          sub_domaines sd,
          domaines_type dt 
        where 
          sd.type=dt.name 
          and sd.enable in ('ENABLE', 'ENABLED') 
        order by entry ;");
      $t=array();
      while ($db->next_record()) {
        $t[$db->f('domaine')][] = $db->f('entry');
      }
      $this->cache_conf_db = $t;
    }
    if ($domain) {
      if (isset($this->cache_conf_db[$domain])) {
        return $this->cache_conf_db[$domain];
      } else {
        return array();
      }
    } // if domain
    return $this->cache_conf_db;
  }

  /**
   * Return full path of the zone configuration file
   * 
   * @param string $domain
   * @return string
   */
  function get_zone_file_uri($domain) {
    return $this->zone_file_directory.$domain;
  }

  /**
   * 
   * @param string $domain
   * @return string zone file path
   */
  function get_zone_file($domain) {
    // Use cache, fill cache if empty
    if (!isset($this->cache_zone_file[$domain]) ) {
      if (file_exists($this->get_zone_file_uri($domain))) {
        $this->cache_zone_file[$domain] = @file_get_contents($this->get_zone_file_uri($domain));
      } else {
        $this->cache_zone_file[$domain] = false;
      }
    }
    return $this->cache_zone_file[$domain] ;
  }

  /**
   * 
   * @param string $domain
   * @return string 
   */
  function get_serial($domain) {
    // Return the next serial the domain must have.
    // Choose between a generated and an incremented.
    
    // Calculated :
    $calc = date('Ymd').'00'."\n";

    // Old one :
    $old=$calc; // default value
    $file = $this->get_zone_file($domain);
    preg_match_all("/\s*(\d{10})\s+\;\sserial\s?/", $file, $output_array);
    if (isset($output_array[1][0]) && !empty($output_array[1][0])) {
      $old = $output_array[1][0];
    }

    // Return max between newly calculated, and old one incremented
    return max(array($calc,$old)) + 1 ;
  }

  /**
   * Return lines that are after ;;;END ALTERNC AUTOGENERATE CONFIGURATION
   * 
   * @param string $domain
   * @return string
   */
  function get_persistent($domain) {
    if ( ! isset($this->cache_get_persistent[$domain] )) {
      preg_match_all('/\;\s*END\sALTERNC\sAUTOGENERATE\sCONFIGURATION(.*)/s', $this->get_zone_file($domain), $output_array);
      if (isset($output_array[1][0]) && !empty($output_array[1][0])) {
        $this->cache_get_persistent[$domain] = $output_array[1][0];
      } else {
        $this->cache_get_persistent[$domain] = false;
      }
    } // isset
    return $this->cache_get_persistent[$domain];
  }
  
  /**
   * 
   * @param string $domain
   * @return string 
   */
  function get_zone_header($domain) {
    return file_get_contents($this->ZONE_TEMPLATE);
  }
  
  /**
   * 
   * @global m_dom $dom
   * @param string $domain
   * @return string
   */
  function get_domain_summary($domain=false) {
    global $dom;

    // Use cache if is filled, if not, fill it
    if (empty($this->cache_domain_summary)) {
      $this->cache_domain_summary = $dom->get_domain_all_summary();
    }

    if ($domain) return $this->cache_domain_summary[$domain];
    else return $this->cache_domain_summary;
  }

  /**
   * 
   * @param string $domain
   * @return boolean
   */
  function dkim_delete($domain) {
    $target_dir = "/etc/opendkim/keys/$domain";
    if (file_exists($target_dir)) {
      @unlink("$target_dir/alternc_private");
      @unlink("$target_dir/alternc.txt");
      @rmdir($target_dir);
    }
    return true;
  }

  /**
   * Generate the domain DKIM key
   * 
   * @param string $domain
   * @return boolean
   */
  function dkim_generate_key($domain) {
    // Stop here if we do not manage the mail
    if ( !  $this->get_domain_summary($domain)['gesmx'] ) return;

    $target_dir = "/etc/opendkim/keys/$domain";

    if (file_exists($target_dir.'/alternc.txt')) return; // Do not generate if exist

    if (! is_dir($target_dir)) mkdir($target_dir); // create dir

    // Generate the key
    $old_dir=getcwd();
    chdir($target_dir);
    exec('opendkim-genkey -r -d "'.escapeshellarg($domain).'" -s "alternc" ');
    chdir($old_dir);

    // opendkim must be owner of the key
    chown("$target_dir/alternc.private", 'opendkim');
    chgrp("$target_dir/alternc.private", 'opendkim');

    return true; // FIXME handle error
  }

  /**
   * Refresh DKIM configuration: be sure to list the domain having a private key (and only them)
   */
  function dkim_refresh_list() { 
    // so ugly... but there is only 1 pass, not 3. Still ugly.
    $trusted_host_new = "# WARNING: this file is auto generated by AlternC.\n# Add your changes after the last line\n";
    $keytable_new     = "# WARNING: this file is auto generated by AlternC.\n# Add your changes after the last line\n";
    $signingtable_new = "# WARNING: this file is auto generated by AlternC.\n# Add your changes after the last line\n";

    # Generate automatic entry
    foreach ($this->get_domain_summary() as $domain => $ds ) {
      // Skip if delete in progress, or if we do not manage dns or mail
      if ( ! $ds['gesdns'] || ! $ds['gesmx'] || strtoupper($ds['dns_action']) == 'DELETE' ) continue;

      // Skip if there is no key generated
      if (! file_exists("/etc/opendkim/keys/$domain/alternc.txt")) continue; 

      // Modif the files.
      $trusted_host_new.="$domain\n";
      $keytable_new    .="alternc._domainkey.$domain $domain:alternc:/etc/opendkim/keys/$domain/alternc.private\n";
      $signingtable_new.="$domain alternc._domainkey.$domain\n";
    }
    $trusted_host_new.="# END AUTOMATIC FILE. ADD YOUR CHANGES AFTER THIS LINE";
    $keytable_new    .="# END AUTOMATIC FILE. ADD YOUR CHANGES AFTER THIS LINE";
    $signingtable_new.="# END AUTOMATIC FILE. ADD YOUR CHANGES AFTER THIS LINE";

    # Get old files
    $trusted_host_old=@file_get_contents($this->dkim_trusted_host_file);
    $keytable_old    =@file_get_contents($this->dkim_keytable_file);
    $signingtable_old=@file_get_contents($this->dkim_signingtable_file);
    
    # Keep manuel entry
    preg_match_all('/\#\s*END\ AUTOMATIC\ FILE\.\ ADD\ YOUR\ CHANGES\ AFTER\ THIS\ LINE(.*)/s', $trusted_host_old, $output_array);
    if (isset($output_array[1][0]) && !empty($output_array[1][0])) {
      $trusted_host_new.=$output_array[1][0];
    } 
    preg_match_all('/\#\s*END\ AUTOMATIC\ FILE\.\ ADD\ YOUR\ CHANGES\ AFTER\ THIS\ LINE(.*)/s', $keytable_old, $output_array);
    if (isset($output_array[1][0]) && !empty($output_array[1][0])) {
      $keytable_new.=$output_array[1][0];
    } 
    preg_match_all('/\#\s*END\ AUTOMATIC\ FILE\.\ ADD\ YOUR\ CHANGES\ AFTER\ THIS\ LINE(.*)/s', $signingtable_old, $output_array);
    if (isset($output_array[1][0]) && !empty($output_array[1][0])) {
      $signingtable_new.=$output_array[1][0];
    } 
    
    // Save if there are some diff
    if ( $trusted_host_new != $trusted_host_old ) {
      file_put_contents($this->dkim_trusted_host_file, $trusted_host_new);
    }
    if ( $keytable_new != $keytable_old ) {
      file_put_contents($this->dkim_keytable_file, $keytable_new);
    }
    if ( $signingtable_new != $signingtable_old ) {
      file_put_contents($this->dkim_signingtable_file, $signingtable_new);
    }

  }
  /**
   * 
   * @param string $domain
   * @return string
   */
  function dkim_entry($domain) {
    $keyfile="/etc/opendkim/keys/$domain/alternc.txt";
    if (! file_exists($keyfile) &&  $this->get_domain_summary($domain)['gesmx'] ) {
      $this->dkim_generate_key($domain);
    }
    return @file_get_contents($keyfile);
  }

  /**
   * Conditionnal generation autoconfig entry for outlook / thunderbird
   * If entry with the same name allready exist, skip it.
   * 
   * @param string $domain
   * @return string
   */
  function mail_autoconfig_entry($domain) {
    $zone= implode("\n",$this->conf_from_db($domain))."\n".$this->get_persistent($domain);

    $entry='';
    if ( $this->get_domain_summary($domain)['gesmx'] ) {
      // If we manage the mail

      // Check if there is no the same entry (defined or manual)
      // can be toto IN A or toto.fqdn.tld. IN A
      if (! preg_match("/autoconfig(\s|\.".str_replace('.','\.',$domain)."\.)/", $zone )) {
        $entry.="autoconfig IN CNAME %%fqdn%%.\n";
      }
      if (! preg_match("/autodiscover(\s|\.".str_replace('.','\.',$domain)."\.)/", $zone )) {
        $entry.="autodiscover IN CNAME %%fqdn%%.\n";
      }
    } // if gesmx
    return $entry;
  }
  
  
  /**
   * 
   * Return a fully generated zone
   * 
   * @global string $L_FQDN
   * @global string $L_NS1_HOSTNAME
   * @global string $L_NS2_HOSTNAME
   * @global string $L_DEFAULT_MX
   * @global string $L_DEFAULT_SECONDARY_MX
   * @global string $L_PUBLIC_IP
   * @param string $domain
   * @return string
   */
  function get_zone($domain) {
    global $L_FQDN, $L_NS1_HOSTNAME, $L_NS2_HOSTNAME, $L_DEFAULT_MX, $L_DEFAULT_SECONDARY_MX, $L_PUBLIC_IP;

    $zone =$this->get_zone_header($domain);
    $zone.=implode("\n",$this->conf_from_db($domain));
    $zone.="\n;;;HOOKED ENTRY\n";

    $zone.= $this->dkim_entry($domain);
    $zone.= $this->mail_autoconfig_entry($domain);

    $zone.="\n;;;END ALTERNC AUTOGENERATE CONFIGURATION";
    $zone.=$this->get_persistent($domain);

    // FIXME check those vars
    $zone = strtr($zone, array(
            "%%fqdn%%"=>"$L_FQDN",
            "%%ns1%%"=>"$L_NS1_HOSTNAME",
            "%%ns2%%"=>"$L_NS2_HOSTNAME",
            "%%DEFAULT_MX%%"=>"$L_DEFAULT_MX",
            "%%DEFAULT_SECONDARY_MX%%"=>"$L_DEFAULT_SECONDARY_MX",
            "@@fqdn@@"=>"$L_FQDN",
            "@@ns1@@"=>"$L_NS1_HOSTNAME",
            "@@ns2@@"=>"$L_NS2_HOSTNAME",
            "@@DEFAULT_MX@@"=>"$L_DEFAULT_MX",
            "@@DEFAULT_SECONDARY_MX@@"=>"$L_DEFAULT_SECONDARY_MX",
            "@@DOMAINE@@"=>"$domain",
            "@@SERIAL@@"=>$this->get_serial($domain),
            "@@PUBLIC_IP@@"=>"$L_PUBLIC_IP",
            "@@ZONETTL@@"=> $this->get_domain_summary($domain)['zonettl'],
          ));

    return $zone;
  }

  /**
   * 
   * @param string $domain
   */
  function reload_zone($domain) {
    exec($this->RNDC." reload ".escapeshellarg($domain), $output, $return_value);
    if ($return_value != 0 ) {
      echo "ERROR: Reload zone failed for zone $domain\n";
    }
  }

  /**
   * return true if zone is locked
   * 
   * @param string $domain
   * @return boolean
   */
  function is_locked($domain) {
    preg_match_all("/(\;\s*LOCKED:YES)/i", $this->get_zone_file($domain), $output_array);
    if (isset($output_array[1][0]) && !empty($output_array[1][0])) {
      return true;
    }
    return false;
  }  

  /**
   * 
   * @global m_mysql $db
   * @global m_dom $dom
   * @param string $domain
   * @return boolean
   */
  function save_zone($domain) {
    global $db, $dom;

    // Do not save if the zone is LOCKED
    if ( $this->is_locked($domain)) {
      $dom->set_dns_result($domain, "The zone file of this domain is locked. Contact your administrator."); // If edit, change dummy_for_translation
      $dom->set_dns_action($domain, 'OK');
      return false;
    }
 
    // Save file, and apply chmod/chown
    $file=$this->get_zone_file_uri($domain);
    file_put_contents($file, $this->get_zone($domain));
    chown($file, 'bind');
    chmod($file, 0640);

    $dom->set_dns_action($domain, 'OK');
    return true; // fixme add tests
  }

  /**
   * Delete the zone configuration file
   * 
   * @param string $domain
   * @return boolean
   */
  function delete_zone($domain) {
    $file=$this->get_zone_file_uri($domain);
    if (file_exists($file)) {
      unlink($file);
    }
    $this->dkim_delete($domain);
    return true;
  }

  /**
   * 
   * @global m_hooks $hooks
   * @return boolean
   */
  function reload_named() {
    global $hooks;
    // Generate the new conf file
    $new_named_conf="// DO NOT EDIT\n// This file is generated by Alternc.\n// Every changes you'll make will be overwrited.\n";
    $tpl=file_get_contents($this->NAMED_TEMPLATE);
    foreach ($this->get_domain_summary() as $domain => $ds ) {
      if ( ! $ds['gesdns'] || strtoupper($ds['dns_action']) == 'DELETE' ) continue;
      $new_named_conf.=strtr($tpl, array("@@DOMAINE@@"=>$domain, "@@ZONE_FILE@@"=>$this->get_zone_file_uri($domain)));
    }

    // Get the actual conf file
    $old_named_conf = @file_get_contents($this->NAMED_CONF);

    // Apply new configuration only if there are some differences
    if ($old_named_conf != $new_named_conf ) {
      file_put_contents($this->NAMED_CONF,$new_named_conf);
      chown($this->NAMED_CONF, 'bind');
      chmod($this->NAMED_CONF, 0640);
      exec($this->RNDC." reconfig");
      $hooks->invoke_scripts("/usr/lib/alternc/reload.d", array('dns_reconfig')  );
    }

    return true;
  }

  /**
   * Regenerate bind configuration and load it
   * 
   * @global m_hooks $hooks
   * @param boolean $all
   * @return boolean
   */
  function regenerate_conf($all=false) {
    global $hooks;

    foreach ($this->get_domain_summary() as $domain => $ds ) {
      if ( ! $ds['gesdns'] && strtoupper($ds['dns_action']) == 'OK' ) continue; // Skip if we do not manage DNS and is up-to-date for this domain

      if ( (strtoupper($ds['dns_action']) == 'DELETE' ) || 
           (strtoupper($ds['dns_action']) == 'UPDATE' && $ds['gesdns']==false ) // in case we update the zone to disable DNS management
         ) { 
        $this->delete_zone($domain);
        continue;
      }

      if ( ( $all || strtoupper($ds['dns_action']) == 'UPDATE' ) && $ds['gesdns'] ) {
        $this->save_zone($domain);
        $this->reload_zone($domain);
        $hooks->invoke_scripts("/usr/lib/alternc/reload.d", array('dns_reload_zone', $domain)  );
      }
    } // end foreach domain

    $this->dkim_refresh_list();
    $this->reload_named();
    return true;
  }

  /**
   * 
   */
  private function dummy_for_translation() {
    _("The zone file of this domain is locked. Contact your administrator.");
  }

} // class


?>
