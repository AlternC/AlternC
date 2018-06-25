#!/usr/bin/php
<?php
/*
 function called as a hook during alternc update_domains.sh as follow: 
 (launched by functions_hosting.sh in launch_hook() shell function)
 create a host:    launch_hooks "create" "$1" "$2" "$3" "$4" (type domain mail value)
 at the end of host creation:    launch_hooks "postinst" "$1" "$2" "$3" "$4" 
 enable or disable a host:    launch_hooks "enable|disable" "$1" "$2" "$3" (type domain value)
 at host deletion: launch_hooks "delete" "$1" "$2" "$3" "$4" (type fqdn)

 also, after reloading apache : 
  run-parts --arg=web_reload /usr/lib/alternc/reload.d
 
 also, dns functions are: 
 after reconfiguring bind (rndc reconfig) : run-parts --arg=dns_reconfig  /usr/lib/alternc/reload.d
 (may need to *redo* rndc reconfig... a "before_dns_reconfig" would be better !)
 before reloading a zone : run-parts --arg=dns_reload_zone --arg="$domain" /usr/lib/alternc/reload.d
*/

// Bootstrap
require_once("/usr/share/alternc/panel/class/config_nochk.php");

if (!isset($ssl)) {
    echo "OUPS: update_cert.sh launched, but ssl module not installed, exiting\n";    
    exit();
}

if (!isset($argv[1])) {
    echo "FATAL: must be launched from functions_hosting.sh !\n";
    exit();
}

if (posix_getuid()!=0) {
    echo "This script MUST be launched as root, it should be able to overwrite files in /etc/ssl/private\n";
    exit(-1);
}

if ( ($argv[1]=="create" || $argv[1]=="postinst" || $argv[1]=="delete") ) {
    if (count($argv)<5) {
        echo "FATAL: create/postinst/delete need 4 parameters: type domain mail value\n";
        print_r($argv);
        exit();
    }
    $ssl->updateDomain($argv[1], $argv[2], $argv[3], $argv[4]);
    exit();
}
if ( ($argv[1]=="enable" || $argv[1]=="disable") ) {
    if (count($argv)<4) {
        echo "FATAL: enable/disable need 3 parameters: type domain value\n";
        print_r($argv);
        exit();
    }
    $ssl->updateDomain($argv[1], $argv[2], $argv[3] );
    exit();
}

echo "FATAL: action unknown, must be launched from functions_hosting.sh !\n";
print_r($argv);
exit();

