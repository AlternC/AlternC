#!/usr/bin/php-alternc-wrapper -q 
<?php

include("/usr/share/alternc/panel/class/config_nochk.php");

$db->query("SELECT id,hostname FROM aws;");
$d=array();
while ($db->next_record()) {
 $d[]=$db->Record;
}
foreach ($d as $r) {
 $aws->_createconf($r[0],1);
}

?>
