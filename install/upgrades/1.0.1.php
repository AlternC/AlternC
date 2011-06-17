#!/usr/bin/php
<?php

   // We check that mysql php module is loaded 
if(!function_exists('mysql_connect'))  {
  if(!dl("mysql.so"))
    exit(1);
}

// we don't check our AlternC session
if(!chdir("/var/alternc/bureau"))
  exit(1);
require("/var/alternc/bureau/class/config_nochk.php");

// we go super-admin
$admin->enabled=1;
$dom->lock();

// And we process the database changes : 
$db->query("SELECT * FROM domaines;");
$domains=array();
while ($db->next_record()) {
  $domains[]=array("dom"=>$db->Record["domaine"],"gesmx"=>$db->Record["gesmx"],"mx"=>$db->Record["mx"]);
}
foreach($domains as $v) {
  if ($v["gesmx"]) {
    $dom->alternc_add_mx_domain($v["dom"]);
  } else {
    $dom->set_sub_domain($v["dom"],"","mx",$v["mx"]);
  }
}

$dom->unlock();


?>