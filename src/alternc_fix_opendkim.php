#!/usr/bin/php
<?php
 // create the missing opendkim keys and update dns zones accordingly when necessary.

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

$ALTERNC_ROOT="/var/alternc/html";

$f=@fopen("/etc/alternc/local.sh","rb");
if (!$f) {
  echo "Can't open /etc/alternc/local.sh !\n";
  exit(1);
}
while ($s=fgets($f,1024)) {
  if (preg_match('#ALTERNC_HTML="([^"]*)#',$s,$mat)) {
    $ALTERNC_ROOT=$mat[1];
  }
}
fclose($f);
$ALTERNC_ROOT=rtrim($ALTERNC_ROOT,"/");


if (!file_exists("/usr/bin/opendkim-genkey")
    && !file_exists("/usr/sbin/opendkim-genkey")) {
  echo "opendkim-tools not installed, please launch:\n";
  echo "apt-get install opendkim-tools\n";
  exit(1);
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

$hasdoneone=false;
$r=mysql_query("SELECT domaine FROM domaines where gesmx=1 AND gesdns=1;");
while ($c=mysql_fetch_array($r)) {
  if (!file_exists("/etc/opendkim/keys/".$c["domaine"]."/alternc.private") ||
      !file_exists("/etc/opendkim/keys/".$c["domaine"]."/alternc.txt")) {
    echo "Creating Opendkim key for domain ".$c["domaine"]."\n";
    if (!is_dir("/etc/opendkim/keys/".$c["domaine"]."")) {
      if (!mkdir("/etc/opendkim/keys/".$c["domaine"]."")) {
	echo "Error creating the directory /etc/opendkim/keys/".$c["domaine"]." !\n";
      } else {
	echo "Created the directory /etc/opendkim/keys/".$c["domaine"]."\n";
      }
    }
    chdir("/etc/opendkim/keys/".$c["domaine"]."");
    passthru("opendkim-genkey -r -d ".$c["domaine"]." -s alternc 2>&1");
    passthru("chown opendkim:opendkim alternc.private 2>&1");
    mysql_query("UPDATE domaines SET dns_action='UPDATE' WHERE domaine='".$c["domaine"]."';");
    $hasdoneone=true;
  }
}

if ($hasdoneone) {
  echo "I created some keys, launching update_domaines...\n";
  passthru("/usr/lib/alternc/update_domains.sh 2>&1");
} else {
  echo "I did nothing, opendkim seems fine...\n";
}

