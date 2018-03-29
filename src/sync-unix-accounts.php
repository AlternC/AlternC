#!/usr/bin/php
<?php

/**
 * Synchronize all ALTERNC accounts to be also UNIX accounts
 * set / delete entries in /etc/passwd /etc/shadow /etc/group
 * the home of each user will be the AlternC root folder.
 * launch me as a cron as root :) 
 */
// Totally ignore those UNIX AND ALTERNC accounts : 
$skip=array("root","bin","www-data","sshd","mail","vmail","sys","man","lp","news","uucp","proxy","backup","list","irc","nobody","mysql","postfix","bind","ftp","clamav","munin","postgres","amavis","dspam","puppet","nagios","proftpd","messagebus","statd","alterncpanel","dovecot","dovenull","alternc-roundcube","saned");
// Set the user to have THIS shell :
$newshell="/bin/bash"; // could be /bin/false or /bin/nologin


if (getmyuid()!=0) {
    echo "Fatal: must be launched as root !\n";
    exit(1);
}
$lock="/run/sync-unix-accounts.lock";
if (is_file($lock) && is_dir("/proc/".intval(file_get_contents($lock)))) {
    echo "AlternC Sync Unix locked\n";
    exit(0);
}
file_put_contents($lock,getmypid());

require_once("/usr/share/alternc/panel/class/config_nochk.php");

global $db;
$members=array();
$unix=array();
putenv("PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin");
openlog("[AlternC Sync Unix]",null,LOG_USER);

$db->query("SELECT uid,login FROM membres;");
while ($db->next_record()) {
    if (in_array($db->Record["login"],$skip)) continue;
    $members[$db->Record["uid"]]=$db->Record["login"];
}

$f=fopen("/etc/passwd","rb");
while ($s=fgets($f,1024)) {
    list($user,$pass,$uid,$gid,$gecos,$home,$shell)=explode(":",$s,7);
    if ($uid<2000 || $uid>32000 || $uid!=$gid || substr($home,0,strlen($L_ALTERNC_HTML))!=$L_ALTERNC_HTML) continue;
    $unix[$uid]=$user;
}

// print_r($unix); print_r($members);
// $L_ALTERNC_HTML

// What shall we create / delete ?
$create=array();
$delete=array();

foreach($members as $muid=>$mlogin) {
    if (!isset($unix[$muid])) {
        $create[$muid]=$mlogin;
    } else {
        if ($unix[$muid]!=$mlogin) {
            $delete[$muid]=1;
        }
    }
}
foreach($unix as $uuid=>$ulogin) {
    if (!isset($members[$uuid])) {
        $delete[$uuid]=$ulogin;
    }
}

if (!count($create) && !count($delete)) {
    @unlink($lock);
    exit(0);
}

syslog(LOG_INFO,"Will create ".count($create)." Unix account and delete ".count($delete).".");


// print_r($create); print_r($delete);

// ------------------------------------------------------------
// /ETC/PASSWD
copy("/etc/passwd","/etc/passwd.bak");
$f=fopen("/etc/passwd","rb");
flock($f,LOCK_EX);
$g=fopen("/etc/passwd.alternc","wb");
$lastwascr=false;
while ($s=fgets($f,1024)) {
    list($user,$pass,$uid,$gid,$gecos,$home,$shell)=explode(":",$s,7);
    if ($uid<2000 || $uid>32000 || $uid!=$gid
    || substr($home,0,strlen($L_ALTERNC_HTML))!=$L_ALTERNC_HTML
    || !isset($delete[$uid])
    ) {
        fputs($g,$s);
        $lastwascr = (substr($s,-1)=="\n");
    }
}
if (!$lastwascr) { // last line didn't end by  \n !! normalize it:
    fputs($g,"\n");
}
foreach($create as $uid=>$login) {
    fputs($g,$login.":x:".$uid.":".$uid.":,,,:".$L_ALTERNC_HTML."/".substr($login,0,1)."/".$login.":$newshell\n");
}
fclose($f);
fclose($g);
rename("/etc/passwd.alternc","/etc/passwd");
syslog(LOG_INFO,"Wrote /etc/passwd");

// ------------------------------------------------------------
// /ETC/GROUP
copy("/etc/group","/etc/group.bak");
$f=fopen("/etc/group","rb");
flock($f,LOCK_EX);
$g=fopen("/etc/group.alternc","wb");
$lastwascr=false;
while ($s=fgets($f,1024)) {
    list($user,$pass,$gid,$users)=explode(":",$s,4);
    if ($gid<2000 || $gid>32000 
    || !isset($delete[$gid])
    ) {
        fputs($g,$s);
        $lastwascr = (substr($s,-1)=="\n");
    }
}
if (!$lastwascr) { // last line didn't end by  \n !! normalize it:
    fputs($g,"\n");
}
foreach($create as $uid=>$login) {
    fputs($g,$login.":x:".$uid.":\n");
}
fclose($f);
fclose($g);
rename("/etc/group.alternc","/etc/group");
syslog(LOG_INFO,"Wrote /etc/group");

// ------------------------------------------------------------
// /ETC/SHADOW
copy("/etc/shadow","/etc/shadow.bak");
$f=fopen("/etc/shadow","rb");
flock($f,LOCK_EX);
$g=fopen("/etc/shadow.alternc","wb");
$lastwascr=false;
while ($s=fgets($f,1024)) {
    list($user,$pass,$rest)=explode(":",$s,3);
    if (
        !in_array($user,$delete)
    ) {
        fputs($g,$s);
        $lastwascr = (substr($s,-1)=="\n");
    }
}
if (!$lastwascr) { // last line didn't end by  \n !! normalize it:
    fputs($g,"\n");
}
foreach($create as $uid=>$login) {
    fputs($g,$login.":*:17380:0:99999:7:::\n");
}
fclose($f);
fclose($g);
chmod("/etc/shadow.alternc",0640);
rename("/etc/shadow.alternc","/etc/shadow");
syslog(LOG_INFO,"Wrote /etc/shadow");

if (count($create)) syslog(LOG_INFO,"Wrote unix system files, ADDED:".implode(" ",$create));
if (count($delete)) syslog(LOG_INFO,"Wrote unix system files, DELETED:".implode(" ",$delete));


@unlink($lock);
