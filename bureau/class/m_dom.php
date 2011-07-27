<?php
/*
 $Id: m_dom.php,v 1.27 2006/02/17 18:34:30 olivier Exp $
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
 Original Author of file: Benjamin Sonntag
 Purpose of file: PHP Class that manage domain names installed on the server
 ----------------------------------------------------------------------
*/

define('SLAVE_FLAG', "/var/run/alternc/refresh_slave");

/**
* Classe de gestion des domaines de l'hébergé.
* 
* Cette classe permet de gérer les domaines / sous-domaines, redirections
* dns et mx des domaines d'un membre hébergé.<br />
* Copyleft {@link http://alternc.net/ AlternC Team}
* 
* @copyright    AlternC-Team 2002-11-01 http://alternc.net/
* 
*/
class m_dom {

  /** $domains : Cache des domaines du membre
   * @access private
   */
  var $domains;

  /** $dns : Liste des dns trouvés par la fonction whois
   * @access private
   */
  var $dns;

  /** Flag : a-t-on trouvé un sous-domaine Webmail pour ce domaine ?
   * @access private
   */
  var $webmail;

  /**
   * Système de verrouillage du cron
   * Ce fichier permet de verrouiller le cron en attendant la validation
   * du domaine par update_domains.sh
   * @access private
   */
  var $fic_lock_cron="/var/run/alternc/cron.lock";

  /**
   * Le cron a-t-il été bloqué ?
   * Il faut appeler les fonctions privées lock et unlock entre les
   * appels aux domaines.
   * @access private
   */
  var $islocked=false;

  var $type_local = "VHOST";
  var $type_url = "URL";
  var $type_ip = "IP";
  var $type_webmail = "WEBMAIL";
  var $type_ipv6 = "IPV6";
  var $type_cname = "CNAME";
  var $type_txt = "TXT";
  var $type_defmx = "DEFMX";
  var $type_defmx2 = "DEFMX2";

  var $action_insert = "0";
  var $action_update= "1";
  var $action_delete = "2";

  /* ----------------------------------------------------------------- */
  /**
   * Constructeur
   */
  function m_dom() {
  }

  /* ----------------------------------------------------------------- */
  /**
   * Quota name
   */
  function alternc_quota_names() {
    return "dom";
  }
  /* ----------------------------------------------------------------- */
  /**
   * Retourne un tableau contenant les types de domaines
   *
   * @return array retourne un tableau indexé contenant la liste types de domaines 
   *  authorisé. Retourne FALSE si une erreur s'est produite.
   */
  function domains_type_lst() {
    global $db,$err,$cuid;
    $err->log("dom","domains_type_lst");
    $db->query("select * from domaines_type order by advanced;");
    $this->domains_type_lst=false;
    while ($db->next_record()) {
      $this->domains_type_lst[strtolower($db->Record["name"])] = $db->Record;
    }
    return $this->domains_type_lst;
  }

  function domains_type_enable_values() {
    global $db,$err,$cuid;
    $err->log("dom","domains_type_target_values");
    $db->query("desc domaines_type;");
    $r = array();
    while ($db->next_record()) {
      if ($db->f('Field') == 'enable') {
        $tab = explode(",", substr($db->f('Type'), 5, -1));
        foreach($tab as $t) { $r[]=substr($t,1,-1); }
      }
    }
    return $r;
  }

  function domains_type_target_values($type=null) {
    global $db,$err,$cuid;
    $err->log("dom","domains_type_target_values");
    if (is_null($type)) {
      $db->query("desc domaines_type;");
      $r = array();
      while ($db->next_record()) {
        if ($db->f('Field') == 'target') {
          $tab = explode(",", substr($db->f('Type'), 5, -1));
          foreach($tab as $t) { $r[]=substr($t,1,-1); }
        }
      }
      return $r;
    } else {
      $db->query("select target from domaines_type where name='$type';");
      if (! $db->next_record()) return false;
      return $db->f('target');
    }
  }

  function domains_type_regenerate($name) {
    global $db,$err,$cuid; 
    $name=mysql_real_escape_string($name);
    $db->query("update sub_domaines set web_action='UPDATE' where lower(type) = lower('$name') ;");
    $db->query("update domaines d, sub_domaines sd set d.dns_action = 'UPDATE' where lower(sd.type)=lower('$name');");
    return true;
  }

  function domains_type_get($name) {
    global $db,$err,$cuid; 
    $name=mysql_real_escape_string($name);
    $db->query("select * from domaines_type where name='$name' ;");
    $db->next_record();
    return $db->Record;
  }

  function domains_type_del($name) {
    global $db,$err,$cuid;
    $name=mysql_real_escape_string($name);
    $db->query("delete domaines_type where name='$name';");
    return true;
  }

  function domains_type_disable($id) {
    global $db,$err,$cuid;
    $id=intval($id);
    $db->query("update domaines_type set enable=false where id=$id;");
    return true;
  }

  function domains_type_enable($id) {
    global $db,$err,$cuid;
    $id=intval($id);
    $db->query("update domaines_type set enable=true where id=$id;");
    return true;
  }

  function domains_type_update($name, $description, $target, $entry, $compatibility, $enable, $only_dns, $need_dns,$advanced) {
    global $err,$cuid,$db;
    $id=intval($id);
    $name=mysql_real_escape_string($name);
    $description=mysql_real_escape_string($description);
    $target=mysql_real_escape_string($target);
    $entry=mysql_real_escape_string($entry);
    $compatibility=mysql_real_escape_string($compatibility);
    $enable=mysql_real_escape_string($enable);
    $only_dns=intval($only_dns);
    $need_dns=intval($need_dns);
    $advanced=intval($advanced);
    $db->query("UPDATE domaines_type SET description='$description', target='$target', entry='$entry', compatibility='$compatibility', enable='$enable', need_dns=$need_dns, only_dns=$only_dns, advanced='$advanced' where name='$name';");
    return true;
  }   

  function sub_domain_change_status($domain,$sub,$type,$value,$status) {
    global $db,$err,$cuid;
    $err->log("dom","sub_domain_change_status");
    $status=strtoupper($status);
    if (! in_array($status,array('ENABLE', 'DISABLE'))) return false;

    $db->query("update sub_domaines set enable='$status' where domaine='$domain' and sub='$sub' and lower(type)=lower('$type') and valeur='$value'");

    return true;
  } 

  /* ----------------------------------------------------------------- */
  /**
   * Retourne un tableau contenant les domaines d'un membre.
   *
   * @return array retourne un tableau indexé contenant la liste des
   *  domaines hébergés sur le compte courant. Retourne FALSE si une
   *  erreur s'est produite.
   */
  function enum_domains() {
    global $db,$err,$cuid;
    $err->log("dom","enum_domains");
    $db->query("select * from domaines where compte='$cuid' order by domaine asc;");
    $this->domains=array();
    if ($db->num_rows()>0) {
      while ($db->next_record()) {
      $this->domains[]=$db->f("domaine");
      }
    }
    return $this->domains;
  }

  function del_domain_cancel($dom) {
    global $db,$err,$classes,$cuid;
    $err->log("dom","del_domaini_canl",$dom);
    $dom=strtolower($dom);
    $db->query("UPDATE sub_domaines SET web_action='UPDATE'  WHERE domaine='$dom';");
    $db->query("UPDATE domaines SET dns_action='UPDATE'  WHERE domaine='$dom';");

    # TODO : some work with domain sensitive classes

    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   *  Efface un domaine du membre courant, et tous ses sous-domaines
   *
   * Cette fonction efface un domaine et tous ses sous-domaines, ainsi que
   * les autres services attachés à celui-ci. Elle appelle donc les autres
   * classe. Chaque classe peut déclarer une fonction del_dom qui sera
   * appellée lors de la destruction d'un domaine.
   *
   * @param string $dom nom de domaine à effacer
   * @return boolean Retourne FALSE si une erreur s'est produite, TRUE sinon.
   */
  function del_domain($dom) {
    global $db,$err,$classes,$cuid;
    $err->log("dom","del_domain",$dom);
    $dom=strtolower($dom);
    $db->query("SELECT * FROM domaines WHERE domaine='$dom';");
    if ($db->num_rows()==0) {
      $err->raise("dom",1,$dom);
      return false;
    }
    $db->next_record();
    if ($db->f("compte")!=$cuid) {
      $err->raise("dom",2,$dom);
      return false;
    }
    $db->query("UPDATE sub_domaines SET web_action='DELETE'  WHERE domaine='$dom';");
    $db->query("UPDATE domaines SET dns_action='DELETE'  WHERE domaine='$dom';");

    // DEPENDANCE :
    // Lancement de del_dom sur les classes domain_sensitive :
    // Declenchons les autres classes.
    foreach($classes as $c) {
      if (method_exists($GLOBALS[$c],"alternc_del_domain")) {
          $GLOBALS[$c]->alternc_del_domain($dom);
      }
    }
    foreach($classes as $c) {
      if (method_exists($GLOBALS[$c],"alternc_del_mx_domain")) {
          $GLOBALS[$c]->alternc_del_mx_domain($dom);
      }
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   *  Installe un domaine sur le compte courant.
   *
   * <p>Si le domaine existe déjà ou est interdit, ou est celui du serveur,
   * l'installation est refusée. Si l'hébergement DNS est demandé, la fonction
   * checkhostallow vérifiera que le domaine peut être installé conformément
   * aux demandes des super-admin.
   * Si le dns n'est pas demandé, le domaine peut être installé s'il est en
   * seconde main d'un tld (exemple : test.eu.org ou test.com, mais pas
   * toto.test.org ou test.test.asso.fr)</p>
   * <p>Chaque classe peut définir une fonction add_dom($dom) qui sera
   * appellée lors de l'installation d'un nouveau domaine.</p>
   *
   * @param string $dom nom fqdn du domaine à installer
   * @param integer $dns 1 ou 0 pour héberger le DNS du domaine ou pas.
   * @param integer $noerase 1 ou 0 pour rendre le domaine inamovible ou non
   * @param integer $force 1 ou 0, si 1, n'effectue pas les tests de DNS.
   *  force ne devrait être utilisé que par le super-admin.
   $ @return boolean Retourne FALSE si une erreur s'est produite, TRUE sinon.
  */
  function add_domain($domain,$dns,$noerase=0,$force=0,$isslave=0,$slavedom="") {
    global $db,$err,$quota,$classes,$L_MX,$L_FQDN,$tld,$cuid,$bro;
    $err->log("dom","add_domain",$domain);

    // Locked ?
    if (!$this->islocked) {
      $err->raise("dom",3);
      return false;
    }
    // Verifie que le domaine est rfc-compliant
    $domain=strtolower($domain);
    $t=checkfqdn($domain);
    if ($t) {
      $err->raise("dom",3+$t);
      return false;
    }
    // Interdit les domaines clés (table forbidden_domains) sauf en cas FORCE
    $db->query("select domain from forbidden_domains where domain='$domain'");
    if ($db->num_rows() && !$force) {
      $err->raise("dom",22);
      return false;
    }
    if ($domain==$L_FQDN || $domain=="www.$L_FQDN") {
      $err->raise("dom",18);
      return false;
    }
    $db->query("SELECT compte FROM domaines WHERE domaine='$domain';");
    if ($db->num_rows()) {
      $err->raise("dom",8);
      return false;
    }
    $db->query("SELECT compte FROM `sub_domaines` WHERE sub != \"\" AND concat( sub, \".\", domaine )='$domain' OR domaine='$domain';");
    if ($db->num_rows()) {
      $err->raise("dom",8);
      return false;
    }
    $this->dns=$this->whois($domain);
    if (!$force) {
      $v=checkhostallow($domain,$this->dns);
      if ($v==-1) {
        $err->raise("dom",7);   // TLD interdit
        return false;
      }
      if ($dns && $v==-2) {
        $err->raise("dom",12);   // Domaine non trouvé dans le whois
        return false;
      }
      if ($dns && $v==-3) {
        $err->raise("dom",23);   // Domaine non trouvé dans le whois
        return false;
      }

      if ($dns) $dns="1"; else $dns="0";

      // mode 5 : force DNS to NO.
      if ($tld[$v]==5) $dns=0;
      // It must be a real domain (no subdomain)
      if (!$dns) {
         $v=checkhostallow_nodns($domain);
         if ($v) {
           $err->raise("dom",22);
           return false;
         }
      }
    }
    // Check the quota :
    if (!$quota->cancreate("dom")) {
      $err->raise("dom",10);
      return false;
    }
    if ($noerase) $noerase="1"; else $noerase="0";
    $db->query("insert into domaines (compte,domaine,gesdns,gesmx,noerase,dns_action) values ('$cuid','$domain','$dns','1','$noerase','UPDATE');");

    if ($isslave) {
      $isslave=true;
      $db->query("SELECT domaine FROM domaines WHERE compte='$cuid' AND domaine='$slavedom';");
      $db->next_record();
      if (!$db->Record["domaine"]) {
        $err->raise("dom",1,$slavedom);
        $isslave=false;
      }
      // Point to the master domain : 
      $this->set_sub_domain($domain, '',     $this->type_url, 'http://www.'.$slavedom);
      $this->set_sub_domain($domain, 'www',  $this->type_url, 'http://www.'.$slavedom);
      $this->set_sub_domain($domain, 'mail', $this->type_url, 'http://mail.'.$slavedom);      
    }
    if (!$isslave) {
      // Creation du repertoire dans www
      $dest_root = $bro->get_userid_root($cuid);
      $domshort=str_replace("-","",str_replace(".","",$domain));
      
      if (! is_dir($dest_root . "/". $domshort)) {
        mkdir($dest_root . "/". $domshort);
      }
      
      // Creation des 3 sous-domaines par défaut : Vide, www et mail
      $this->set_sub_domain($domain, '',     $this->type_url,     'http://www.'.$domain);
      $this->set_sub_domain($domain, 'www',  $this->type_local,   '/'. $domshort);
      $this->set_sub_domain($domain, 'mail', $this->type_webmail, '');
    }

    // DEPENDANCE :
    // Lancement de add_dom sur les classes domain_sensitive :
    // Declenchons les autres classes.    
    foreach($classes as $c) {
      if (method_exists($GLOBALS[$c],"alternc_add_domain")) {
        $GLOBALS[$c]->alternc_add_domain($domain);
      }
    }
    foreach($classes as $c) {
      if (method_exists($GLOBALS[$c],"alternc_add_mx_domain")) {
        $GLOBALS[$c]->alternc_add_mx_domain($domain);
      }
    }
    if ($isslave) {
      foreach($classes as $c) {
        if (method_exists($GLOBALS[$c],"alternc_add_slave_domain")) {
          $GLOBALS[$c]->alternc_add_slave_domain($domain,$slavedom);
        }
      } 
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Retourne les entrées DNS du domaine $domain issues du WHOIS.
   *
   * Cette fonction effectue un appel WHOIS($domain) sur Internet,
   * et extrait du whois les serveurs DNS du domaine demandé. En fonction
   * du TLD, on sait (ou pas) faire le whois correspondant.
   * Actuellement, les tld suivants sont supportés :
   * .com .net .org .be .info .ca .cx .fr .biz .name
   *
   * @param string $domain Domaine fqdn dont on souhaite les serveurs DNS
   * @return array Retourne un tableau indexé avec les NOMS fqdn des dns
   *   du domaine demandé. Retourne FALSE si une erreur s'est produite.
   *
   */
  function whois($domain) {
    global $db,$err;
    $err->log("dom","whois",$domain);
    // pour ajouter un nouveau TLD, utiliser le code ci-dessous.
    //  echo "whois : $domain<br />";
    preg_match("#.*\.([^\.]*)#",$domain,$out);
    $ext=$out[1];
    // pour ajouter un nouveau TLD, utiliser le code ci-dessous.
    //  echo "ext: $ext<br />";

    if (($fp=@fsockopen("whois.iana.org", 43))>0) {
      fputs($fp, "$domain\r\n");
      $found = false;
      $state=0;
      while (!feof($fp)) {
        $ligne = fgets($fp,128);
        if (preg_match('#^whois:#', $ligne)) { $serveur=preg_replace('/whois:\ */','',$ligne,1); }
      }
    }
		$serveur=str_replace(array(" ","\n"),"",$serveur);

    $egal="";
    switch($ext) {
    case "net":
      $egal="=";
      break;
    case "name":
      $egal="domain = ";
      break;
    }
    // pour ajouter un nouveau TLD, utiliser le code ci-dessous.
    //  echo "serveur : $serveur <br />";
    if (($fp=@fsockopen($serveur, 43))>0) {
      fputs($fp, "$egal$domain\r\n");
      $found = false;
      $state=0;
      while (!feof($fp)) {
  $ligne = fgets($fp,128);
  // pour ajouter un nouveau TLD, utiliser le code ci-dessous.
  //  echo "| $ligne<br />";
  switch($ext) {
  case "org":
  case "com":
  case "net":
  case "info":
  case "biz":
  case "name":
  case "cc":
    if (preg_match("#Name Server:#", $ligne)) {
      $found = true;
      $tmp=strtolower(str_replace(chr(10), "",str_replace(chr(13),"",str_replace(" ","", str_replace("Name Server:","", $ligne)))));
      if ($tmp)
        $server[]=$tmp;
    }
    break;
  case "cx":
    $ligne = str_replace(chr(10), "",str_replace(chr(13),"",str_replace(" ","", $ligne)));
    if ($ligne=="" && $state==1)
      $state=2;
    if ($state==1)
      $server[]=strtolower($ligne);
    if ($ligne=="Nameservers:" && $state==0) {
      $state=1;
      $found = true;
    }
    break;
        case "eu":
  case "be":
          $ligne=preg_replace("/^ *([^ ]*) \(.*\)$/","\\1",trim($ligne));
          if($found)
             $tmp = trim($ligne);
          if ($tmp)
             $server[]=$tmp;
          if ($ligne=="Nameservers:") {
            $state=1;
            $found=true;
          }
          break;
    case "im":
          if (preg_match('/Name Server:/', $ligne)) {
            $found = true;
            // weird regexp (trailing garbage after name server), but I could not make it work otherwise
            $tmp = strtolower(preg_replace('/Name Server: ([^ ]+)\..$/',"\\1", $ligne));
            $tmp = preg_replace('/[^-_a-z0-9\.]/', '', $tmp);
            if ($tmp)
              $server[]=$tmp;
          }
          break;
    case "it":
          if (preg_match("#nserver:#", $ligne)) {
            $found=true;
            $tmp=strtolower(preg_replace("/nserver:\s*[^ ]*\s*([^\s]*)$/","\\1", $ligne));
            if ($tmp)
              $server[]=$tmp;
          }
          break;
  case "fr":
  case "re":
          if (preg_match("#nserver:#", $ligne)) {
            $found=true;
            $tmp=strtolower(preg_replace("#nserver:\s*([^\s]*)\s*.*$#","\\1", $ligne));
            if ($tmp)
              $server[]=$tmp;
          }
          break;
  case "ca":
  case "ws";
    if (preg_match('#Name servers#', $ligne)) {
          // found the server
      $state = 1;
    } elseif ($state) {
      if (preg_match('#^[^%]#', $ligne) && $ligne = preg_replace('#[[:space:]]#', "", $ligne)) {
      // first non-whitespace line is considered to be the nameservers themselves
      $found = true;
      $server[] = $ligne;
    }
    }
    break;
        case "coop":
          if (preg_match('#Host Name:\s*([^\s]+)#', $ligne, $matches)) {
            $found = true;
            $server[] = $matches[1];
          }
  } // switch
      } // while
      fclose($fp);
    } else {
      $err->raise("dom",11);
      return false;
    }

    if ($found) {
      return $server;
    } else {
      $err->raise("dom",12);
      return false;
    }
  } // whois

  /* ----------------------------------------------------------------- */
  /**
   *  vérifie la presence d'un champs mx valide sur un serveur DNS
   *
  */
  
  function checkmx($domaine,$mx) {
    //initialise variables
    $mxhosts = array();
    
    //récupére les champs mx
    if (!getmxrr($domaine,$mxhosts)) {
      //aucune hôte mx spécifié
      return 1;
    }
    else {
      //vérifie qu'un des hôtes est bien sur alternc
      $bolmx = 0;
      //décompose les différents champ MX coté alternc
      $arrlocalmx = split(",",$mx);
      //parcours les différents champ MX retournés
      foreach($mxhosts as $mxhost) {
        foreach($arrlocalmx as $localmx) {
          if ($mxhost==$localmx) {
            $bolmx = 1;
          }
        }
      }
      //définition de l'erreur selon reponse du parcours de mxhosts
      if ($bolmx == 0) {
        //aucun des champs MX ne correspond au serveur
        return 2;          
      }
      else {
        //un champ mx correct a été trouvé
        return 0;
      }
    }
  } //checkmx


  /* ----------------------------------------------------------------- */
  /**
   *  retourne TOUTES les infos d'un domaine
   *
   * @param string $dom Domaine dont on souhaite les informations
   * @return array Retourne toutes les infos du domaine sous la forme d'un
   * tableau associatif comme suit :<br /><pre>
   *  $r["name"] =  Nom fqdn
   *  $r["dns"]  =  Gestion du dns ou pas ?
   *  $r["mx"]   =  Valeur du champs MX si "dns"=true
   *  $r["mail"] =  Heberge-t-on le mail ou pas ? (si "dns"=false)
   *  $r["nsub"] =  Nombre de sous-domaines
   *  $r["sub"]  =  tableau associatif des sous-domaines
   *  $r["sub"][0-(nsub-1)]["name"] = nom du sous-domaine (NON-complet)
   *  $r["sub"][0-(nsub-1)]["dest"] = Destination (url, ip, local ...)
   *  $r["sub"][0-(nsub-1)]["type"] = Type (0-n) de la redirection.
   *  </pre>
   *  Retourne FALSE si une erreur s'est produite.
   *
   */
  function get_domain_all($dom) {
    global $db,$err,$cuid;
    $err->log("dom","get_domain_all",$dom);
    // Locked ?
    if (!$this->islocked) {
      $err->raise("dom",3);
      return false;
    }
    $t=checkfqdn($dom);
    if ($t) {
      $err->raise("dom",3+$t);
      return false;
    }
    $r["name"]=$dom;
    $db->query("select * from domaines where compte='$cuid' and domaine='$dom'");
    if ($db->num_rows()==0) {
      $err->raise("dom",1,$dom);
      return false;
    }
    $db->next_record();
    $r["dns"]=$db->Record["gesdns"];
    $r["dns_action"]=$db->Record["dns_action"];
    $r["dns_result"]=$db->Record["dns_result"];
    $r["mail"]=$db->Record["gesmx"];
    $r["mx"]=$db->Record["mx"];
    $r['noerase']=$db->Record['noerase'];
    $db->free();
    $db->query("select count(*) as cnt from sub_domaines where compte='$cuid' and domaine='$dom'");
    $db->next_record();
    $r["nsub"]=$db->Record["cnt"];
    $db->free();
    $db->query("select sd.*, dt.description as type_desc, dt.only_dns from sub_domaines sd, domaines_type dt where compte='$cuid' and domaine='$dom' and upper(dt.name)=upper(sd.type) order by sd.sub,sd.type");
    // Pas de webmail, on le cochera si on le trouve.
    $this->webmail=0;
    for($i=0;$i<$r["nsub"];$i++) {
      $db->next_record();
      $r["sub"][$i]=array();
      $r["sub"][$i]["name"]=$db->Record["sub"];
      $r["sub"][$i]["dest"]=$db->Record["valeur"];
      $r["sub"][$i]["type"]=$db->Record["type"];
      $r["sub"][$i]["enable"]=$db->Record["enable"];
      $r["sub"][$i]["type_desc"]=$db->Record["type_desc"];
      $r["sub"][$i]["only_dns"]=$db->Record["only_dns"];
      $r["sub"][$i]["web_action"]=$db->Record["web_action"];
/*
      if ($db->Record["type"]==3) { // Webmail
  $this->webmail=1;
  $r["sub"][$i]["dest"]=_("Webmail access");
      }
*/
    }
    $db->free();
    return $r;
  } // get_domain_all


  /* ----------------------------------------------------------------- */
  /**
   * Retourne TOUTES les infos d'un sous domaine du compte courant.
   *
   * @param string $dom Domaine fqdn concerné
   * @param string $sub Sous-domaine dont on souhaite les informations
   * @return arrray Retourne un tableau associatif contenant les
   *  informations du sous-domaine demandé.<pre>
   *  $r["name"]= nom du sous-domaine (NON-complet)
   *  $r["dest"]= Destination (url, ip, local ...)
   *  </pre>
   *  $r["type"]= Type (0-n) de la redirection.
   *  Retourne FALSE si une erreur s'est produite.
   */
  function get_sub_domain_all($dom,$sub, $type="", $value='') {
    global $db,$err,$cuid;
    $err->log("dom","get_sub_domain_all",$dom."/".$sub);
    // Locked ?
    if (!$this->islocked) {
      $err->raise("dom",3);
      return false;
    }
    $t=checkfqdn($dom);
    if ($t) {
      $err->raise("dom",3+$t);
      return false;
    }
/*
    if ( ! empty($value)) {
        $type = " and valeur=\"".mysql_real_escape_string($value)."\"";
    }
    if ( ! empty($type)) {
        $type = " and type=\"".mysql_real_escape_string($type)."\"";
    }
*/
    $db->query("select sd.*, dt.description as type_desc, dt.only_dns from sub_domaines sd, domaines_type dt where compte='$cuid' and domaine='$dom' and sub='$sub' and ( length('$type')=0 or type='$type') and (length('$value')=0 or '$value'=valeur) and upper(dt.name)=upper(sd.type);");
    if ($db->num_rows()==0) {
      $err->raise("dom",14);
      return false;
    }
    $db->next_record();
    $r=array();
    $r["name"]=$db->Record["sub"];
    $r["dest"]=$db->Record["valeur"];
    $r["enable"]=$db->Record["enable"];
    $r["type_desc"]=$db->Record["type_desc"];
    $r["only_dns"]=$db->Record["only_dns"];
    $r["web_action"]=$db->Record["web_action"];
    $db->free();
    return $r;
  } // get_sub_domain_all


  function check_type_value($type, $value) {
    global $db,$err,$cuid;

    // check the type we can have in domaines_type.target

    switch ($this->domains_type_target_values($type)) {
      case 'NONE':
        if (empty($value) or is_null($value)) {return true;}
        break;
      case 'URL': 
        if ( $value == strval($value)) {return true;}
        break;
      case 'DIRECTORY': 
        if (substr($value,0,1)!="/") {
          $value="/".$value;
        }
        if (!checkuserpath($value)) {
          $err->raise("dom",21);
        return false;
        }
        return true;
        break;
      case 'IP': 
        if (checkip($value)) {return true;}
        break;
      case 'IPV6': 
        if (checkipv6($value)) {return true;}
        break;
      case 'DOMAIN': 
        if (checkcname($value)) {return true;}
        break;
      case 'TXT':
        if ( $value == strval($value)) {return true;}
        break;
      default:
        return false;
        break;
    }
    return false;
  } //check_type_value


  function can_create_subdomain($dom,$sub,$type,$type_old='', $value_old='') {
    global $db,$err,$cuid;
    $err->log("dom","can_create_subdomain",$dom."/".$sub);

    # Get the compatibility list for this domain type
    $db->query("select upper(compatibility) as compatibility from domaines_type where upper(name)=upper('$type');");
    if (!$db->next_record()) return false;
    $compatibility_lst = explode(",",$db->f('compatibility'));

    # Get the list of type of subdomains already here who have the same name
    $db->query("select * from sub_domaines where sub='$sub' and domaine='$dom' and not (type='$type_old' and valeur='$value_old') and web_action != 'DELETE'");
    #$db->query("select * from sub_domaines where sub='$sub' and domaine='$dom';");
    while ($db->next_record()) {
      # And if there is a domain with a incompatible type, return false
      if (! in_array(strtoupper($db->f('type')),$compatibility_lst)) return false;
    }
    
    # All is right, go ! Create ur domain !
    return true;
  }

  //  /* ----------------------------------------------------------------- */
  /**
   * Modifier les information du sous-domaine demandé.
   *
   * <b>Note</b> : si le sous-domaine $sub.$dom n'existe pas, il est créé.<br />
   * <b>Note : TODO</b> : vérification de concordance de $dest<br />
   *
   * @param string $dom Domaine dont on souhaite modifier/ajouter un sous domaine
   * @param string $subk Sous domaine à modifier / créer
   * @param integer $type Type de sous-domaine (local, ip, url ...)
   * @param string $action Action : vaut "add" ou "edit" selon que l'on
   *  Crée (add) ou Modifie (edit) le sous-domaine
   * @param string $dest Destination du sous-domaine, dépend de la valeur
   *  de $type (url, ip, dossier...)
   * @return boolean Retourne FALSE si une erreur s'est produite, TRUE sinon.
   */
   // TODO : j'ai viré le type action, valider que plus personne ne l'utilise (quatrieme argument)
  function set_sub_domain($dom,$sub,$type,$dest, $type_old=null,$sub_old=null,$value_old=null) {
    global $db,$err,$cuid;
    $err->log("dom","set_sub_domain",$dom."/".$sub."/".$type."/".$dest);
    // Locked ?
    if (!$this->islocked) {
      $err->raise("dom",3);
      return false;
    }
    $dest=trim($dest);
    $sub=trim(trim($sub),".");
    $dom=strtolower($dom);
    $sub=strtolower($sub);

    //    if (!(($sub == '*') || ($sub=="") || (preg_match('/([a-z0-9][\.\-a-z0-9]*)?[a-z0-9]/', $sub)))) {
    $fqdn=checkfqdn($sub);
    // Special cases : * (all subdomains at once) and '' empty subdomain are allowed.
    if (($sub != '*' && $sub!='') && !($fqdn==0 || $fqdn==4)) {
      $err->raise("dom",24);
      return false;
    }

    if (! $this->check_type_value($type,$dest)) {
      # TODO have a real err code
      $err->raise("dom",667);
      return false;
    }

    // On a épuré $dir des problèmes eventuels ... On est en DESSOUS du dossier de l'utilisateur.
    if ($t=checkfqdn($dom)) {
      $err->raise("dom",3+$t);
      return false;
    }

    if (! $this->can_create_subdomain($dom,$sub,$type,$type_old,$value_old)) {
      # TODO have a real error code
      $err->raise("dom", 654);
      return false;
    }

    if (! is_null($type_old )) { // It's not a creation, it's an edit. Delete the old one
      $db->query("update sub_domaines set web_action='DELETE' where domaine='$dom' and sub='$sub' and upper(type)=upper('$type_old') and valeur='$value_old';");
    }

    // Re-create the one we want
    if (! $db->query("insert into sub_domaines (compte,domaine,sub,valeur,type,web_action) values ('$cuid','$dom','$sub','$dest','$type','UPDATE');") ) {
      echo "query failed: ".$db->Error;
      return false;
    }

    // Tell to update the DNS file
    $db->query("update domaines set dns_action='UPDATE' where domaine='$dom';");

    return true;
  } // set_sub_domain


  /* ----------------------------------------------------------------- */
  /**
   *  Supprime le sous-domaine demandé
   *
   * @param string $dom Domaine dont on souhaite supprimer un sous-domaine
   * @param string $sub Sous-domaine que l'on souhaite supprimer
   * @return boolean Retourne FALSE si une erreur s'est produite, TRUE sinon.
   *
   */
  function del_sub_domain($dom,$sub,$type,$value='') {
    global $db,$err,$cuid;
    $err->log("dom","del_sub_domain",$dom."/".$sub);
    // Locked ?
    if (!$this->islocked) {
      $err->raise("dom",3);
      return false;
    }
    $t=checkfqdn($dom);
    if ($t) {
      $err->raise("dom",3+$t);
      return false;
    }
    if (!$r=$this->get_sub_domain_all($dom,$sub,$type)) {
      // Le sous-domaine n'existe pas, erreur
      $err->raise("dom",14);
      return false;
    } else {
      // OK, on valide :
      $db->query("update sub_domaines set web_action='DELETE' where domaine='$dom' and sub='$sub' and type='$type' and ( length('$value')=0 or valeur='$value') ");
      $db->query("update domaines set dns_action='UPDATE' where domaine='$dom';");
    }
    return true;
  } // del_sub_domain

  /* ----------------------------------------------------------------- */
  /**
   * Modifie les information du domaine précisé.
   *
   * @param string $dom Domaine du compte courant que l'on souhaite modifier
   * @param integer $dns Vaut 1 ou 0 pour héberger ou pas le DNS du domaine
   * @param integer $gesmx Héberge-t-on le emails du domaines sur ce serveur ?
   * @param boolean $force Faut-il passer les checks DNS ou MX ? (admin only)
   * @return boolean appelle $mail->add_dom ou $ma->del_dom si besoin, en
   *  fonction du champs MX. Retourne FALSE si une erreur s'est produite,
   *  TRUE sinon.
   *
   */
  function edit_domain($dom,$dns,$gesmx,$force=0) {
    global $db,$err,$L_MX,$classes,$cuid;
    $err->log("dom","edit_domain",$dom."/".$dns."/".$gesmx);
    // Locked ?
    if (!$this->islocked && !$force) {
      $err->raise("dom",3);
      return false;
    }
    if ($dns == 1 && !$force) {
      $this->dns=$this->whois($dom);
      $v=checkhostallow($dom,$this->dns);
      if ($v==-1) {
        $err->raise("dom",7);   // TLD interdit
        return false;
      }
      if ($dns && $v==-2) {
        $err->raise("dom",12);  // Domaine non trouvé dans le whois
        return false;
      }
      if ($dns && $v==-3) {
        $err->raise("dom",23);  // Domaine non trouvé dans le whois
        return false;
      }
    }
    $t=checkfqdn($dom);
    if ($t) {
      $err->raise("dom",3+$t);
      return false;
    }
    if (!$r=$this->get_domain_all($dom)) {
      // Le domaine n'existe pas, Failure
      $err->raise("dom",4,$dom);
      return false;
    }
    if ($dns!="1") $dns="0";
    // On vérifie que des modifications ont bien eu lieu :)
    if ($r["dns"]==$dns && $r["mail"]==$gesmx) {
      $err->raise("dom",15);
      return false;
    }
      
    //si gestion mx uniquement, vérification du dns externe
    if ($dns=="0" && $gesmx=="1" && !$force) {
      $vmx = $this->checkmx($dom,$mx);
      if ($vmx == 1) {
        // Aucun champ mx de spécifié sur le dns
	$err->raise("dom",25);
	return false;
      }
      
      if ($vmx == 2) {
        // Serveur non spécifié parmi les champx mx
	$err->raise("dom",25);
	return false;
      }
    }
      
    // OK, des modifs ont été faites, on valide :
    // DEPENDANCE :
    if ($gesmx && !$r["mail"]) { // on a associé le MX : on cree donc l'entree dans MySQL
      // Lancement de add_dom sur les classes domain_sensitive :
      foreach($classes as $c) {
	if (method_exists($GLOBALS[$c],"alternc_add_mx_domain")) {
	  $GLOBALS[$c]->alternc_add_mx_domain($dom);
	}
      }
    }
    
    if (!$gesmx && $r["mail"]) { // on a dissocié le MX : on détruit donc l'entree dans LDAP
      // Lancement de del_dom sur les classes domain_sensitive :
      foreach($classes as $c) {
	if (method_exists($GLOBALS[$c],"alternc_del_mx_domain")) {
	  $GLOBALS[$c]->alternc_del_mx_domain($dom);
	}
      }
    }
    
    $db->query("UPDATE domaines SET gesdns='$dns', gesmx='$gesmx' WHERE domaine='$dom'");
    $db->query("UPDATE domaines set dns_action='UPDATE' where domaine='$dom';");
    
    return true;
  } // edit_domain
  


  /****************************/
  /*  Slave dns ip managment  */
  /****************************/
  /* ----------------------------------------------------------------- */
  /**
   * Return the list of ip addresses and classes that are allowed access to domain list
   * through AXFR Transfers from the bind server.
   */
  function enum_slave_ip() {
  global $db,$err;
  $db->query("SELECT * FROM slaveip;");
  if (!$db->next_record()) {
    return false;
  }
  do {
    $res[]=$db->Record;
  } while ($db->next_record());
  return $res;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Add an ip address (or a ip class) to the list of allowed slave ip access list.
   */
  function add_slave_ip($ip,$class="32") {
  global $db,$err;
  if (!checkip($ip)) {
    $err->raise("dom",19);
    return false;
  }
  $class=intval($class);
  if ($class<8 || $class>32) $class=32;
  $db->query("SELECT * FROM slaveip WHERE ip='$ip' AND class='$class';");
  if ($db->next_record()) {
    $err->raise("err",22);
    return false;
  }
  $db->query("INSERT INTO slaveip (ip,class) VALUES ('$ip','$class');");
  $f=fopen(SLAVE_FLAG,"w");
  fputs($f,"yopla");
  fclose($f);  
  return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Remove an ip address (or a ip class) from the list of allowed slave ip access list.
   */
  function del_slave_ip($ip) {
  global $db,$err;
  if (!checkip($ip)) {
    $err->raise("dom",19);
    return false;
  }
  $db->query("DELETE FROM slaveip WHERE ip='$ip'");
  $f=fopen(SLAVE_FLAG,"w");
  fputs($f,"yopla");
  fclose($f);  
  return true;
  }



  /* ----------------------------------------------------------------- */
  /**
   * Check for a slave account
   */
  function check_slave_account($login,$pass) {
  global $db,$err;
  $db->query("SELECT * FROM slaveaccount WHERE login='$login' AND pass='$pass';");
  if ($db->next_record()) { 
    return true;
  }
  return false;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Out (echo) the complete hosted domain list : 
   */
  function echo_domain_list() {
  global $db,$err;
  $db->query("SELECT domaine FROM domaines WHERE gesdns=1 ORDER BY domaine");
  while ($db->next_record()) {
    echo $db->f("domaine")."\n";
  }
  return true;
  }


  /* ----------------------------------------------------------------- */
  /**
   * Returns the complete hosted domain list : 
   */
  function get_domain_list($uid=-1) {
  global $db,$err;
  $uid=intval($uid);
  $res=array();
  if ($uid!=-1) {
    $sql=" AND compte='$uid' ";
  }
  $db->query("SELECT domaine FROM domaines WHERE gesdns=1 $sql ORDER BY domaine");
  while ($db->next_record()) {
    $res[]=$db->f("domaine");
  }
  return $res;
  }


  /* ----------------------------------------------------------------- */
  /**
   * Return the list of allowed slave accounts 
   */
  function enum_slave_account() {
  global $db,$err;
  $db->query("SELECT * FROM slaveaccount;");
  $res=array();
  while ($db->next_record()) {
    $res[]=$db->Record;
  }
  if (!count($res)) return false;
  return $res;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Add a slave account that will be allowed to access the domain list
   */
  function add_slave_account($login,$pass) {
  global $db,$err;
  $db->query("SELECT * FROM slaveaccount WHERE login='$login'");
  if ($db->next_record()) {
    $err->raise("err",23);
    return false;
  }
  $db->query("INSERT INTO slaveaccount (login,pass) VALUES ('$login','$pass')");
  return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Remove a slave account
   */
  function del_slave_account($login) {
  global $db,$err;
  $db->query("DELETE FROM slaveaccount WHERE login='$login'");
  return true;
  }

  /*************/
  /*  Private  */
  /*************/


  /* ----------------------------------------------------------------- */
  /**
   * Lock tente de verrouiller le fichier lock du cron. Si tout va bien (toujours?)
   * retourne True, sinon retourne False
   * NOTE : le systeme de lock est asymétrique, si on a un fichier CRONLOCK, on
   * attends (que le cron ait fini son execution).
   * @access private
   */
  function lock() {
    global $db,$err;
    $err->log("dom","lock");
    if ($this->islocked) {
      $err->raise("dom",17);
    }
    while (file_exists($this->fic_lock_cron)) {
      sleep(2);
    }
    $this->islocked=true;
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * unlock déverrouille le fichier lock du cron. Si tout va bien (toujours?)
   * retourne True, sinon retourne False
   * NOTE : actuellement, vu le système de lock asymetrique, on ne fait rien ;)
   * @access private
   */
  function unlock() {
    global $db,$err;
    $err->log("dom","unlock");
    if (!$this->islocked) {
      $err->raise("dom",3);
    }
    $this->islocked=false;
    return true;
  }


  /* ----------------------------------------------------------------- */
  /**
   * Declare that a domain's emails are hosted in this server : 
   * This adds 2 MX entries in this domain (if required)
   */
  function alternc_add_mx_domain($domain) {
    global $err;
    $err->log("dom","alternc_add_mx_domain");
    $this->set_sub_domain($domain, '', $this->type_defmx, '');
    if (! empty($GLOBALS['L_DEFAULT_SECONDARY_MX'])) {
      $this->set_sub_domain($domain, '', $this->type_defmx2, '');
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  /**
   * Efface un compte (tous ses domaines)
   */
  function alternc_del_member() {
    global $err;
    $err->log("dom","alternc_del_member");
    $li=$this->enum_domains();
    foreach($li as $dom) {
      $this->del_domain($dom);
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  /**
   * Returns the used quota for the $name service for the current user.
   * @param $name string name of the quota
   * @return integer the number of service used or false if an error occured
   * @access private
   */
  function alternc_get_quota($name) {
    global $db,$err,$cuid;
    if ($name=="dom") {
      $err->log("dom","get_quota");
      $db->query("SELECT COUNT(*) AS cnt FROM domaines WHERE compte='$cuid'");
      $db->next_record();
      return $db->f("cnt");
    } else return false;
  }


  /* ----------------------------------------------------------------- */
  /**
   * Exporte toutes les informations domaine du compte.
   * @access private
   * EXPERIMENTAL 'sid' function ;) 
   */
  function alternc_export() {
    global $db,$err;
    $err->log("dom","export");
    $this->enum_domains();
    $str="<dom>\n";
    foreach ($this->domains as $d) {
      $str.="  <domain>\n    <name>".xml_entities($d)."</name>\n";
      $s=$this->get_domain_all($d);
      $str.="    <hasdns>".xml_entities($s[dns])."</hasdns>\n";
      $str.="    <hasmx>".xml_entities($s[mx])."</hasmx>\n";
      $str.="    <mx>".xml_entities($s[mail])."</mx>\n";
      if (is_array($s[sub])) {
        foreach ($s[sub] as $sub) {
          $str.="    <subdomain>";
          $str.="<name>".xml_entities($sub[name])."</name>";
          $str.="<dest>".xml_entities($sub[dest])."</dest>";
          $str.="<type>".xml_entities($sub[type])."</type>";
          $str.="</subdomain>\n";
        }
      }
      $str.="  </domain>\n";
    }
    $str.="</dom>\n";
    return $str;
  }


} /* Class m_domains */

?>
