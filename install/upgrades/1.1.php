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

$db->query("SELECT * FROM sub_domaines WHERE type='webmail'");
if ($db->num_rows()) {
  echo "WARNING: You have webmail domain-types, you need to install alternc-squirrelmail or alternc-roundcube to be able to use them again. They will work but may break until you do that\n";
}
