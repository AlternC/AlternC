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
      $size=$qu['used'];
    } else {
      // The QUOTA system being disabled, we need to use 'du' on each folder.
      $login = $db->f('login');
      $size=exec("/usr/bin/du -s /var/www/alternc/".substr($login,0,1)."/".$login);
    }
    $db2->query("REPLACE INTO size_web SET uid=?, size=?;",array(intval($db->f('uid')),intval($size)));
    echo $db->f('login')." (".(round($size/1024, 1))." MB)\n";
  }
}

echo "\n---------------------------\n Generating size-cache for MySQL databases\n\n";
  // We get all hosts on which sql users' DB are
$r=$db->query("select * from db_servers;");
$allsrv=array();
while ($db->next_record()) {
  $allsrv[] = $db->Record;
}

$tab=array();
foreach($allsrv as $c) {
  $tab=$mysql->get_dbus_size($c["name"],$c["host"],$c["login"],$c["password"],$c["client"]);
  echo "++ Processing ".$c["name"]." ++\n";
  foreach ($tab as $dbname=>$size) {
    $db->query("REPLACE INTO size_db SET db=?,size=?;",array($dbname,$size)); 
    echo "   $dbname done (".(round(($size/1024)/1024,1))." MB) \n"; flush();
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
      $size1=exec("sudo /usr/bin/du -s ".escapeshellarg("/var/lib/mailman/lists/".$c["name"]));
      $size2=exec("sudo /usr/bin/du -s ".escapeshellarg("/var/lib/mailman/archives/private/".$c["name"]));
      $size3=exec("sudo /usr/bin/du -s ".escapeshellarg("/var/lib/mailman/archives/private/".$c["name"].".mbox"));
      $size=(intval($size1)+intval($size2)+intval($size3));
      $db->query("REPLACE INTO size_mailman SET uid=?,list=?,size=?;",array($c["uid"],$c["name"],$size));
      echo " done (".(round($size/1024, 1))." MB) \n"; flush();
    }
  }
}


?>
