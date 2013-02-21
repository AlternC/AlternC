#!/usr/bin/php
<?php

// We check that mysql php module is loaded 
if(!function_exists('mysql_connect'))  {
  if(!dl("mysql.so"))
    exit(1);
}

// we don't check our AlternC session
if(!chdir("/usr/share/alternc/panel"))
  exit(1);
require("/usr/share/alternc/panel/class/config_nochk.php");


// +++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// FIRST PART : populate the table db_servers

$l = $mysql->dbus;
// populate it if there is not entry
$db->query("select * from db_servers;");
if ($db->num_rows()==0) {
  $db->query(" insert into db_servers (name, host, login, password, client) values ('".mysql_escape_string($l->HumanHostname)."','".mysql_escape_string($l->Host)."','".mysql_escape_string($l->User)."','".mysql_escape_string($l->Password)."','".mysql_escape_string($L_MYSQL_CLIENT)."');");
}

// set the membres.db_server_id 
$db->query(" update membres set db_server_id = (select max(id) from db_servers) where db_server_id is null ;");

// END of db_servers part
// +++++++++++++++++++++++++++++++++++++++++++++++++++++++++

?>
