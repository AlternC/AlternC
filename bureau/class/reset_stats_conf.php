<?php

include("config.php");

if (!$admin->enabled) {
    __("This page is restricted to authorized staff");
    exit();
}

$db->query("SELECT id,hostname FROM stats;");
$d = array();
while ($db->next_record()) {
    $d[] = $db->Record;
}
foreach ($d as $r) {
    echo "Stats de " . $r[0] . " " . $r[1] . " <br>\n";
    flush();
    $stats->_createconf($r[0], 1);
}
