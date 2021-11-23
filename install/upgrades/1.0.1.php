#!/usr/bin/php-alternc-wrapper
<?php

// If we upgrade directly to 3.1 the panel directory change
$panel='';
if(chdir("/usr/share/alternc/panel")) $panel='/usr/share/alternc/panel';
elseif (chdir("/var/alternc/bureau")) $panel='/var/alternc/bureau';

if (empty($panel)) { echo "Problem to load panel library"; exit(1); }

require("$panel/class/config_nochk.php");

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
