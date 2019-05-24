<?php

/*
  ----------------------------------------------------------------------
  LICENSE

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License (GPL)
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  To read the license please visit http://www.gnu.org/copyleft/gpl.html
  ----------------------------------------------------------------------
*/

/**
 * Manages BIND 9+ zone management templates in AlternC 3.5+
 * 
 * @copyright AlternC-Team 2000-2018 https://alternc.com/
 */
class m_bind {

    var $shouldreload;
    var $shouldreconfig;
    
    var $ZONE_TEMPLATE ="/etc/alternc/templates/bind/templates/zone.template";
    var $NAMED_TEMPLATE ="/etc/alternc/templates/bind/templates/named.template";
    var $NAMED_CONF ="/var/lib/alternc/bind/automatic.conf";
    var $RNDC ="/usr/sbin/rndc";

    var $zone_file_directory = '/var/lib/alternc/bind/zones';

    // ------------------------------------------------------------
    /** Hook launched before any action by updatedomains 
     * initialize the reload/reconfig flags used by POST
     * @NOTE launched as ROOT 
     */
    function hook_updatedomains_dns_pre() {
        $this->shouldreload=false;
        $this->shouldreconfig=false;
    }


    // ------------------------------------------------------------
    /**
     * Hook launched for each ZONE for which we want a zone update (or create)
     * update the zone, create it if necessary, 
     * and ask for reload or reconfig of bind9 depending on what happened
     * @NOTE launched as ROOT 
     */
    function hook_updatedomains_dns_add($dominfo) {
        global $L_FQDN,$L_NS1_HOSTNAME,$L_NS2_HOSTNAME,$L_DEFAULT_MX,$L_DEFAULT_SECONDARY_MX,$L_PUBLIC_IP,$L_PUBLIC_IPV6;

        $domain = $dominfo["domaine"];
        $ttl = $dominfo["zonettl"];

        // does it already exist?
        if (file_exists($this->zone_file_directory."/".$domain)) {
            list($islocked,$serial,$more)=$this->read_zone($domain);
            $serial++; // only increment serial for new zones
        } else {
            $more="";
            $serial=date("Ymd")."00";
            $islocked=false;
        }
        if ($islocked) return;

        // Prepare a new zonefile from a template
        $zone = file_get_contents($this->ZONE_TEMPLATE);

        // add the SUBDOMAIN entries
        $zone .= $this->conf_from_db($domain);

        // substitute ALTERNC & domain variables
        $zone = strtr($zone, array(
            "%%fqdn%%" => "$L_FQDN",
            "%%ns1%%" => "$L_NS1_HOSTNAME",
            "%%ns2%%" => "$L_NS2_HOSTNAME",
            "%%DEFAULT_MX%%" => "$L_DEFAULT_MX",
            "%%DEFAULT_SECONDARY_MX%%" => "$L_DEFAULT_SECONDARY_MX",
            "@@fqdn@@" => "$L_FQDN",
            "@@ns1@@" => "$L_NS1_HOSTNAME",
            "@@ns2@@" => "$L_NS2_HOSTNAME",
            "@@DEFAULT_MX@@" => "$L_DEFAULT_MX",
            "@@DEFAULT_SECONDARY_MX@@" => "$L_DEFAULT_SECONDARY_MX",
            "@@DOMAINE@@" => $domain,
            "@@SERIAL@@" => $serial,
            "@@PUBLIC_IP@@" => "$L_PUBLIC_IP",
            "@@PUBLIC_IPV6@@" => "$L_PUBLIC_IPV6",
            "@@ZONETTL@@" => $ttl,
        ));

        // add the "END ALTERNC CONF line";
        $zone .= ";;; END ALTERNC AUTOGENERATE CONFIGURATION\n";

        // add the manually entered info:
        $zone .= $more;
        file_put_contents($this->zone_file_directory."/".$domain,$zone);

        // add the line into bind9 conf:
        if (add_line_to_file(
            $this->NAMED_CONF,
            trim(strtr(
                file_get_contents($this->NAMED_TEMPLATE),
                array(
                    "@@DOMAIN@@" => $domain,
                    "@@ZONE_FILE@@" => $this->zone_file_directory."/".$domain
                )
            )))
        ) {
            $this->shouldreconfig=true;
        } else {
            $this->shouldreload=true;
        }
    }


    // ------------------------------------------------------------
    /** 
     * Hook launched for each ZONE for which we want a zone DELETE
     * remove the zone and its file,
     * and if any action happened, ask for bind RECONFIG at posttime
     * @NOTE launched as ROOT 
     */
    function hook_updatedomains_dns_del($dominfo) {
        $domain = $dominfo["domaine"];
        if (del_line_from_file(
            $this->NAMED_CONF,
            trim(strtr(
                file_get_contents($this->NAMED_TEMPLATE),
                array(
                    "@@DOMAIN@@" => $domain,
                    "@@ZONE_FILE@@" => $this->zone_file_directory."/".$domain
                )
            )))
        ) {
            $this->shouldreconfig=true;
        } else {
            return;
        }
        @unlink($this->zone_file_directory."/".$domain);
    }

    
    // ------------------------------------------------------------
    /** 
     * Hook function launched at the very end of updatedomains 
     * here, we just reload OR reconfig (or both) bind9 depending 
     * on what happened before.
     * @NOTE launched as ROOT 
     */ 
    function hook_updatedomains_dns_post() {
        global $msg;
        if ($this->shouldreload) {
            $ret=0;
            exec($this->RNDC." reload 2>&1",$out,$ret);
            if ($ret!=0) {
                $msg->raise("ERROR","bind","Error while reloading bind, error code is $ret\n".implode("\n",$out));
            } else {
                $msg->raise("INFO","bind","Bind reloaded");
            }
        }
        if ($this->shouldreconfig) {
            $ret=0;
            exec($this->RNDC." reconfig 2>&1",$out,$ret);
            if ($ret!=0) {
                $msg->raise("ERROR","bind","Error while reconfiguring bind, error code is $ret\n".implode("\n",$out));
            } else {
                $msg->raise("INFO","bind","Bind reconfigured");
            }
        }
    }

    
    // ------------------------------------------------------------
    /** 
     * read a zone file for $domain, 
     * @param $domain string the domain name 
     * @return array with 3 informations: 
     * is the domain locked? (boolean), what's the current serial (integer), the data after alternc conf (string of lines)
     */
    function read_zone($domain) {
        $f=fopen($this->zone_file_directory."/".$domain,"rb");
        $islocked=false;
        $more="";
        $serial=date("Ymd")."00";
        while ($s=fgets($f,4096)) {
            if (preg_match("#\;\s*LOCKED:YES#i",$s)) {
                $islocked=true;
            }
            if (preg_match("/\s*(\d{10})\s+\;\sserial\s?/", $s,$mat)) {
                $serial=$mat[1];
            }
            if (preg_match('/\;\s*END\sALTERNC\sAUTOGENERATE\sCONFIGURATION(.*)/s', $s)) {
                break;
            }
        }
        while ($s=fgets($f,4096)) {
            $more.=$s;
        }
        return array($islocked,$serial,$more);
    }


    // ------------------------------------------------------------
    /**
     * Return the part of the conf we got from the sub_domaines table
     * @global m_mysql $db
     * @param string $domain
     * @return string a zonefile excerpt
     */
    function conf_from_db($domain) {
        global $db;
        $db->query("
        SELECT 
          REPLACE(REPLACE(dt.entry,'%TARGET%',sd.valeur), '%SUB%', if(length(sd.sub)>0,sd.sub,'@')) AS ENTRY,
          dt.target AS TARGET,
          dt.entry  AS ORIGINAL_ENTRY,
          sd.valeur AS VALEUR,
          if(length(sd.sub)>0,sd.sub,'@') AS SUB
        FROM 
          sub_domaines sd,
          domaines_type dt 
        WHERE 
          sd.type=dt.name
          AND sd.enable IN ('ENABLE', 'ENABLED')
          AND sd.web_action NOT IN ('DELETE')
        ORDER BY ENTRY ;");
        $t="";
        while ($db->next_record()) {
            // TXT entries may be longer than 255 characters, but need
            // special treatment. @see https://kb.isc.org/docs/aa-00356
            if (strlen($db->f('VALEUR')) >= 256 && $db->f('TARGET') == 'TXT') {
                $chunks = str_split($db->f('VALEUR'), 255);
                if ($chunks !== FALSE) {
                    $new_entry = '';
                    foreach ($chunks as $chunk) {
                        $new_entry .= '"' . $chunk . '" ';
                    }
                    $new_entry = trim($new_entry, ' ');
                    $entry = strtr($db->f('ORIGINAL_ENTRY'), array(
                        '%SUB%' => $db->f('SUB'),
                        // Don't want extra double quotes in this case
                        '"%TARGET%"' => $new_entry,
                    ));
                }
                else {
                    $entry = $db->f('ENTRY');
                }
            }
            else {
                $entry = $db->f('ENTRY');
            }
            $t.= $entry . "\n";
        }
        return $t;
    }

    
} // m_bind

