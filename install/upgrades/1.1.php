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

//updating db and dbusers tables

$db->query("Alter table dbusers add column password varchar(32);");
$db->query("Alter table dbusers add column enable enum('ACTIVATED','HIDDEN','ADMIN') not NULL default 'ACTIVATED';");
$db->query("Alter table db add column id bigint(20) unsigned NOT NULL AUTO INCREMENT primary key;");


$db->query("select distinct uid,login,pass from db;");
//on insere dans dbusers avec enabled = admin
$query=array();
while($db->next_record()){
 $query[]="insert into dbusers values('',".$db->f('uid').",'".$db->f('login')."','".$db->f('pass')."',\"ADMIN\");";
}
foreach ($query as $q){
$db->query($q);
}

//Updating mysql.db table to fix the "_" wildcard bug 

$db->query("select Db from mysql.db ;");
$query2=array();
while($db->next_record()){
  $dbn=preg_replace("/^([A-Za-z0-9]*)_([A-Za-z0-9]*)/","$1\_$2",$db->f('Db'));
  $query2[]="update mysql.db set Db=replace(Db,'".$db->f('Db')."','".$dbn."');";
}

foreach ($query2 as $q2){
$db->query($q2);
}

//done ? :)
