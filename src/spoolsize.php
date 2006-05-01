#!/usr/bin/php -q
<?php

require_once("/var/alternc/bureau/class/config_nochk.php");
// On déverrouile le bureau AlternC :) 
alternc_shutdown();

echo "---------------------------\n Generating size-cache for mail accounts\n\n";
$r=mysql_query("SELECT * FROM mail_users WHERE alias NOT LIKE '%@%' AND alias LIKE '%\_%';");
while ($c=mysql_fetch_array($r)) {
  echo $c["alias"]; flush();
  $size=exec("/usr/lib/alternc/du.pl ".$c["path"]);
  mysql_query("REPLACE INTO size_mail SET alias='".addslashes($c["alias"])."',size='$size';");
  echo " done ($size KB)\n";  flush();
}

echo "---------------------------\n Generating size-cache for db accounts\n\n";
$r=mysql_query("SELECT db FROM db;");
while ($c=mysql_fetch_array($r)) {
  echo $c["db"]; flush();
  $size=$mysql->get_db_size($c["db"]);
  mysql_query("REPLACE INTO size_db SET db='".addslashes($c["db"])."',size='$size';");
  echo " done ($size KB) \n"; flush();
}

echo "---------------------------\n Generating size-cache for web accounts\n\n";
$r=mysql_query("SELECT uid,login FROM membres;");
while ($c=mysql_fetch_array($r)) {
  echo $c["login"]; flush();
  $size=exec("/usr/lib/alternc/du.pl /var/alternc/html/".substr($c["login"],0,1)."/".$c["login"]);
  mysql_query("REPLACE INTO size_web SET uid='".$c["uid"]."',size='$size';");
  echo " done ($size KB) \n"; flush();
}

// On relocke le bureau pour éviter un msg d'erreur.
sem_acquire( $alternc_sem );

?>
