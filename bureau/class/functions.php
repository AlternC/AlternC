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

/**
 * Format a field value for input or textarea : 
 * 
 * @param string $str
 * @return string
 */
function fl($str) {
    return str_replace("<", "&lt;", str_replace("\"", "&quot;", $str));
}

/**
 *  Check if a domain can be hosted on this server :
 * Return a negative value in case of an error,
 * or a string for the index in $tld
 * 
 * @global string $L_NS1
 * @global string $L_NS2
 * @global m_mysql $db
 * @global m_dom $dom
 * @param string $domain
 * @param array $dns
 * @return int
 */
function checkhostallow($domain, $dns) {
    global $L_NS1, $L_NS2, $db, $dom;
    $sizefound = 0;
    $found = "";
    $db->query("SELECT tld,mode FROM tld;");
    while ($db->next_record()) {
        list($key, $val) = $db->Record;
        if (substr($domain, -1 - strlen($key)) == "." . $key) {
            if ($sizefound < strlen($key)) {
                $sizefound = strlen($key);
                $found = $key;
                $fmode = $val;
            }
        }
    }
    if ($dom->tld_no_check_at_all) {
        return 0; // OK , the boss say that you can.
    }
    if (!$found || $fmode == 0) {   // TLD not allowed at all
        return -1;
    }
    if (($fmode != 4) && (!is_array($dns))) { // NO dns found in the whois, and domain MUST exists
        return -2;
    }
    if ($fmode > 2) { // OK, in the case 3 4 5
        return $found;
    }
    $n1 = false;
    $n2 = false;
    for ($i = 0; $i < count($dns); $i++) {
        if (strtolower($dns[$i]) == strtolower($L_NS1)) {
            $n1 = true;
        }
        if (strtolower($dns[$i]) == strtolower($L_NS2)) {
            $n2 = true;
        }
    }
    if ($fmode == 1 && $n1) {
        return $found;
    }
    if ($fmode == 2 && $n1 && $n2) {
        return $found;
    }
    return -3; // DNS incorrect in the whois
}

/**
 * Check that a domain can be hosted in that server, 
 *   without DNS managment. 
 * @global m_mysql $db
 * @param string $domain
 * @return int
 */
function checkhostallow_nodns($domain) {
    global $db;
    $sizefound = 0;
    $found = "";
    $db->query("SELECT tld,mode FROM tld;");
    while ($db->next_record()) {
        list($key, $val) = $db->Record;
        if (substr($domain, -1 - strlen($key)) == "." . $key) {
            if ($sizefound < strlen($key)) {
                $sizefound = strlen($key);
                $found = $key;
                $fmode = $val;
            }
        }
    }
    // If we found a correct tld, let's find how many . before ;)
    if (!$found || $fmode == 0) {                      // TLD not allowed at all
        return 1;
    }
    if (count(explode(".", substr($domain, 0, -$sizefound))) > 2) {
        return 1;
    }
    return 0;
}

/**
 * Return the remote IP.
 * If you are behind a proxy, use X_FORWARDED_FOR instead of REMOTE_ADDR
 * @return string
 */
function get_remote_ip() {
    return getenv('REMOTE_ADDR');
}

/**
 * Check that $url is a correct url (http:// or https:// or ftp://) 
 * 
 * @param type $url
 * @return boolean
 */
function checkurl($url) {
    // TODO : add a path/file check
    if (substr($url, 0, 7) != "http://" && substr($url, 0, 8) != "https://" && substr($url, 0, 6) != "ftp://") {
        return false;
    }
    if (substr($url, 0, 7) == "http://") {
        $fq = substr($url, 7);
    }
    if (substr($url, 0, 8) == "https://") {
        $fq = substr($url, 8);
    }
    if (substr($url, 0, 6) == "ftp://") {
        $fq = substr($url, 6);
    }
    $f = explode("/", $fq);
    if (!is_array($f)) {
        $f = array($f);
    }
    $t = checkfqdn($f[0]);
    return !$t;
}

/**
 * Check that TXT domain is correct 
 * 
 * @param string $txt
 * @return boolean
 */
function checksubtxt($txt) {
    return true;
}

/**
 * Check that CNAME domain is correct 
 * @param string $cname
 * @return boolean
 */
function checkcname($cname) {
    if (($check = checkfqdn(rtrim($cname, ".")))) {
        if ($check != 4) { // ALLOW non-fully qualified (no .)
            return false; // bad FQDN
        }
    }
    if (substr($cname, -1) != ".") {
        // Not fully qualified : 
        if (strpos($cname, ".") === false) {
            // NO DOT in the middle, no DOT elsewhere => seems fine
            return true;
        } else {
            // NO DOT at the end, but A DOT ELSEWHERE => seems broken (please use fully qualified)
            return false;
        }
    }
    // fully qualified => fine
    return true;
}

/**
 * Check that $ip is a correct 4 Dotted ip
 * @param string $ip
 * @return type
 */
function checkip($ip) {
    // return true or false whether the ip is correctly formatted
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
}

/**
 * Check that $ip is a correct ipv6 ip 
 * @param string $ip
 * @return type
 */
function checkipv6($ip) {
    // return true or false whether the ip is correctly formatted
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
}

/**
 * Check a login mail, cf http://www.bortzmeyer.org/arreter-d-interdire-des-adresses-legales.html 
 * @todo Check who is using that function and delete it when unused 
 * @param string $mail
 * @return boolean
 */
function checkloginmail($mail) {
    return true;
}

/**
 * Check an email address, use filter_var with emails, which works great ;) 
 * @todo  check who is using that function and delete it when unused 
 * @param string $mail
 * @return boolean
 */
function checkmail($mail) {
    if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        return FALSE;
    } else {
        return TRUE;
    }
}

/**
 * Check that a domain name is fqdn compliant 
 * @param string $fqdn
 * @return int
 */
function checkfqdn($fqdn) {
    // (RFC 1035 http://www.ietf.org/rfc/rfc1035.txt)
    // Retourne 0 si tout va bien, sinon, retourne un code erreur...
    // 1. Nom de domaine complet trop long.
    // 2. L'un des membres est trop long.
    // 3. Caractere interdit dans l'un des membres.
    // 4. Le fqdn ne fait qu'un seul membre (il n'est donc pas fq...)
    if (strlen($fqdn) > 255) {
        return 1;
    }
    $members = explode(".", $fqdn);
    if (count($members) > 1) {
        $ret = 0;
    } else {
        $ret = 4;
    }
    reset($members);
    while (list ($key, $val) = each($members)) {
        if (strlen($val) > 63) {
            return 2;
        }
        // Note: a.foo.net is a valid domain
        // Note: RFC1035 tells us that a domain should not start by a digit, but every registrar allows such a domain to be created ... too bad.
        if (!preg_match("#^[a-z0-9_]([a-z0-9-]*[a-z0-9])?$#i", $val)) {
            return 3;
        }
    }
    return $ret;
}

/**
 * @global m_mem $mem
 * @param string $path
 * @return int 
 * return 0 if the path is not in the user's space
 * return 1 if this is a directory
 * return 2 if this is a regular file
 */
function checkuserpath($path) {
    global $mem;
    $user = $mem->user["login"];
    $usar = substr($user, 0, 1);
    if (substr($path, 0, 1) != "/") {
        $path = "/" . $path;
    }
    $rpath = realpath(ALTERNC_HTML . "/$usar/$user$path");
    if (!$rpath) { // if file or directory does not exist
        return 1; // FIXME is it safe to say OK in this case ?
    }
    $userpath = getuserpath();
    if (strpos($rpath, $userpath) === 0) {
        if (is_dir(ALTERNC_HTML . "/$usar/$user$path")) {
            return 1;
        }
        if (is_file(ALTERNC_HTML . "/$usar/$user$path")) {
            return 2;
        }
    }
    return 0;
}

/**
 * get the home of the user
 *
 * @global m_mem $mem
 * @args string $user the username, if null will use the global $mem. no
 * security checks performed on path
 * @return string the actual absolute path
 */
function getuserpath($user = null) {
    if (is_null($user)) {
        global $mem;
        $user = $mem->user['login'];
    }
    return rtrim(ALTERNC_HTML, "/") . "/" . substr($user, 0, 1) . "/" . $user;
}

/**
 * ECHOes checked="checked" only if the parameter is true
 * useful for checkboxes and radio buttons
 * 
 * @param boolean $test
 * @param boolean $echo
 */
function cbox($test, $echo = true) {
    if ($test) {
        $return = " checked=\"checked\"";
    } else {
        $return = '';
    }
    if ($echo) {
        echo $return;
    }
    return $return;
}

/**
 * ECHOes selected="selected" only if the parameter is true
 * useful for checkboxes and radio buttons
 * 
 * @param boolean $bool
 * @param boolean $echo
 * @return string
 */
function selected($bool, $echo = TRUE) {
    if ($bool) {
        $return = " selected=\"selected\"";
    } else {
        $return = '';
    }
    if ($echo) {
        echo $return;
    }
    return $return;
}

/**
 * 
 * @param boolean $test
 * @param string $tr
 * @param string $fa
 * @param integer $affiche
 * @return string
 */
function ecif($test, $tr, $fa = "", $affiche = 1) {
    if ($test) {
        $retour = $tr;
    } else {
        $retour = $fa;
    }
    if ($affiche) {
        echo $retour;
    }
    return $retour;
}

/**
 * 
 * @param string $str
 */
function __($str) {
    echo _($str);
}

/**
 * 
 * @param boolean $test
 * @param string $tr
 * @param string $fa
 * @return string
 */
function ife($test, $tr, $fa = "") {
    if ($test) {
        return $tr;
    }
    return $fa;
}

/**
 * 
 * @param int|string $size
 * @param integer $html
 * @return string
 */
function format_size($size, $html = 0) {
    // Retourne une taille formatt�e en Octets, Kilo-octets, M�ga-octets ou Giga-Octets, avec 2 d�cimales.
    if ("-" == $size) {
        return $size;
    }
    $size = (float) $size;
    if ($size < 1024) {
        $r = $size;
        if ($size != 1) {
            $r.=" " . _("Bytes");
        } else {
            $r.=" " . _("Byte");
        }
    } else {
        $size = $size / 1024;
        if ($size < 1024) {
            $r = round($size, 2) . " " . _("Kb");
        } else {
            $size = $size / 1024;
            if ($size < 1024) {
                $r = round($size, 2) . " " . _("Mb");
            } else {
                $size = $size / 1024;
                if ($size < 1024) {
                    $r = round($size, 2) . " " . _("Gb");
                } else {
                    $r = round($size / 1024, 2) . " " . _("Tb");
                }
            }
        }
    }
    if ($html) {
        return str_replace(" ", "&nbsp;", $r);
    } else {
        return $r;
    }
}

/**
 * 
 * @param int $hid
 * @return string
 */
function getlinkhelp($hid) {
    return "(<a href=\"javascript:help($hid);\">?</a>)";
}

/**
 * 
 * @param int $hid
 */
function linkhelp($hid) {
    echo getlinkhelp($hid);
}

/**
 * 
 * @param string $format
 * @param string $date
 * @return string
 */
function format_date($format, $date) {
    $d = substr($date, 8, 2);
    $m = substr($date, 5, 2);
    $y = substr($date, 0, 4);
    $h = substr($date, 11, 2);
    $i = substr($date, 14, 2);
    if ($h > 12) {
        $hh = $h - 12;
        $am = "pm";
    } else {
        $hh = $h;
        $am = "am";
    }

    // we want every number to be treated as a string.
    $format=str_replace('$d', '$s', $format);
    return sprintf($format, $d, $m, $y, $h, $i, $hh, $am);
}

/**
 * Strip slashes if needed : 
 * @param string $str
 * @return string
 */
function ssla($str) {
    if (get_magic_quotes_gpc()) {
        return stripslashes($str);
    } else {
        return $str;
    }
}

/* ----------------------------------------------------------------- */

/** Hashe un mot de passe en clair en MD5 avec un salt al�atoire
 * @param string $pass Mot de passe � crypter (max 32 caract�res)
 * @return string Retourne le mot de passe crypt�
 * @access private
 */
function _md5cr($pass, $salt = "") {
    if (!$salt) {
        $chars = "./0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        for ($i = 0; $i < 12; $i++) {
            $salt.=substr($chars, (mt_rand(0, strlen($chars))), 1);
        }
        $salt = "$1$" . $salt;
    }
    return crypt($pass, $salt);
}

/** split mysql database name between username and custom database name
 * @param string $dbname database name
 * @return array returns username as first element, custom name as second
 */
function split_mysql_database_name($dbname) {
    $db_exploded_name = explode("_", $dbname);
    return array($db_exploded_name[0],
        implode("_", array_slice($db_exploded_name, 1)));
}

/* ----------------------------------------------------------------- */

/** Echappe les caract�res pouvant perturber un flux XML standard : 
 * @param string $string Chaine de caract�re � encoder en valeur xml.
 * @return string Retourne la cha�ne modifi�e si besoin.
 * @access private
 */
function xml_entities($string) {
    return str_replace("<", "&lt;", str_replace(">", "&gt;", str_replace("&", "&amp;", $string)));
}

/* ----------------------------------------------------------------- */

/** Converti un nombre de mois en une chaine plus lisible
 * @param  integer $months Nombre de mois
 * @return string Cha�ne repr�sentant le nombre de mois
 * @access private
 */
function pretty_months($months) {
    if ($months % 12 == 0 && $months > 11) {
        $years = $months / 12;
        return "$years " . ($years > 1 ? _("years") : _("year"));
    } else {
        return "$months " . ($months > 1 ? _("months") : _("month"));
    }
}

/* ----------------------------------------------------------------- */

/** Fabrique un drop-down pour les dur�es de comptes
 * @name string $name Nom pour le composasnt
 * @selected number Option selection�e du composant
 * @return string Code html pour le drop-down
 * @access private
 */
function duration_list($name, $selected = 0) {
    $res = "<select name=\"$name\" id=\"$name\" class=\"inl\">";

    foreach (array(0, 1, 2, 3, 4, 6, 12, 24) as $dur) {
        $res .= "<option value=\"$dur\"";
        if ($selected == $dur) {
            $res .= ' selected="selected" ';
        }

        $res .= '>';

        if ($dur == 0) {
            $res .= _('Not managed');
        } else {
            $res .= pretty_months($dur);
        }
        $res .= '</option>';
    }

    $res .= '</select>';
    return $res;
}

/**
 * select_values($arr,$cur) echo des <option> du tableau $values ou de la table sql $values
 *  selectionne $current par defaut. 
 *  Si on lui demande poliement, il prend un tableau a une dimension
 * 
 * @param array $values
 * @param string $cur
 * @param boolean $onedim
 */
function eoption($values, $cur, $onedim = false) {
    if (is_array($values)) {
        foreach ($values as $k => $v) {
            if ($onedim) {
                $k = $v;
            }
            echo "<option value=\"$k\"";
            if ($k == $cur) {
                echo " selected=\"selected\"";
            }
            echo ">" . $v . "</option>";
        }
    }
}

/**
  /* Echo the HTMLSpecialChars version of a value.
 * Must be called when pre-filling fields values in forms such as : 
 * <input type="text" name="toto" value="<?php ehe($toto); ?>" />
 * Use the charset of the current language for transcription
 * 
 * @global string $charset
 * @param string $str
 * @param boolean $affiche
 * @return string
 */
function ehe($str, $affiche = TRUE) {
    global $charset;
    $retour = htmlspecialchars($str, ENT_QUOTES|ENT_SUBSTITUTE, $charset);
    if ($affiche) {
        echo $retour;
    }
    return $retour;
}

/**
  /* Echo the URLENCODED version of a value.
 * Must be called when pre-filling fields values in URLS such as : 
 * document.location='logs_tail.php?file=<?php eue($file); ?>
 * Use the charset of the current language for transcription
 * 
 * @global string $charset
 * @param string $str
 * @param boolean $affiche
 * @return string
 */
function eue($str, $affiche = TRUE) {
    global $charset;
    $retour = urlencode($str);
    if ($affiche) {
        echo $retour;
    }
    return $retour;
}

/* Get the Fields of the posted form from $_REQUEST or POST or GET
 * and check their type
 */

/**
 * 
 * @param array $fields
 * @param boolean $requestOnly
 * @return array
 */
function getFields($fields, $requestOnly = false) {
    $vars = array();
    $methodType = array("get", "post", "request", "files", "server");

    foreach ($fields AS $name => $options) {
        if (in_array(strtolower($options[0]), $methodType) === false) {
            die("Unrecognized method type used for field " . $name . " : " . $options[0]);
        }
        if ($requestOnly === true) {
            $method = "_REQUEST";
        } else {
            $method = "_" . strtoupper($options[0]);
        }
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
                die("Illegal method type used for field " . $name . " : " . $options[1]);
        }
    }

    // Insert into $GLOBALS.
    foreach ($vars AS $var => $value) {
        $GLOBALS[$var] = $value;
    }
    return $vars;
}

/**
 * 
 * @param array $array
 */
function printVar($array) {
    echo "<pre style=\"border: 1px solid black; text-align: left; font-size: 9px\">\n";
    print_r($array);
    echo "</pre>\n";
}

/**
 * 
 * @param array $a
 * @param array $b
 * @return int
 */
function list_properties_order($a, $b) {
    if ($a['label'] == $b['label']) {
        return 0;
    }
    return ($a['label'] < $b['label']) ? -1 : 1;
}

/**
 * Shows a pager : Previous page 0 1 2 ... 16 17 18 19 20 ... 35 36 37 Next page
 * 
 * 
 * Arguments are as follow : 
 * $offset = the current offset from 0 
 * $count = The number of elements shown per page 
 * $total = The total number of elements 
 * $url = The url to show for each page. %%offset%% will be replace by the proper offset
 * $before & $after are HTML code to show before and after the pager **only if the pager is to be shown
 * 
 * @param int $offset
 * @param int $count
 * @param int $total
 * @param string $url
 * @param string $before
 * @param string $after
 * @param boolean $echo
 * @return string
 */
function pager($offset, $count, $total, $url, $before = "", $after = "", $echo = true) {
    $return = "";
    $offset = intval($offset);
    $count = intval($count);
    $total = intval($total);
    if ($offset <= 0) {
        $offset = "0";
    }
    if ($count <= 1) {
        $count = "1";
    }
    if ($total <= 0) {
        $total = "0";
    }
    if ($total < $offset) {
        $offset = max(0, $total - $count);
    }
    if ($total <= $count) { // When there is less element than 1 complete page, just don't do anything :-D
        return true;
    }
    $return .= $before;
    // Shall-we show previous page link ?
    if ($offset) {
        $o = max($offset - $count, 0);
        $return .= "<a href=\"" . str_replace("%%offset%%", $o, $url) . "\" alt=\"(Ctl/Alt-p)\" title=\"(Alt-p)\" accesskey=\"p\">" . _("Previous Page") . "</a> ";
    } else {
        $return .= _("Previous Page") . " ";
    }

    if ($total > (2 * $count)) { // On n'affiche le pager central (0 1 2 ...) s'il y a au moins 2 pages.
        $return .= " - ";
        if (($total < ($count * 10)) && ($total > $count)) {  // moins de 10 pages : 
            for ($i = 0; $i < $total / $count; $i++) {
                $o = $i * $count;
                if ($offset == $o) {
                    $return .= $i . " ";
                } else {
                    $return .= "<a href = \"" . str_replace("%%offset%%", $o, $url) . "\">$i</a> ";
                }
            }
        } else { // Plus de 10 pages, on affiche 0 1 2 , 2 avant et 2 apr�s la page courante, et les 3 dernieres
            for ($i = 0; $i <= 2; $i++) {
                $o = $i * $count;
                if ($offset == $o) {
                    $return .= $i . " ";
                } else {
                    $return .= "<a href=\"" . str_replace("%%offset%%", $o, $url) . "\">$i</a> ";
                }
            }
            if ($offset >= $count && $offset < ($total - 2 * $count)) { // On est entre les milieux ...
                // On affiche 2 avant jusque 2 apr�s l'offset courant mais sans d�border sur les indices affich�s autour
                $start = max(3, intval($offset / $count) - 2);
                $end = min(intval($offset / $count) + 3, intval($total / $count) - 3);
                if ($start != 3) {
                    $return .= " ... ";
                }
                for ($i = $start; $i < $end; $i++) {
                    $o = $i * $count;
                    if ($offset == $o) {
                        $return .= $i . " ";
                    } else {
                        $return .= "<a href=\"" . str_replace("%%offset%%", $o, $url) . "\">$i</a> ";
                    }
                }
                if ($end != intval($total / $count) - 3) {
                    $return .= " ... ";
                }
            } else {
                $return .= " ... ";
            }
            for ($i = intval($total / $count) - 3; $i < $total / $count; $i++) {
                $o = $i * $count;
                if ($offset == $o) {
                    $return .= $i . " ";
                } else {
                    $return .= "<a href=\"" . str_replace("%%offset%%", $o, $url) . "\">$i</a> ";
                }
            }
            $return .= " - ";
        } // More than 10 pages?
    }
    // Shall-we show the next page link ?
    if ($offset + $count < $total) {
        $o = $offset + $count;
        $return .= "<a href=\"" . str_replace("%%offset%%", $o, $url) . "\" alt=\"(Ctl/Alt-s)\" title=\"(Alt-s)\" accesskey=\"s\">" . _("Next Page") . "</a> ";
    } else {
        $return .= _("Next Page") . " ";
    }
    $return .= $after;
    if ($echo) {
        echo $return;
    }
    return $return;
}

/**
 * 
 * @param int $length
 * @param int $classcount
 * @return string
 */
function create_pass($length = 10, $classcount = 3) {
    $sets = array();

    // Si classcount policy est 4 catégories différents, on utilise les 4 cat, sinon, on en utilise 3
    if ($classcount < 4)
	$available_sets='lud';
    else
	$available_sets='luds';

    if(strpos($available_sets, 'l') !== false)
	$sets[] = 'abcdefghijklmnopqrstuvwxyz';
    if(strpos($available_sets, 'u') !== false)
	$sets[] = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    if(strpos($available_sets, 'd') !== false)
	$sets[] = '0123456789';
    if(strpos($available_sets, 's') !== false)
	$sets[] = '(!#$%)*+,-./:;<=>?@[\]^_';

    $all = '';
    $password = '';
    foreach($sets as $set) {
	$password .= $set[array_rand(str_split($set))];
	$all .= $set;
    }

    $all = str_split($all);
    for($i = 0; $i < $length - count($sets); $i++)
	$password .= $all[array_rand($all)];

    $password = str_shuffle($password);

    return $password;
}

/**
 *  Affiche un bouton qui permet de generer automatiquement des mots de passes 
 * 
 * @param int $pass_size
 * @param string $fields_to_fill1
 * @param string $fields_to_fill2
 * @return int
 */
function display_div_generate_password($pass_size = DEFAULT_PASS_SIZE, $fields_to_fill1 = "", $fields_to_fill2 = "") {
    static $id=1;
    echo "<div id='z$id' style='display:none;'><a href=\"javascript:generate_password_html('$id',$pass_size,'$fields_to_fill1','$fields_to_fill2');\">";
    __("Clic here to generate a password");
    echo "</a></div>";
    echo "<script type='text/javascript'>$('#z$id').show();</script>";
    $id++;
    return 0;
}

/**
 * Affiche un bouton pour selectionner un dossier sur le serveur 
 * 
 * @param string    $dir
 * @param string    $caller
 * @param int       $width
 * @param int       $height
 */
function display_browser($dir = "", $caller = "main.dir", $width = 350, $height = 450) {
    // Browser id
    static $id=0;
    $id++;
    $bid = "b" . $id;
    echo "<script type=\"text/javascript\">
        <!--
          $(function() {
              $( \"#" . $bid . "\" ).dialog({
              autoOpen: false,
              width: " . $width . ",
              height: " . $height . ",
              modal: true,
              open: function()
                {
                    $('.ui-widget-overlay').css('opacity', .70);
                    $('.ui-dialog-content').css('background-color', '#F0F0FA');
                },
            });
         
            $( \"#bt" . $bid . "\" )
              .button()
              .attr(\"class\", \"ina\")
              .click(function() {
                $( \"#" . $bid . "\" ).dialog( \"open\" );
                return false;
              });
          });
          
          
          document.write('&nbsp;<input type=\"button\" id=\"bt" . $bid . "\" value=\"" . _("Choose a folder...") . "\" class=\"ina\">');
          document.write('<div id=\"" . $bid . "\" title=\"" . _("Choose a folder...") . "\" style=\"display: none; bgcolor:red;\">');
          document.write('  <iframe src=\"/browseforfolder2.php?caller=" . $caller . "&amp;file=" . ehe($dir, false) . "&amp;bid=" . $bid . "\" width=\"" . ($width - 40) . "\" height=\"" . ($height - 64) . "\" frameborder=\"no\" id=\"browseiframe\"></iframe>');
          document.write('</div>');
        //  -->
        </script>
        ";
}

/**
 *  Converts HSV to RGB values
 * -----------------------------------------------------
 *  Reference: http://en.wikipedia.org/wiki/HSL_and_HSV
 *  Purpose:   Useful for generating colours with
 *             same hue-value for web designs.
 *  Input:     Hue        (H) Integer 0-360
 *             Saturation (S) Integer 0-100
 *             Lightness  (V) Integer 0-100
 *  Output:    String "R,G,B"
 *             Suitable for CSS function RGB().
 *  
 * @param int   $iH
 * @param int   $iS
 * @param int   $iV
 * @return array
 */
function fHSVtoRGB($iH, $iS, $iV) {

    if ($iH < 0) {
        $iH = 0;   // Hue:
    }
    if ($iH > 360) {
        $iH = 360; //   0-360
    }
    if ($iS < 0) {
        $iS = 0;   // Saturation:
    }
    if ($iS > 100) {
        $iS = 100; //   0-100
    }
    if ($iV < 0) {
        $iV = 0;   // Lightness:
    }
    if ($iV > 100) {
        $iV = 100; //   0-100
    }

    $dS = $iS / 100.0; // Saturation: 0.0-1.0
    $dV = $iV / 100.0; // Lightness:  0.0-1.0
    $dC = $dV * $dS;   // Chroma:     0.0-1.0
    $dH = $iH / 60.0;  // H-Prime:    0.0-6.0
    $dT = $dH;       // Temp variable

    while ($dT >= 2.0) {
        $dT -= 2.0; // php modulus does not work with float
    }
    $dX = $dC * (1 - abs($dT - 1));     // as used in the Wikipedia link

    switch ($dH) {
        case($dH >= 0.0 && $dH < 1.0):
            $dR = $dC;
            $dG = $dX;
            $dB = 0.0;
            break;
        case($dH >= 1.0 && $dH < 2.0):
            $dR = $dX;
            $dG = $dC;
            $dB = 0.0;
            break;
        case($dH >= 2.0 && $dH < 3.0):
            $dR = 0.0;
            $dG = $dC;
            $dB = $dX;
            break;
        case($dH >= 3.0 && $dH < 4.0):
            $dR = 0.0;
            $dG = $dX;
            $dB = $dC;
            break;
        case($dH >= 4.0 && $dH < 5.0):
            $dR = $dX;
            $dG = 0.0;
            $dB = $dC;
            break;
        case($dH >= 5.0 && $dH < 6.0):
            $dR = $dC;
            $dG = 0.0;
            $dB = $dX;
            break;
        default:
            $dR = 0.0;
            $dG = 0.0;
            $dB = 0.0;
            break;
    }

    $dM = $dV - $dC;
    $dR += $dM;
    $dG += $dM;
    $dB += $dM;
    $dR *= 255;
    $dG *= 255;
    $dB *= 255;

    return array('r' => round($dR), 'g' => round($dG), 'b' => round($dB));
}

/**
 * 
 * @param int   $hex
 * @return int  
 */
function hexa($hex) {
    $num = dechex($hex);
    return (strlen("$num") >= 2) ? "$num" : "0$num";
}

/**
 * 
 * @param int   $p
 * @return string
 */
function PercentToColor($p = 0) {
    if ($p > 100) {
        $p = 100;
    }
    if ($p < 0) {
        $p = 0;
    }
    // Pour aller de vert a rouge en passant par jaune et orange
    $h = 1 + ((100 - $p) * 130 / 100);

    $rvb = fHSVtoRGB((int) $h, 96, 93);
    $color = "#" . hexa($rvb['r']) . hexa($rvb['g']) . hexa($rvb['b']);

    return $color;
}

/**
 * 
 * @global m_messages    $msg
 * @global m_mem    $mem
 * @global int          $cuid
 * @return boolean
 */
function panel_lock() {
    global $cuid;
    if ($cuid != 2000) {
        return false;
    }
    return touch(ALTERNC_LOCK_PANEL);
}

/**
 * 
 * @global m_messages    $msg
 * @global m_mem    $mem
 * @global int          $cuid
 * @return boolean
 */
function panel_unlock() {
    global $cuid;
    if ($cuid != 2000) {
        return false;
    }
    return unlink(ALTERNC_LOCK_PANEL);
}

/**
 * 
 * @return boolean
 */
function panel_islocked() {
    return file_exists(ALTERNC_LOCK_PANEL);
}


/** Give a new CSRF uniq token for a form
 * the session must be up since the CSRF is linked
 * to the session cookie. We also need the $db pdo object
 * @return the csrf cookie to add into a csrf hidden field in your form
 */
function csrf_get($return=false) {
    global $db;
    static $token="";
    if (!isset($_SESSION["csrf"])) {
        $_SESSION["csrf"]=md5(mt_rand().mt_rand().mt_rand());
    }
    if ($token=="") {
      $token=md5(mt_rand().mt_rand().mt_rand());
      $db->query("INSERT INTO csrf SET cookie=?, token=?, created=NOW(), used=0;",array($_SESSION["csrf"],$token));
    }
    if ($return) 
        return $token;
    echo '<input type="hidden" name="csrf" value="'.$token.'" />';
    return true;        
}

/** Check a CSRF token against the current session
 * a token can be only checked once, it's disabled then
 * @param $token string the token to check in the DB + session
 * @return $result integer 0 for invalid token, 1 for good token, -1 for expired token (already used)
 * if a token is invalid or expired, an $msg is raised, that can be displayed
 */
function csrf_check($token=null) {
    global $db,$msg;

    if (is_null($token)) $token=$_POST["csrf"];

    if (!isset($_SESSION["csrf"])) {
        $msg->raise('Error', "functions", _("The posted form token is incorrect. Maybe you need to allow cookies"));
        return 0; // no csrf cookie :/
    }
    if (strlen($token)!=32 || strlen($_SESSION["csrf"])!=32) {
        unset($_SESSION["csrf"]);
        $msg->raise('Error', "functions", _("Your cookie or token is invalid"));
        return 0; // invalid csrf cookie 
    }
    $db->query("SELECT used FROM csrf WHERE cookie=? AND token=?;",array($_SESSION["csrf"],$token));
    if (!$db->next_record()) {
        $msg->raise('Error', "functions", _("Your token is invalid"));
        return 0; // invalid csrf cookie 
    }
    if ($db->f("used")) {
        $msg->raise('Error', "functions", _("Your token is expired. Please refill the form."));
        return -1; // expired
    }
    $db->query("UPDATE csrf SET used=1 WHERE cookie=? AND token=?;",array($_SESSION["csrf"],$token)); 
    $db->exec("DELETE FROM csrf WHERE created<DATE_SUB(NOW(), INTERVAL 1 DAY);");
    return 1;
}
