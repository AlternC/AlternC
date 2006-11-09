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

  var $type_local = "0";
  var $type_url = "1";
  var $type_ip = "2";
  var $type_webmail = "3";

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
   * Retourne un tableau contenant les domaines d'un membre.
   *
   * @return array retourne un tableau indexé contenant la liste des
   *  domaines hébergés sur le compte courant. Retourne FALSE si une
   *  erreur s'est produite.
   */
  function enum_domains() {
    global $db,$err,$cuid;
    $err->log("dom","enum_domains");
    $db->query("select * from domaines where compte='$cuid';");
    $this->domains=array();
    if ($db->num_rows()>0) {
      while ($db->next_record()) {
	$this->domains[]=$db->f("domaine");
      }
    }
    return $this->domains;
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
    $db->query("INSERT INTO domaines_standby (compte,domaine,mx,gesdns,gesmx,action) SELECT compte,domaine,mx,gesdns,gesmx,2 FROM domaines WHERE domaine='$dom'"); // DELETE
    $db->query("DELETE FROM domaines WHERE domaine='$dom';");
    $db->query("DELETE FROM sub_domaines WHERE domaine='$dom';");

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
  function add_domain($domain,$dns,$noerase=0,$force=0) {
    global $db,$err,$quota,$classes,$L_MX,$L_FQDN,$tld,$cuid;
    $err->log("dom","add_domain",$domain);
    $mx="1";
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
    $db->query("select compte from domaines_standby where domaine='$domain';");
    if ($db->num_rows()!=0) {
      $err->raise("dom",9);
      return false;
    }
    $this->dns=$this->whois($domain);
    if (!$force) {
      $v=checkhostallow($domain,$this->dns);
      if ($v==-1) {
	$err->raise("dom",7); 	// TLD interdit
	return false;
      }
      if ($dns && $v==-2) {
	$err->raise("dom",12); 	// Domaine non trouvé dans le whois
	return false;
      }
      if ($dns && $v==-3) {
	$err->raise("dom",23); 	// Domaine non trouvé dans le whois
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
    $db->query("insert into domaines (compte,domaine,mx,gesdns,gesmx,noerase) values ('$cuid','$domain','$L_MX','$dns','$mx','$noerase');");
    $db->query("insert into domaines_standby (compte,domaine,mx,gesdns,gesmx,action) values ('$cuid','$domain','$L_MX','$dns','$mx',0);"); // INSERT
    // Creation des 3 sous-domaines par défaut : Vide, www et mail
    $this->set_sub_domain($domain, '',     $this->type_url,     'add', 'http://www.'.$domain);
    $this->set_sub_domain($domain, 'www',  $this->type_local,   'add', '/');
    $this->set_sub_domain($domain, 'mail', $this->type_webmail, 'add', '');
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
    //	echo "whois : $domain<br />";
    ereg(".*\.([^\.]*)",$domain,$out);
    $ext=$out[1];
    // pour ajouter un nouveau TLD, utiliser le code ci-dessous.
    //	echo "ext: $ext<br />";
    $egal="";
    switch($ext) {
    case "com":
    case "net":
      $serveur="rs.internic.net";
      $egal="=";
      break;
    case "org":
      $serveur="whois.pir.org";
      break;
    case "be":
      $serveur="whois.dns.be";
      break;
    case "eu":
      $serveur="195.234.53.193";
      break;
    case "info":
      $serveur="whois.afilias.net";
      break;
    case "ca":
      $serveur="whois.cira.ca";
      break;
    case "cx":
      $serveur="whois.nic.cx";
      break;
    case "it":
      $serveur="whois.nic.it";
      break;
    case "fr":
      $serveur="whois.nic.fr";
      break;
    case "biz":
      $serveur="whois.nic.biz";
      break;
    case "name":
      $serveur="whois.nic.name";
      break;
    case "ws":
      $serveur="whois.samoanic.ws";
      break;
    default:
      $err->raise("dom",7);
      return false;
      break;
    }
    // pour ajouter un nouveau TLD, utiliser le code ci-dessous.
    //	echo "serveur : $serveur <br />";
    if (($fp=fsockopen($serveur, 43))>0) {
      fputs($fp, "$egal$domain\r\n");
      $found = false;
      $state=0;
      while (!feof($fp)) {
	$ligne = fgets($fp,128);
	// pour ajouter un nouveau TLD, utiliser le code ci-dessous.
	//	echo "| $ligne<br />";
	switch($ext) {
	case "org":
	case "com":
	case "net":
	case "info":
	case "biz":
	case "name":
	  if (ereg("Name Server:", $ligne)) {
	    $found = true;
	    $tmp=strtolower(ereg_replace(chr(10), "",ereg_replace(chr(13),"",ereg_replace(" ","", ereg_replace("Name Server:","", $ligne)))));
	    if ($tmp)
	      $server[]=$tmp;
	  }
	  break;
	case "cx":
	  $ligne = ereg_replace(chr(10), "",ereg_replace(chr(13),"",ereg_replace(" ","", $ligne)));
	  if ($ligne=="" && $state==1)
	    $state=2;
	  if ($state==1)
	    $server[]=strtolower($ligne);
	  if ($ligne=="Nameservers:" && $state==0) {
	    $state=1;
	    $found = true;
	  }
	  break;
	case "ca":
	  $ligne=ereg_replace(chr(10), "",ereg_replace(chr(13),"",ereg_replace(" ","", $ligne)));
	  if ($ligne=="Status:EXIST")
	    $found=true;
	  if (ereg("NS.-Hostname:", $ligne)) {
	    $tmp=strtolower(ereg_replace("NS.-Hostname:","", $ligne));
	    if ($tmp)
	      $server[]=$tmp;
	  }
	  break;
        case "eu":
	case "be":
          $ligne=ereg_replace(chr(10), "",ereg_replace(chr(13),"",ereg_replace(" ","", $ligne)));
          if($found)
             $tmp = trim($ligne);
          if ($tmp)
             $server[]=$tmp;
          if ($ligne=="Nameservers:") {
            $state=1;
            $found=true;
          }
          break;
        case "it":
          if (ereg("nserver:", $ligne)) {
            $found=true;
            $tmp=strtolower(preg_replace("/nserver:\s*[^ ]*\s*([^\s]*)$/","\\1", $ligne));
            if ($tmp)
              $server[]=$tmp;
          }
          break;
	case "fr":
          if (ereg("nserver:", $ligne)) {
            $found=true;
            $tmp=strtolower(preg_replace("/nserver:\s*([^\s]*)\s*.*$/","\\1", $ligne));
            if ($tmp)
              $server[]=$tmp;
          }
          break;
	case "ws";
/* e.g.
Welcome to the .WS Whois Server

Use of this service for any purpose other 
than determining the availability of a domain 
in the .WS TLD to be registered is strictly 
prohibited.

  Domain Name: DRONE.WS

  Registrant: Registered through Go Daddy Software, Inc. (GoDaddy.com

  Domain created on 2005-01-11 08:56:25
  Domain last updated on 2005-01-11 08:56:25

  Name servers:

    ns2.koumbit.net
    ns1.koumbit.net
*/

/*
failure:

Welcome to the .WS Whois Server

Use of this service for any purpose other 
than determining the availability of a domain 
in the .WS TLD to be registered is strictly 
prohibited.

No match for "dronefdasfsa.ws".

*/
	  if (ereg('^[[:space:]]*Name servers:[[:space:]]*$', $ligne)) {
	        // found the server
	  	$state = 1;
	  } elseif ($state) {
	  	if ($ligne = ereg_replace('[[:space:]]', "", $ligne)) {
		  // first non-whitespace line is considered to be the nameservers themselves
		  $found = true;
		  $server[] = $ligne;
		}
	  }
	  break;
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
   * <b>Note</b> : si le domaine est en attente (présent dans
   *  domaines_standby), une erreur est retournée
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
    $db->query("select * from domaines_standby where compte='$cuid' and domaine='$dom'");
    if ($db->num_rows()>0) {
      $err->raise("dom",13);
      return false;
    }
    $db->query("select * from domaines where compte='$cuid' and domaine='$dom'");
    if ($db->num_rows()==0) {
      $err->raise("dom",1,$dom);
      return false;
    }
    $db->next_record();
    $r["dns"]=$db->Record["gesdns"];
    $r["mail"]=$db->Record["gesmx"];
    $r["mx"]=$db->Record["mx"];
    $r[noerase]=$db->Record[noerase];
    $db->free();
    $db->query("select count(*) as cnt from sub_domaines where compte='$cuid' and domaine='$dom'");
    $db->next_record();
    $r["nsub"]=$db->Record["cnt"];
    $db->free();
    $db->query("select * from sub_domaines where compte='$cuid' and domaine='$dom'");
    // Pas de webmail, on le cochera si on le trouve.
    $this->webmail=0;
    for($i=0;$i<$r["nsub"];$i++) {
      $db->next_record();
      $r["sub"][$i]=array();
      $r["sub"][$i]["name"]=$db->Record["sub"];
      $r["sub"][$i]["dest"]=$db->Record["valeur"];
      $r["sub"][$i]["type"]=$db->Record["type"];
      if ($db->Record["type"]==3) { // Webmail
	$this->webmail=1;
	$r["sub"][$i]["dest"]=_("Webmail access");
      }
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
  function get_sub_domain_all($dom,$sub) {
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
    $db->query("select * from sub_domaines where compte='$cuid' and domaine='$dom' and sub='$sub'");
    if ($db->num_rows()==0) {
      $err->raise("dom",14);
      return false;
    }
    $db->next_record();
    $r=array();
    $r["name"]=$db->Record["sub"];
    $r["dest"]=$db->Record["valeur"];
    $r["type"]=$db->Record["type"];
    $db->free();
    return $r;
  } // get_sub_domain_all

  /* ----------------------------------------------------------------- */
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
  function set_sub_domain($dom,$sub,$type,$action,$dest) {
    global $db,$err,$cuid;
    $err->log("dom","set_sub_domain",$dom."/".$sub);
    // Locked ?
    if (!$this->islocked) {
      $err->raise("dom",3);
      return false;
    }
    $dest=trim($dest);
    $dom=strtolower($dom);
    $sub=strtolower($sub);

    if (!(($sub == '*') || ($sub=="") || (preg_match('/([a-z0-9][\.\-a-z0-9]*)?[a-z0-9]/', $sub)))) {
      $err->raise("dom",24);
      return false;
    }
    if ($type==2) { // IP
      if (!checkip($dest)) {
	$err->raise("dom",19);
	return false;
      }
    }
    if ($type==1) { // URL
      if (!checkurl($dest)) {
	$err->raise("dom",20);
	return false;
      }
    }
    if ($type==0) { // LOCAL
      if (substr($dest,0,1)!="/") {
	$dest="/".$dest;
      }
      if (!checkuserpath($dest)) {
	$err->raise("dom",21);
	return false;
      }
    }
    // On a épuré $dir des problèmes eventuels ... On est en DESSOUS du dossier de l'utilisateur.
    $t=checkfqdn($dom);
    if ($t) {
      $err->raise("dom",3+$t);
      return false;
    }
    if (!$r=$this->get_sub_domain_all($dom,$sub)) {
      // Le sous-domaine n'existe pas, on le crée seulement si $action vaut add
      if ($action=="add") {
	$db->query("insert into sub_domaines (compte,domaine,sub,valeur,type) values ('$cuid','$dom','$sub','$dest',$type);");
	$db->query("delete from sub_domaines_standby where domaine='$dom' and sub='$sub';");
	$db->query("insert into sub_domaines_standby (compte,domaine,sub,valeur,type,action) values ('$cuid','$dom','$sub','$dest','$type',0);"); // INSERT
      } else {
	$err->raise("dom",14);
	return false;
      }
    } else {
      if ($action=="edit") {
	// On vérifie que des modifications ont bien eu lieu :)
	if ($r["type"]==$type && $r["dest"]==$dest) {
	  $err->raise("dom",15);
	  return false;
	}
	// OK, des modifs ont été faites, on valide :
	$db->query("update sub_domaines set type='$type', valeur='$dest' where domaine='$dom' and sub='$sub'");
	$db->query("delete from sub_domaines_standby where domaine='$dom' and sub='$sub'");
	$db->query("insert into sub_domaines_standby (compte,domaine,sub,valeur,type,action) values ('$cuid','$dom','$sub','$dest','$type',1);"); // UPDATE
      } else {
	$err->raise("dom",16);
	return false;
      }
    }
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
  function del_sub_domain($dom,$sub) {
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
    if (!$r=$this->get_sub_domain_all($dom,$sub)) {
      // Le sous-domaine n'existe pas, erreur
      $err->raise("dom",14);
      return false;
    } else {
      // OK, on valide :
      $db->query("delete from sub_domaines where domaine='$dom' and sub='$sub'");
      $db->query("delete from sub_domaines_standby where domaine='$dom' and sub='$sub'");
      $db->query("insert into sub_domaines_standby (compte,domaine,sub,valeur,type,action) values ('$cuid','$dom','$sub','".$r["dest"]."','".$r["type"]."',2);"); // DELETE
    }
    return true;
  } // del_sub_domain

  /* ----------------------------------------------------------------- */
  /**
   * Modifie les information du domaine précisé.
   *
   * @param string $dom Domaine du compte courant que l'on souhaite modifier
   * @param integer $dns Vaut 1 ou 0 pour héberger ou pas le DNS du domaine
   * @param integer $mx Nom fqdn du serveur mx, si le mx local est précisé,
   *  on héberge alors les mails du domaine.
   * @return boolean appelle $mail->add_dom ou $ma->del_dom si besoin, en
   *  fonction du champs MX. Retourne FALSE si une erreur s'est produite,
   *  TRUE sinon.
   *
   */
  function edit_domain($dom,$dns,$mx) {
    global $db,$err,$L_MX,$classes,$cuid;
    $err->log("dom","edit_domain",$dom);
    // Locked ?
    if (!$this->islocked) {
      $err->raise("dom",3);
      return false;
    }
    if ($dns == 1) {
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
    if ($r["dns"]==$dns && $r["mx"]==$mx) {
      $err->raise("dom",15);
      return false;
    }
    // MX ?
    if ($mx==$L_MX)
      $gesmx="1";
    else
      $gesmx="0";
      
    //si gestion mx uniquement, vérification du dns externe
    if ($dns=="0" && $gesmx=="1") {
      $vmx = checkmx($dom,$mx);
      if ($vmx == 1) {
        //aucun champ mx de spécifié sur le dns
      }
  
      if ($vmx == 2) {
        //serveur non spécifié parmi les champx mx
      }
    }
      
    // OK, des modifs ont été faites, on valide :
    // DEPENDANCE :
    if ($gesmx && !$r["mail"]) { // on a associé le MX : on cree donc l'entree dans LDAP
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
    
    $db->query("update domaines set gesdns='$dns', mx='$mx', gesmx='$gesmx' where domaine='$dom'");
    $db->query("insert into domaines_standby (compte,domaine,mx,gesdns,gesmx,action) values ('$cuid','$dom','$mx','$dns','$gesmx',1);"); 
    // UPDATE
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
