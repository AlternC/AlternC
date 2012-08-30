#!/usr/bin/php -q 
<?php

include("/var/alternc/bureau/class/config_nochk.php");

$db->query("SELECT id,hostname FROM aws;");
while ($db->next_record()) {
 $d[]=$db->Record;
}
foreach ($d as $r) {
 $aws->_createconf($r[0],1);
}

?>