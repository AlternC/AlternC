#!/usr/bin/php -q
<?php

// Script de migration des données LDAP vers les bases MYSQL de mx/imap
include("/var/alternc/bureau/class/local.php");

// On a chargé un fichier de local.php version antérieure, donc AVEC LDAP et SANS mysql_host (surement)

// Connect to the ldap server

if (!($ds=ldap_connect($L_LDAP_HOST))) {
   echo "CANNOT CONNECT TO LDAP SERVER ! \n";
   return 1;
}

if (!(ldap_bind($ds,$L_LDAP_ROOT,$L_LDAP_ROOTPWD))) {
	ldap_close($ds);
	echo "CANNOT BIND TO LDAP SERVER ! \n";
	return 1;
}

// Connect to the mysql server
if (!mysql_connect($L_MYSQL_HOST,$L_MYSQL_LOGIN,$L_MYSQL_PWD)) {
   echo "CANNOT CONNECT TO MYSQL SERVER ! \n";
   return 1;
}
if (!mysql_select_db($L_MYSQL_DATABASE)) {
   echo "CANNOT CONNECT TO MYSQL DATABASE ! \n";
   return 1;
}

// Now enumerate the data for each base.

$sr=ldap_search($ds,"dc=domains,".$L_LDAP_POSTFIX,"(type=mail)",array("mail","uid","account","pop"));
$info = ldap_get_entries($ds, $sr);
if ($info["count"]==0) {
    echo "INFO : Aucun mail dans la base DOMAINS \n";
}

echo "Transferring ".$info["count"]." Entries from domains ";
for($i=0;$i<$info["count"];$i++) {
    mysql_query("INSERT INTO mail_domain (mail,alias,uid,pop) VALUES ('".addslashes($info[$i]["mail"][0])."','".addslashes($info[$i]["account"][0])."','".addslashes($info[$i]["uid"][0])."','".addslashes($info[$i]["pop"][0])."');");
    if (($i/10.0)==intval($i/10)) { echo "."; flush(); }
}
echo " done\n";

$sr=ldap_search($ds,"dc=aliases,".$L_LDAP_POSTFIX,"(objectClass=alias)",array("mail","alias"));
$info = ldap_get_entries($ds, $sr);
if ($info["count"]==0) {
    echo "INFO : Aucun mail dans la base ALIASES \n";
}

echo "Transferring ".$info["count"]." Entries from aliases ";
for($i=0;$i<$info["count"];$i++) {
    mysql_query("INSERT INTO mail_alias (mail,alias) VALUES ('".addslashes($info[$i]["mail"][0])."','".addslashes($info[$i]["alias"][0])."');");
    if (($i/10.0)==intval($i/10)) { echo "."; flush(); }
}
echo " done\n";

$sr=ldap_search($ds,"dc=users,".$L_LDAP_POSTFIX,"(objectClass=posixAccount)",array("uid","uidNumber","homeDirectory","userPassword"));
$info = ldap_get_entries($ds, $sr);
if ($info["count"]==0) {
    echo "INFO : Aucun mail dans la base USERS \n";
}

echo "Transferring ".$info["count"]." Entries from users ";
for($i=0;$i<$info["count"];$i++) {
    mysql_query("INSERT INTO mail_users (uid,alias,path,password) VALUES ('".addslashes($info[$i]["uidNumber"][0])."','".addslashes($info[$i]["uid"][0])."','".addslashes($info[$i]["homeDirectory"][0])."','".addslashes($info[$i]["userPassword"][0])."');");
    if (($i/10.0)==intval($i/10)) { echo "."; flush(); }
}
echo " done\n";

mysql_close();
ldap_close($ds);


?>
