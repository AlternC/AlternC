<?php
/*
 $Id: functions.php,v 1.9 2005/12/18 09:50:59 benjamin Exp $
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2002 by the AlternC Development Team.
 http://alternc.org/
 ----------------------------------------------------------------------
 Based on:
 Valentin Lacambre's web hosting softwares: http://altern.org/
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
 Original Author of file: Benjamin Sonntag, Franck Missoum
 Purpose of file: Miscellaneous functions globally used
 ----------------------------------------------------------------------
*/

/* seed the random number generator : */
list($usec, $sec) = explode(' ', microtime());
mt_srand((float) $sec + ((float) $usec * 100000));

/* Format a field value for input or textarea : */
function fl($str) { return str_replace("<","&lt;",str_replace("\"","&quot;",$str)); }

/*
 Check if a domain can be hosted on this server :
 Return a negative value in case of an error,
 or a string for the index in $tld
*/
function checkhostallow($domain,$dns) {
  global $L_NS1,$L_NS2,$db;
  $sizefound=0;
  $found="";
  $db->query("SELECT tld,mode FROM tld;");
  while ($db->next_record()) {
    list($key,$val)=$db->Record;
    if (substr($domain,-1-strlen($key))==".".$key) {
      if ($sizefound<strlen($key)) {
	$sizefound=strlen($key);
	$found=$key;
	$fmode=$val;
      }
    }
  }

  if (!$found || $fmode==0)			// TLD not allowed at all
    return -1;
  if (($fmode!=4) && (!is_array($dns)))	// NO dns found in the whois, and domain MUST exists
    return -2;
  if ($fmode>2)		// OK, in the case 3 4 5
    return $found;
  $n1=false;	$n2=false;
  for ($i=0;$i<count($dns);$i++) {
    if ($dns[$i]==$L_NS1) $n1=true;
    if ($dns[$i]==$L_NS2) $n2=true;
  }
  if ($fmode==1 && $n1)		// OK
    return $found;
  if ($fmode==2 && $n1 && $n2)		// OK
    return $found;
  return -3;	// DNS incorrect in the whois
}

/* Check that a domain can be hosted in that server, 
without DNS managment. 
*/
function checkhostallow_nodns($domain) {
  global $db;
  $sizefound=0;
  $found="";
  $db->query("SELECT tld,mode FROM tld;");
  while ($db->next_record()) {
    list($key,$val)=$db->Record;
    if (substr($domain,-1-strlen($key))==".".$key) {
      if ($sizefound<strlen($key)) {
	$sizefound=strlen($key);
	$found=$key;
	$fmode=$val;
      }
    }
  }
  // If we found a correct tld, let's find how many . before ;)
  if (!$found || $fmode==0)                       // TLD not allowed at all
    return 1;
  if (count(explode(".",substr($domain,0,-$sizefound)))>2) {
    return 1;
  }
  return 0;
}

/* Check that $url is a correct url (http:// or https:// or ftp://)  */
function checkurl($url) {
  // TODO : add a path/file check
  if (substr($url,0,7)!="http://" && substr($url,0,8)!="https://" && substr($url,0,6)!="ftp://") return false;
  if (substr($url,0,7)=="http://" ) $fq=substr($url,7);
  if (substr($url,0,8)=="https://") $fq=substr($url,8);
  if (substr($url,0,6)=="ftp://"  ) $fq=substr($url,6);
  $f=explode("/",$fq);
  if (!is_array($f)) $f=array($f);
  $t=checkfqdn($f[0]);
  if ($t) return false;
  return true;
}

/* Check that $ip is a correct 4 Dotted ip */
function checkip($ip) {
  // return true or false whether the ip is correctly formatted
  if (!ereg("[0-9]*\.[0-9]*\.[0-9]*\.[0-9]*",$ip)) return false;
  $l=explode(".",$ip);
  if ($l[0]>255 || $l[1]>255 || $l[2]>255 || $l[3]>255) return false;
  return true;
}

/* Check a login mail */
function checkloginmail($mail) {
  if (!preg_match("/^[a-zA-Z0-9_\.:\+\-]+$/",$mail)) {
    return false;
  } else {
    return true;
  }
}
/* " */

/* Check an email address, use checkloginmail and checkfqdn */
function checkmail($mail) {
  // Retourne 0 si tout va bien, sinon retourne un code erreur...
  // 1 s'il n'y a rien devant l'@
  // 2 3 ou 4 si le domaine est incorrect.
  // 5 s'il y a caractères interdits dans la partie gauche du @
  // 6 si le mail contient aucun ou plus d'un @
  $t=explode("@",$mail);
  if (count($t)!=2) {
    return 6;
  }
  $c=checkfqdn($t[1]);
  if ($c)
    return $c;
  // Verification de la partie gauche :
  if (!checkloginmail($t[0])) {
    if ($t[0]=="") {
    	return 1;
    } else {
        return 5;
    }
  }
  return 0;
}

/* Check that a domain name is fqdn compliant */
function checkfqdn($fqdn) {
  // (RFC 1035 http://www.ietf.org/rfc/rfc1035.txt)
  // Retourne 0 si tout va bien, sinon, retourne un code erreur...
  // 1. Nom de domaine complet trop long.
  // 2. L'un des membres est trop long.
  // 3. Caract?re interdit dans l'un des membres.
  if (strlen($fqdn)>255)
    return 1;
  $members=explode(".", $fqdn);
  if (count($members)>1) {
    reset($members);
    while (list ($key, $val) = each ($members)) {
      if (strlen($val)>63)
	return 2;
      if (!eregi("^[a-z0-9][a-z0-9-]*[a-z0-9]$",$val)) {
	/*"*/                  return 3;
      }
    }
  } else {
    return 4;
  }
  return 0;
}

function checkuserpath($path) {
  /*
    return 0 if the path is not in the user's space
    return 1 if this is a directory
    return 2 if this is a regular file
  */
  global $mem;
  $user=$mem->user["login"];
  $usar=substr($user,0,1);
  if (substr($path,0,1)=="/")
    $path="/".$path;
  if (is_dir("/var/alternc/html/$usar/$user$path")) {
    return 1;
  }
  if (is_file("/var/alternc/html/$usar/$user$path")) {
    return 2;
  }
  return 0;
}


function cbox($test) {
  if ($test) echo (" checked=\"checked\"");
}

function ecif($test,$tr,$fa="") {
  if ($test)
    echo $tr;
  else
    echo $fa;
}

function __($str) {
  echo _($str);
}

function ife($test,$tr,$fa="") {
  if ($test)
    return $tr;
  else
    return $fa;
}

function format_size($size) {
  // Retourne une taille formattée en Octets, Kilo-octets, Méga-octets ou Giga-Octets, avec 2 décimales.
  if ("-" == $size) {
    return $size;
  }
  $size=(float)$size;
  if ($size<1024) {
    $r=$size;
    if ($size!=1) {
      $r.=" "._("Bytes");
    } else {
      $r.=" "._("Byte");
    }
  } else {
    $size=$size/1024;
    if ($size<1024) {
      $r=round($size,2)." "._("Kb");
    } else {
      $size=$size/1024;
      if ($size<1024) {
	$r=round($size,2)." "._("Mb");
      } else {
	$size=$size/1024;
	if ($size<1024) {
	  $r=round($size,2)." "._("Gb");
	} else {
	  $r=round($size/1024,2)." "._("Tb");
	}
      }
    }
  }
  return $r;
}

function getlinkhelp($hid) {
  return "(<a href=\"javascript:help($hid);\">?</a>)";
}
function linkhelp($hid) {
  echo getlinkhelp($hid);
}

function format_date($format,$date) {
  $d=substr($date,8,2);
  $m=substr($date,5,2);
  $y=substr($date,0,4);
  $h=substr($date,11,2);
  $i=substr($date,14,2);
  if ($h>12) {
    $hh=$h-12;
    $am="pm";
  } else {
    $hh=$h;
    $am="am";
  }
  return sprintf(_($format),$d,$m,$y,$h,$i,$hh,$am);
}

/* Strip slashes if needed : */
function ssla($str) {
  if (get_magic_quotes_gpc()) {
    return stripslashes($str);
  } else {
    return $str;
  }
}

  /* ----------------------------------------------------------------- */
  /** Hashe un mot de passe en clair en MD5 avec un salt aléatoire
   * @param string $pass Mot de passe à crypter (max 32 caractères)
   * @return string Retourne le mot de passe crypté
   * @access private
   */
  function _md5cr($pass,$salt="") {
    if (!$salt) {
      $chars="./0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
      for ($i=0;$i<12;$i++) {
	$salt.=substr($chars,(mt_rand(0,strlen($chars))),1);
      }
      $salt="$1$".$salt;
    }
    return crypt($pass,$salt);
  }

/** split mysql database name between username and custom database name
 * @param string $dbname database name
 * @return array returns username as first element, custom name as second
 */
function split_mysql_database_name($dbname) {
    $db_exploded_name = explode("_",$dbname);
    return array($db_exploded_name[0],
                 implode("_", array_slice($db_exploded_name, 1)));
}


/* ----------------------------------------------------------------- */
/** Echappe les caractères pouvant perturber un flux XML standard : 
 * @param string $string Chaine de caractère à encoder en valeur xml.
 * @return string Retourne la chaîne modifiée si besoin.
 * @access private
 */
function xml_entities($string) {
  return str_replace("<","&lt;",str_replace(">","&gt;",str_replace("&","&amp;",$string)));
}

/* ----------------------------------------------------------------- */
/** Converti un nombre de mois en une chaine plus lisible
 * @param  number $months Nombre de mois
 * @return string Chaîne représentant le nombre de mois
 * @access private
 */
function pretty_months($months) {
  if( $months % 12 == 0 && $months > 11) {
    $years = $months / 12;
    return "$years " . ($years > 1 ? _("years") : _("year"));
  } else {
    return "$months " . ($months > 1 ? _("months") : _("month"));
  }
}

/* ----------------------------------------------------------------- */
/** Fabrique un drop-down pour les durées de comptes
 * @name string $name Nom pour le composasnt
 * @selected number Option selectionée du composant
 * @return string Code html pour le drop-down
 * @access private
 */
function duration_list($name, $selected=0) {
  $res = "<select name=\"$name\" id=\"$name\">";

  foreach(array(0, 1, 2, 3, 4, 6, 12, 24) as $dur) {
    $res .= "<option value=\"$dur\"";
    if($selected == $dur) {
      $res .= ' selected';
    }

    $res .= '>';

    if($dur == 0) {
      $res .= _('Not managed');
    } else {
      $res .= pretty_months($dur);
    }
    $res .= '</option>';
  }

  $res .= '</select>';
  return $res;
}

/* ----------------------------------------------------------------- */
/** Remet un chemin en position locale
 * retire ../ ou / au besoin
 * @file string chemin du repertoire ou du fichier
 * @return string chemin du repertoir en position local
 * @access private
 */
 function dir_local($file) {
    //recherche la chaine commençant aprés ../ ou /  ceci n fois
    preg_match('`^(/|../)*(.*)`',$file,$res);
    echo $file."<br/>"; 
    print_r($res);
    echo "<br/>";
    if ($res) {
      return $res[2];
    } else {
      return $file;
    } 
 }


?>
