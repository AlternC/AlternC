#!/usr/bin/php
<?php

// If we upgrade directly to 3.1 the panel directory change
$panel='';
if(chdir("/usr/share/alternc/panel")) $panel='/usr/share/alternc/panel';
elseif (chdir("/var/alternc/bureau")) $panel='/var/alternc/bureau';

if (empty($panel)) { echo "Problem to load panel library"; exit(1); }

require("$panel/class/config_nochk.php");

$db2=new DB_System();
// we go super-admin
$admin->enabled=1;

$db->query("SELECT * FROM sub_domaines WHERE type='webmail'");
if ($db->num_rows()) {
  echo "################################################################################\n";
  echo "WARNING: You have WEBMAIL domain-types, you need to install alternc-squirrelmail or alternc-roundcube\n";
  echo "to be able to use them again. those subdomains will be broken until you do that\n";
  echo "Also, a script converts your procmail-builder filters to the new SIEVE protocol.\n";
  echo "This script is in /usr/lib/alternc/procmail_to_sieve.php once to migrate\n";
  echo "PRESS ENTER TO CONTINUE\n";
  echo "################################################################################\n";
}

//updating db and dbusers tables

$db->query("Alter table dbusers add column password varchar(32);");
$db->query("Alter table dbusers add column enable enum('ACTIVATED','HIDDEN','ADMIN') not NULL default 'ACTIVATED';");
$db->query("Alter table db add column id bigint(20) unsigned NOT NULL AUTO INCREMENT primary key;");


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
?>
//done ? :)
