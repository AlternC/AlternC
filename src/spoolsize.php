#!/usr/bin/php -q
<?php

require_once("/usr/share/alternc/panel/class/config_nochk.php");
// On déverrouile le bureau AlternC :) 
@alternc_shutdown();

echo "---------------------------\n Generating size-cache for web accounts\n\n";
$r=mysql_query("SELECT uid,login FROM membres;");
while ($c=mysql_fetch_array($r)) {
  echo $c["login"]; flush();
  $size=exec("sudo /usr/lib/alternc/du.pl ".ALTERNC_HTML."/".substr($c["login"],0,1)."/".$c["login"]);
  mysql_query("REPLACE INTO size_web SET uid='".$c["uid"]."',size='$size';");
  echo " done ($size KB) \n"; flush();
}

echo "---------------------------\n Generating size-cache for MySQL databases\n\n";
$r=mysql_query("SELECT uid,db FROM db;");
while ($c=mysql_fetch_array($r)) {
  echo $c["uid"]."/".$c["db"]; flush();
  $s=mysql_query('SHOW TABLE STATUS FROM `'.$c["db"].'`');
  $size=0;
  while ($d=mysql_fetch_array($s)) {
    $size+=$d["Data_length"]+$d["Index_length"];
  }
  //  $size/=1024;
  mysql_query("REPLACE INTO size_db SET db='".$c["db"]."',size='$size';");
  echo " done ($size B) \n"; flush();
}

echo "---------------------------\n Generating size-cache for web accounts\n\n";
$r=@mysql_query("SELECT uid, name FROM mailman;");
if ($r) {
  while ($c=mysql_fetch_array($r)) {
    echo $c["uid"]."/".$c["name"]; flush();
    $size1=exec("sudo /usr/lib/alternc/du.pl /var/lib/mailman/lists/".$c["name"]);
    $size2=exec("sudo /usr/lib/alternc/du.pl /var/lib/mailman/archives/private/".$c["name"]);
    $size3=exec("sudo /usr/lib/alternc/du.pl /var/lib/mailman/archives/private/".$c["name"].".mbox");
    $size=(intval($size1)+intval($size2)+intval($size3));
    mysql_query("REPLACE INTO size_mailman SET uid='".$c["uid"]."',list='".$c["name"]."', size='$size';");
    echo " done ($size KB) \n"; flush();
  }
 }

// On relocke le bureau pour éviter un msg d'erreur.
@sem_acquire( $alternc_sem );

?>
