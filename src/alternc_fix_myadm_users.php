#!/usr/bin/php
<?php

   /* Fix the $uid_myadm mysql users access.
      This script is idempotent and can be launch anytime 
      usually after an AlternC upgrade
    */

$f=@fopen("/etc/alternc/my.cnf","rb");
if (!$f) {
  echo "Can't open /etc/alternc/my.cnf !\n";
  exit(1);
}
$mdb=""; $mhost=""; $muser=""; $mpass="";

while ($s=fgets($f,1024)) {
  if (preg_match('#database="([^"]*)#',$s,$mat)) {
    $mdb=$mat[1];
  }
  if (preg_match('#host="([^"]*)#',$s,$mat)) {
    $mhost=$mat[1];
  }
  if (preg_match('#user="([^"]*)#',$s,$mat)) {
    $muser=$mat[1];
  }
  if (preg_match('#password="([^"]*)#',$s,$mat)) {
    $mpass=$mat[1];
  }
}
fclose($f);
if (!$mdb || !$mhost || !$muser || !$mpass) {
  echo "Can't find data in /etc/alternc/my.cnf\n";
  exit(1);
}

function create_pass($length = 8){
  $chars = "1234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
  $i = 0;
  $password = "";
  while ($i <= $length) {
    $password .= @$chars[mt_rand(0,strlen($chars))];
    $i++;
  }
  return $password;
}

require_once("/usr/share/alternc/panel/class/db_mysql.php");
$db=new DB_Sql($mdb,$mhost,$muser,$mpass);
if (!$db) {
  echo "Can't connect to MySQL !\n";
  exit(1);
}

// Fix a bug in 3.0.0
$db->query("UPDATE dbusers SET enable='ACTIVATED' WHERE name!=CONCAT(uid,'_myadm');");

$db->query("SELECT * FROM db_servers");
$srv=array();
$client=array();
while ($db->next_record()) {
    $c=$db->Record;
    $srv[$c["id"]]=new DB_Sql("mysql",$c["host"],$c["login"],$c["password"]);
    if (!$srv[$c["id"]]) {
        echo "Can't connect to server having id ".$c["id"]." at host ".$c["host"]." EXITING !\n";
        exit();
    }
  $client[$c["id"]]=$c["client"];
}

$r=$db->query("SELECT uid, login, db_server_id FROM membres;");
while ($db->next_record()) {
    $c=$db->Record;
    $membres[$c["uid"]]=array($c["login"],$c["db_server_id"]);
}

foreach($membres as $uid => $data) {
  $membre=$data[0];
  $srvid=$data[1];
  $db->query("SELECT * FROM dbusers WHERE uid=$uid AND NAME='".$uid."_myadm';");
  if (!$db->next_record()) {
    echo "Creating user ".$uid."_myadm for login ".$membre."\n";
    $pass=create_pass(8);
    $db->query("INSERT INTO dbusers SET uid=$uid, name='".$uid."_myadm', password='$pass', enable='ADMIN';");
    if (is_array($db->last_error()))   echo implode("\n",$db->last_error());
  } else {
    $pass=$db->f("password");
  }
  echo "Granting rights to user ".$uid."_myadm for login ".$membre." ... ";
  // Now granting him access to all user's databases
  $srv[$srvid]->query("GRANT USAGE ON *.* TO '".$uid."_myadm'@'".$client[$srvid]."' IDENTIFIED BY '$pass';");
  if (is_array($srv[$srvid]->last_error()))   echo implode("\n",$srv[$srvid]->last_error());
  $t=$db->query("SELECT * FROM db WHERE uid=$uid;");
  if (is_array($db->last_error()))   echo implode("\n",$db->last_error());
  while ($db->next_record()) {
      $d=$db->Record;
      $srv[$srvid]->query("GRANT ALL ON ".$d["db"].".* TO '".$uid."_myadm'@'".$client[$srvid]."';");
      echo " ".$d["db"];
      if (is_array($srv[$srvid]->last_error())) echo implode("\n",$srv[$srvid]->last_error());
  }
  echo "\n";
}

