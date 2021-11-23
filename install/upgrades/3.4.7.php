#!/usr/bin/php-alternc-wrapper
<?php

// we don't check our AlternC session
if(!chdir("/usr/share/alternc/panel"))
exit(1);
require("/usr/share/alternc/panel/class/config_nochk.php");

// we enumerate all tables in AlternC's database, and change (if necessary) their engine to InnoDB

$tables=array();
$db->query("SHOW TABLES;");
while ($db->next_record()) {
    $tables[]=$db->Record[0];
}
echo "Setting AlternC's tables to InnoDB engine\n";
foreach($tables as $table) {
    $db->query("ALTER TABLE `".$table."` ENGINE InnoDB;");
}

echo "Done\n";

