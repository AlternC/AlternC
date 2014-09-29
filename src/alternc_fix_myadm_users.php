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
    $password .= @$chars{mt_rand(0,strlen($chars))};
    $i++;
  }
  return $password;
}

$res=mysql_connect($mhost,$muser,$mpass);
if (!$res) {
  echo "Can't connect to MySQL !\n";
  exit(1);
}
if (!mysql_select_db($mdb)) {
  echo "Can't connect to DB MySQL !\n";
  exit(1);
}

// Fix a bug in 3.0.0
mysql_query("UPDATE dbusers SET enable='ACTIVATED' WHERE name!=CONCAT(uid,'_myadm');");

$r=mysql_query("SELECT * FROM db_servers",$res);
$srv=array();
$client=array();
while ($c=mysql_fetch_array($r)) {
  $srv[$c["id"]]=mysql_connect($c["host"],$c["login"],$c["password"]);
  if (!$srv[$c["id"]]) {
    echo "Can't connect to server having id ".$c["id"]." at host ".$c["host"]." EXITING !\n";
    exit();
  }
  $client[$c["id"]]=$c["client"];
}

$r=mysql_query("SELECT uid, login, db_server_id FROM membres;",$res);
while ($c=mysql_fetch_array($r)) {
  $membres[$c["uid"]]=array($c["login"],$c["db_server_id"]);
}

foreach($membres as $uid => $data) {
  $membre=$data[0];
  $srvid=$data[1];
  $ok=@mysql_fetch_array(mysql_query("SELECT * FROM dbusers WHERE uid=$uid AND NAME='".$uid."_myadm';",$res));
  if (!$ok) {
    echo "Creating user ".$uid."_myadm for login ".$membre."\n";
    $pass=create_pass(8);
    mysql_query("INSERT INTO dbusers SET uid=$uid, name='".$uid."_myadm', password='$pass', enable='ADMIN';",$res);
    echo mysql_error();
  } else {
    $pass=$ok["password"];
  }
  echo "Granting rights to user ".$uid."_myadm for login ".$membre." ... ";
  // Now granting him access to all user's databases
  mysql_query("GRANT USAGE ON *.* TO '".$uid."_myadm'@'".$client[$srvid]."' IDENTIFIED BY '$pass';",$srv[$srvid]);
  echo mysql_error();
  $t=mysql_query("SELECT * FROM db WHERE uid=$uid;",$res);
  echo mysql_error();
  while ($d=mysql_fetch_array($t)) {
    mysql_query("GRANT ALL ON ".$d["db"].".* TO '".$uid."_myadm'@'".$client[$srvid]."';",$srv[$srvid]);
    echo " ".$d["db"];
    echo mysql_error();
  }
  echo "\n";
}

