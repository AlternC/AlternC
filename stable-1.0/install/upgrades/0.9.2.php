#!/usr/bin/php -q
<?php

// Set the available memory to a large enough variable to be able to handle quite busy servers :) 
ini_set("memory_limit","128M");

/**
 * Le but de ce script est deux choses:
 *
 * - migration des données LDAP vers les bases MYSQL de mx/imap
 * - conversion des mots de passe en "crypt"
 *
 * dépendances de ce script:
 * php4-cgi + php4-ldap + php4-mysql pour la migration ldap=>mysql du mail
 * donc le script "0.9.1_migrationldap.php"
 * 
 */

/**
 * for _md5cr()
 */
require_once('/var/alternc/bureau/class/functions.php');
require_once('/var/alternc/bureau/class/config_nochk.php');

$config = "/var/alternc/bureau/class/local.php";
$bar = @include($config);
if ($bar === FALSE) {
  echo "cannot find the PHP config file: $config, aborting\n";
  exit(0);
}

/**
 * sortir sans erreur et avec des instructions pour l'usager
 */
function graceful_failure() {
  global $config;
  echo "assuming accounts have already been transfered\n";
  echo "if that is not the case:\n";
  echo " - make sure the LDAP server is running\n";
  echo " - make sure the login information is correct (in $config)\n";
  echo " - restart this script (".$_SERVER['argv'][0].")\n";
  exit(0);
}

// premiere etape
echo "Step 1: converting the LDAP database to MySQL, hold on\n";
echo "  a dot (.) is printed for each 10 successful request\n";
echo "  a X is printed for each failed request.\n";
echo "    Usually, those failed requests occur when an entry is already present\n";
echo "    in the database and can generally be ignored\n";

// On a chargé un fichier de local.php version antérieure, donc AVEC LDAP et SANS mysql_host (surement)

// Connect to the ldap server

if (function_exists("ldap_connect")) {
if (!($ds=ldap_connect($L_LDAP_HOST))) {
  echo "cannot connect to ldap server \"$L_LDAP_HOST\"\n";
  graceful_failure();
}

if (!(ldap_bind($ds,$L_LDAP_ROOT,$L_LDAP_ROOTPWD))) {
  ldap_close($ds);
  echo "cannot bind to ldap server \"$L_LDAP_HOST\" with user \"$L_LDAP_ROOT\"\n";
  graceful_failure();
}

// Connect to the mysql server
// errors here are fatal
if (!mysql_connect($L_MYSQL_HOST,$L_MYSQL_LOGIN,$L_MYSQL_PWD)) {
   echo "cannot connect to mysql server\n";
   return 1;
}
if (!mysql_select_db($L_MYSQL_DATABASE)) {
   echo "cannot connect to mysql database\n";
   return 1;
}

// Now enumerate the data for each base.
$sr=ldap_search($ds,"dc=domains,".$L_LDAP_POSTFIX,"(objectclass=mail)",
                array("mail","uid","account","pop","type"));
$info = ldap_get_entries($ds, $sr);
if ($info["count"]==0) {
    echo "INFO : Aucun mail dans la base DOMAINS \n";
}

echo "Transferring ".$info["count"]." Entries from domains ";
for($i=0;$i<$info["count"];$i++) {
  if ($info[$i]["type"][0]=="mail") $type=0; else $type=1;

    if (count($info[$i]["account"]) > 1) {
      unset($info[$i]["account"]['count']);
      $accounts = join("\n", $info[$i]["account"]);
    }
    mysql_query("INSERT INTO mail_domain (mail,alias,uid,pop,type) VALUES ('".
                addslashes($info[$i]["mail"][0])."','".
                addslashes($accounts)."','".
                addslashes($info[$i]["uid"][0])."','".
                addslashes($info[$i]["pop"][0])."','$type');") || print "X";
    if (($i/10.0)==intval($i/10)) { echo "."; flush(); }
}
echo " done\n";

$sr=ldap_search($ds,"dc=aliases,".$L_LDAP_POSTFIX,"(objectClass=alias)",
                array("mail","alias"));
$info = ldap_get_entries($ds, $sr);
if ($info["count"]==0) {
    echo "INFO : Aucun mail dans la base ALIASES \n";
}

echo "Transferring ".$info["count"]." Entries from aliases ";
for($i=0;$i<$info["count"];$i++) {
    mysql_query("INSERT INTO mail_alias (mail,alias) VALUES ('".
                addslashes($info[$i]["mail"][0])."','".
                addslashes($info[$i]["alias"][0])."');") || print "X";
    if (($i/10.0)==intval($i/10)) { echo "."; flush(); }
}
echo " done\n";


$sr=ldap_search($ds,"dc=users,".$L_LDAP_POSTFIX,"(objectClass=posixAccount)",
                array("uid","gidNumber","homeDirectory","userPassword"));
$info = ldap_get_entries($ds, $sr);
if ($info["count"]==0) {
    echo "INFO : Aucun mail dans la base USERS \n";
}


echo "Transferring ".$info["count"]." Entries from users ";
for($i=0;$i<$info["count"];$i++) {
  // echo serialize($info[$i])."\n";
  $pass=substr($info[$i]["userpassword"][0],7);
  mysql_query("INSERT INTO mail_users (uid,alias,path,password) VALUES ('".
              addslashes($info[$i]["gidnumber"][0])."','".
              addslashes($info[$i]["uid"][0])."','".
              addslashes($info[$i]["homedirectory"][0])."','".
              addslashes($pass)."');") || print "X";
    if (($i/10.0)==intval($i/10)) { echo "."; flush(); }
}
echo " done\n";
ldap_close($ds);

} else {
  echo "ldap module not loaded into php, skipping LDAP conversion\n";
}

echo "Step 2: encrypting user passwords ";

if (!mysql_query("use $L_MYSQL_DATABASE")) {
  echo "can't select database $L_MYSQL_DATABASE\n";
}

if ($q = mysql_query("SELECT LENGTH(`pass`) AS len FROM `membres` GROUP BY len ORDER BY len ASC;")) {
  if ($res = mysql_fetch_array($q)) {
    if ($res['len'] == 34) {
      print "(already encrypted)";
    } else {
      if (!($q = mysql_query("SELECT uid,pass FROM membres;"))) {
        echo "SELECT failed: " . mysql_error() . "\n";
      }

      while ($c = mysql_fetch_array($q)) {
        $pass=_md5cr($c['pass']);
        $id=$c['uid'];
        echo "membre $id\n";
        if (!mysql_query("UPDATE membres SET pass='$pass' WHERE uid='$id';")) {
          echo "UPDATE failed: " . mysql_error() . "\n";
        } else {
          echo "."; flush();
        }
      }
    }
  } else {
    echo "fetch_array() failed: ". mysql_error()."\n";
  }
} else {
  echo "query failed: ". mysql_error()."\n";
}
echo "\n";

mysql_close();

?>
