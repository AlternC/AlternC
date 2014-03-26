#!/usr/bin/php -q
<?php

require_once("/usr/share/alternc/panel/class/config_nochk.php");

global $db;

echo "\n---------------------------\n Generating size-cache for web accounts\n\n";
$r=mysql_query("SELECT uid,login FROM membres;");
while ($c=mysql_fetch_array($r)) {
  echo $c["login"]; flush();
  $size=exec("sudo /usr/lib/alternc/du.pl ".ALTERNC_HTML."/".substr($c["login"],0,1)."/".$c["login"]);
  $db->query("REPLACE INTO size_web SET uid='".$c["uid"]."',size='$size';");
  echo " done ($size KB) \n"; flush();
}

echo "\n---------------------------\n Generating size-cache for MySQL databases\n\n";
  // We get all hosts on which sql users' DB are
  $r=mysql_query("select * from db_servers;");
  $tab=array();
  while ($c=mysql_fetch_array($r)) {
    $tab=$mysql->get_dbus_size($c["name"],$c["host"],$c["login"],$c["password"],$c["client"]);
    echo "++ Processing ".$c["name"]." ++\n";
    foreach ($tab as $dbname=>$size) {
      $db->query("REPLACE INTO size_db SET db='".$dbname."',size='$size';"); 
      echo "   $dbname done ($size B) \n"; flush();
    }
    echo "\n";
  }

echo "---------------------------\n Generating size-cache for mailman\n\n";
if ($db->query("SELECT uid, name FROM mailman;")) {
  $cc=array();
  $d=array();
  if($db->num_rows()){
    while ($db->next_record()) {
      $cc[]=array("uid" => $db->f("uid"), "name" => $db->f("name"));
    }
    foreach ($cc as $c){
      echo $c["uid"]."/".$c["name"]; flush();
      $size1=exec("sudo /usr/lib/alternc/du.pl /var/lib/mailman/lists/".$c["name"]);
      $size2=exec("sudo /usr/lib/alternc/du.pl /var/lib/mailman/archives/private/".$c["name"]);
      $size3=exec("sudo /usr/lib/alternc/du.pl /var/lib/mailman/archives/private/".$c["name"].".mbox");
      $size=(intval($size1)+intval($size2)+intval($size3));
      $db->query("REPLACE INTO size_mailman SET uid='".$c["uid"]."',list='".$c["name"]."', size='$size';");
      echo " done ($size KB) \n"; flush();
    }
  }
}


?>
