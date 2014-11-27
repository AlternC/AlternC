#!/usr/bin/php -q
<?php

require_once("/usr/share/alternc/panel/class/config_nochk.php");

global $db;

echo "\n---------------------------\n Generating size-cache for web accounts\n\n";
exec("/usr/lib/alternc/quota_get_all", $list_quota_tmp);
$list_quota=array();
foreach ($list_quota_tmp as $qt) {
  $qt = explode(" ", $qt);
  $list_quota[$qt[0]] = array('used'=>$qt[1], 'quota'=>$qt[2]);
}

if ($db->query("SELECT uid,login FROM membres;")) {
  $db2 = new DB_system();
  while ($db->next_record()) {
    if (isset($list_quota[$db->f('uid')])) {
      $qu=$list_quota[$db->f('uid')];
      $db2->query("INSERT OR REPLACE INTO size_web SET uid='".intval($db->f('uid'))."',size='".intval($qu['used'])."';");
      echo $db->f('login')." (".$qu['used']." B)\n";
    }  
  }
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
      $size1=exec("sudo /usr/lib/alternc/du.pl ".escapeshellarg("/var/lib/mailman/lists/".$c["name"]));
      $size2=exec("sudo /usr/lib/alternc/du.pl ".escapeshellarg("/var/lib/mailman/archives/private/".$c["name"]));
      $size3=exec("sudo /usr/lib/alternc/du.pl ".escapeshellarg("/var/lib/mailman/archives/private/".$c["name"].".mbox"));
      $size=(intval($size1)+intval($size2)+intval($size3));
      $db->query("REPLACE INTO size_mailman SET uid='".$c["uid"]."',list='".$c["name"]."', size='$size';");
      echo " done ($size KB) \n"; flush();
    }
  }
}


?>
