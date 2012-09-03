#!/usr/bin/php
   <?php

$f=@fopen("/etc/alternc/local.sh","rb");
if (!$f) {
  echo "Can't find /etc/alternc/local.sh, please install AlternC properly !\n";
  exit();
}

$ALTERNC_LOC="";
while ($s=fgets($f,1024)) {
  if (strpos($s,"=")!==false) {
    list($key,$val)=explode("=",trim($s),2);
    if (trim($key)=="ALTERNC_LOC") {
      $val=trim(trim($val,"'\""));
      $ALTERNC_LOC=$val;
      break;
    }
  }
  
}
fclose($f);
if (!$ALTERNC_LOC) {
  echo "Can't find ALTERNC_LOC in /etc/alternc/local.sh, please install AlternC properly !\n";
  exit();  
}

require_once($ALTERNC_LOC."/class/config_nochk.php"); 

$upnp->cron();

