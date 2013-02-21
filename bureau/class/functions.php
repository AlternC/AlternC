<?php
/*
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2000-2012 by the AlternC Development Team.
 https://alternc.org/
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
 Purpose of file: Miscellaneous functions globally used
 ----------------------------------------------------------------------
*/

/* seed the random number generator : */
list($usec, $sec) = explode(' ', microtime());
mt_srand((float) $sec + ((float) $usec * 100000));

/* Format a field value for input or textarea : */
function fl($str) { return str_replace("<","&lt;",str_replace("\"","&quot;",$str)); }

/* Used by class/m_log.php for usort */
function compare_logname($a, $b) {
  return strcmp($a['name'],$b['name']);
}

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
  $tld_no_check_at_all = variable_get('tld_no_check_at_all', 0,'Set to 1 to disable ALL check on the TLD (users will be able to add any domain)');
  if ( $tld_no_check_at_all )
    return 0; // OK , the boss say that you can.

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

function get_remote_ip() {
  // Return the remote IP.
  // If you are behind a proxy, use X_FORWARDED_FOR instead of REMOTE_ADDR
  return getenv('REMOTE_ADDR');
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

/* Check that TXT domain is correct */
function checksubtxt($txt) {
	return true;
}
/* Check that CNAME domain is correct */
function checkcname($cname) {
	return true;
}

/* Check that $ip is a correct 4 Dotted ip */
function checkip($ip) {
  // return true or false whether the ip is correctly formatted
  return filter_var($ip,FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
}

/* Check that $ip is a correct ipv6 ip */
function checkipv6($ip) {
  // return true or false whether the ip is correctly formatted
  return filter_var($ip,FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
}

/* Check a login mail, cf http://www.bortzmeyer.org/arreter-d-interdire-des-adresses-legales.html */
/* FIXME: check who is using that function and delete it when unused */
function checkloginmail($mail) {
  return true;
}

/* Check an email address, use filter_var with emails, which works great ;)  */
/* FIXME: check who is using that function and delete it when unused */
function checkmail($mail) {
  if (filter_var($mail,FILTER_VALIDATE_EMAIL)) {
    return 0;
  } else {
    return 1;
  }
}

/* Check that a domain name is fqdn compliant */
function checkfqdn($fqdn) {
  // (RFC 1035 http://www.ietf.org/rfc/rfc1035.txt)
  // Retourne 0 si tout va bien, sinon, retourne un code erreur...
  // 1. Nom de domaine complet trop long.
  // 2. L'un des membres est trop long.
  // 3. Caractere interdit dans l'un des membres.
  // 4. Le fqdn ne fait qu'un seul membre (il n'est donc pas fq...)
  if (strlen($fqdn)>255)
    return 1;
  $members=explode(".", $fqdn);
  if (count($members)>1) $ret=0; else $ret=4;
  reset($members);
  while (list ($key, $val) = each ($members)) {
    if (strlen($val)>63)
      return 2;

    // Note: a.foo.net is a valid domain
    // Note: RFC1035 tells us that a domain should not start by a digit, but every registrar allows such a domain to be created ... too bad.
    if (!preg_match("#^[a-z0-9_]([a-z0-9-_]*[a-z0-9_])?$#i",$val)) {
      return 3;
    }
  }
  return $ret;
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
  if (substr($path,0,1)!="/")
    $path="/".$path;

  $rpath = realpath(ALTERNC_HTML."/$usar/$user$path");
  if (!$rpath) { // if file or directory does not exist
    return 1; // FIXME is it safe to say OK in this case ?
  }
  $userpath = getuserpath();
  if(strpos($rpath,$userpath) === 0){
    if (is_dir(ALTERNC_HTML."/$usar/$user$path")) {
        return 1;
    }
    if (is_file(ALTERNC_HTML."/$usar/$user$path")) {
      return 2;
    }
  }
  return 0;
}

/**
 * get the home of the user
 *
 * @args string $user the username, if null will use the global $mem. no
 * security checks performed on path
 * @returns string the actual absolute path
 */
function getuserpath($user = null) {
  if (is_null($user)) {
    global $mem;
    $user = $mem->user['login'];
  }
  return ALTERNC_HTML."/".substr($user,0,1)."/".$user;
}

/* ECHOes checked="checked" only if the parameter is true
 * useful for checkboxes and radio buttons
 */
function cbox($test) {
  if ($test) echo (" checked=\"checked\"");
}


/* ECHOes selected="selected" only if the parameter is true
 * useful for checkboxes and radio buttons
 */
function selected($bool) {
  if ($bool) {
    echo " selected=\"selected\"";
  }
}

function ecif($test,$tr,$fa="",$affiche=1) {
  if ($test)
    $retour = $tr;
  else
    $retour = $fa;
    
  if ($affiche) 
    echo $retour;
  else
    return $retour;
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

function format_size($size,$html=0) {
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
  if ($html) {
    return str_replace(" ","&nbsp;",$r);
  }else{
    return $r;
  }
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
  return sprintf($format,$d,$m,$y,$h,$i,$hh,$am);
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
  $res = "<select name=\"$name\" id=\"$name\" class=\"inl\">";

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

/* select_values($arr,$cur) echo des <option> du tableau $values ou de la table sql $values
   selectionne $current par defaut. 
   Si on lui demande poliement, il prend un tableau a une dimension
*/
function eoption($values,$cur,$onedim=false) {
  if (is_array($values)) {
    foreach ($values as $k=>$v) {
      if ( $onedim ) $k=$v;
      echo "<option value=\"$k\"";
      if ($k==$cur) echo " selected=\"selected\"";
      echo ">".$v."</option>";
    }
  }
}


/* Echo the HTMLSpecialChars version of a value. 
 * Must be called when pre-filling fields values in forms such as : 
 * <input type="text" name="toto" value="<?php ehe($toto); ?>" />
 * Use the charset of the current language for transcription
 */
function ehe($str,$affiche=1) {
  global $charset;
  $retour = htmlspecialchars($str,ENT_QUOTES,$charset); 
  if ($affiche) {
    echo $retour;
  } else {
    return $retour;
  }
}

/* Get the Fields of the posted form from $_REQUEST or POST or GET
 * and check their type
 */
function getFields($fields, $requestOnly = false) {
  $vars = array();
  $methodType = array ("get", "post", "request", "files", "server");
  
  foreach ($fields AS $name => $options) {
    if (in_array(strtolower($options[0]), $methodType) === false)
      die ("Unrecognized method type used for field " . $name . " : " . $options[0]);
    
    if ($requestOnly === true)
      $method = "_REQUEST";
    else
      $method = "_" . strtoupper($options[0]);
    
    switch ($options[1]) {
    case "integer":
      $vars[$name] = (isset($GLOBALS[$method][$name]) && is_numeric($GLOBALS[$method][$name]) ? intval($GLOBALS[$method][$name]) : $options[2]);
      break;
    case "float":
      $vars[$name] = (isset($GLOBALS[$method][$name]) && is_numeric($GLOBALS[$method][$name]) ? floatval($GLOBALS[$method][$name]) : $options[2]);
      break;
    case "string":
      $vars[$name] = (isset($GLOBALS[$method][$name]) ? trim($GLOBALS[$method][$name]) : $options[2]);
      break;
    case "array":
      $vars[$name] = (isset($GLOBALS[$method][$name]) && is_array($GLOBALS[$method][$name]) ? $GLOBALS[$method][$name] : $options[2]);
      break;
    case "boolean":
      $vars[$name] = (isset($GLOBALS[$method][$name]) ? $GLOBALS[$method][$name] : $options[2]);
      break;
    case "file":
      $vars[$name] = (isset($GLOBALS[$method][$name]) ? $GLOBALS[$method][$name] : $options[2]);
      break;
    default:
      die ("Illegal method type used for field " . $name . " : " . $options[1]);
    }
  }
  
  // Insert into $GLOBALS.
  foreach ($vars AS $var => $value)
    $GLOBALS[$var] = $value;
  
  return $vars;
}

function printVar($array) {
  echo "<pre style=\"border: 1px solid black; text-align: left; font-size: 9px\">\n";
  print_r($array);
  echo "</pre>\n";
}
function list_properties_order($a, $b) {
  if ( $a['label'] == $b['label']) {
    return 0;
  }
  return ($a['label']<$b['label'])?-1:1;
} // end private function list_properties_order


/** Show a pager as 
  Previous page 0 1 2 ... 16 17 18 19 20 ... 35 36 37 Next page
  Arguments are as follow : 
  $offset = the current offset from 0 
  $count = The number of elements shown per page 
  $total = The total number of elements 
  $url = The url to show for each page. %%offset%% will be replace by the proper offset
  $before & $after are HTML code to show before and after the pager **only if the pager is to be shown**
  */
function pager($offset,$count,$total,$url,$before="",$after="") {
  $offset=intval($offset); 
  $count=intval($count); 
  $total=intval($total); 
  if ($offset<=0) $offset="0";
  if ($count<=1) $count="1";
  if ($total<=0) $total="0";
  if ($total<$offset) $offset=max(0,$total-$count);

  if ($total<=$count) { // When there is less element than 1 complete page, just don't do anything :-D
    return true;
  }
  echo $before;
  // Shall-we show previous page link ?
  if ($offset) {
    $o=max($offset-$count,0);
    echo "<a href=\"".str_replace("%%offset%%",$o,$url)."\" alt=\"(Ctl/Alt-p)\" title=\"(Alt-p)\" accesskey=\"p\">"._("Previous Page")."</a> ";
  } else {
    echo _("Previous Page")." ";
  }

  if ($total>(2*$count)) { // On n'affiche le pager central (0 1 2 ...) s'il y a au moins 2 pages.
    echo " - ";
    if (($total<($count*10)) && ($total>$count)) {  // moins de 10 pages : 
      for($i=0;$i<$total/$count;$i++) {
	$o=$i*$count;
	if ($offset==$o) {
	  echo $i." "; 
	} else {
	  echo "<a href=\"".str_replace("%%offset%%",$o,$url)."\">$i</a> ";
	}
      }
    } else { // Plus de 10 pages, on affiche 0 1 2 , 2 avant et 2 après la page courante, et les 3 dernieres
      for($i=0;$i<=2;$i++) {
	$o=$i*$count;
	if ($offset==$o) {
	  echo $i." "; 
	} else {
	  echo "<a href=\"".str_replace("%%offset%%",$o,$url)."\">$i</a> ";
	}
      }
      if ($offset>=$count && $offset<($total-2*$count)) { // On est entre les milieux ...
	// On affiche 2 avant jusque 2 après l'offset courant mais sans déborder sur les indices affichés autour
	$start=max(3,intval($offset/$count)-2);
	$end=min(intval($offset/$count)+3,intval($total/$count)-3);
	if ($start!=3) echo " ... ";
	for($i=$start;$i<$end;$i++) {
	  $o=$i*$count;
	  if ($offset==$o) {
	    echo $i." "; 
	  } else {
	    echo "<a href=\"".str_replace("%%offset%%",$o,$url)."\">$i</a> ";
	  }
	}
	if ($end!=intval($total/$count)-3) echo " ... ";
      } else {
	echo " ... ";
      }
      for($i=intval($total/$count)-3;$i<$total/$count;$i++) {
	$o=$i*$count;
	if ($offset==$o) {
	  echo $i." "; 
	} else {
	  echo "<a href=\"".str_replace("%%offset%%",$o,$url)."\">$i</a> ";
	}
      }
    echo " - ";
    } // More than 10 pages?
  }
  // Shall-we show the next page link ?
  if ($offset+$count<$total) {
    $o=$offset+$count;
    echo "<a href=\"".str_replace("%%offset%%",$o,$url)."\" alt=\"(Ctl/Alt-s)\" title=\"(Alt-s)\" accesskey=\"s\">"._("Next Page")."</a> ";
  } else {
    echo _("Next Page")." ";
  }
  echo $after;
}

function create_pass($length = 8){
  $chars = "1234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
  $i = 0;
  $password = "";
  while ($i <= $length) {
    $password .= @$chars{mt_rand(0,strlen($chars))};
    $i++;
  }
  return $password;
}

define("DEFAULT_PASS_SIZE", 8);

/* Affiche un bouton qui permet de generer automatiquement des mots de passes */
function display_div_generate_password($pass_size=DEFAULT_PASS_SIZE, $fields_to_fill1="", $fields_to_fill2="") {
  $id=rand(1,1000);
  echo "<div id='$id' style='display:none;'><a href=\"javascript:generate_password_html('$id',$pass_size,'$fields_to_fill1','$fields_to_fill2');\">";
  __("Clic here to generate a password");
  echo "</a></div>";
  echo "<script type='text/javascript'>$('#$id').show();</script>";
  return 0;
}

/* Affiche un bouton pour selectionner un dossier sur le serveur */
function display_browser($dir="", $caller="main.dir", $width=350, $height=450) {
  // Browser id
  $bid="b".rand(1,1000);
  echo "<script type=\"text/javascript\">
        <!--
          $(function() {
              $( \"#".$bid."\" ).dialog({
              autoOpen: false,
              width: ".$width.",
              height: ".$height.",
              modal: true,
              open: function()
                {
                    $('.ui-widget-overlay').css('opacity', .70);
                    $('.ui-dialog-content').css('background-color', '#F0F0FA');
                },
            });
         
            $( \"#bt".$bid."\" )
              .button()
              .attr(\"class\", \"ina\")
              .click(function() {
                $( \"#".$bid."\" ).dialog( \"open\" );
                return false;
              });
          });
          
          
          document.write('&nbsp;<input type=\"button\" id=\"bt".$bid."\" value=\""._("Choose a folder...")."\" class=\"ina\">');
          document.write('<div id=\"".$bid."\" title=\""._("Choose a folder...")."\" style=\"display: none; bgcolor:red;\">');
          document.write('  <iframe src=\"/browseforfolder2.php?caller=".$caller."&file=".ehe($dir, 0)."&bid=".$bid."\" width=\"".($width-25)."\" height=\"".($height-50)."\" frameborder=\"no\" id=\"browseiframe\"></iframe>');
          document.write('</div>');
        //  -->
        </script>
        ";
  
}

// Insere un $wrap_string tous les $max caracteres dans $message
function auto_wrap($message="",$max=10,$wrap_string="<wbr/>") {
  $cpt = 0;
  $mot = split(" ",$message);
  while (isset($mot[$cpt]) && ($mot[$cpt] != "")){
    if(@strlen($mot[$cpt]) > $max){
      $nvmot = chunk_split ($mot[$cpt], $max, $wrap_string );
      $message = str_replace($mot[$cpt], $nvmot, $message);
    }
    $cpt++;
  }
  return $message;
}

?>
