#!/usr/bin/php
<?php

// We check that mysql php module is loaded 
if(!function_exists('mysql_connect'))  {
  if(!dl("mysql.so"))
    exit(1);
}

// If we upgrade directly to 3.1 the panel directory change
$panel='';
if(chdir("/usr/share/alternc/panel")) $panel='/usr/share/alternc/panel';
elseif (chdir("/var/alternc/bureau")) $panel='/var/alternc/bureau';

if (empty($panel)) { echo "Problem to load panel library"; exit(1); }

require("$panel/class/config_nochk.php");

$db2=new DB_System();
// we go super-admin
$admin->enabled=1;

$db->query("select distinct uid,login,pass from db;");
//on insere dans dbusers avec enabled = admin
$query=array();
while($db->next_record()){
  $db2->query("select id from dbusers where name ='".$db->f('login')."' and password='".$db->f('pass')."';");
  if($db2->num_rows() ==0 ){
    $query[]="insert ignore into dbusers values('',".$db->f('uid').",'".$db->f('login')."','".$db->f('pass')."',\"ADMIN\");";
  }
}

foreach ($query as $q){
  $db->query($q);
}

?>
//done ? :)
