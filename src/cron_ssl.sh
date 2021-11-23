#!/usr/bin/php-alternc-wrapper
<?php
/**
 * script called as a every-minute-crontab
 * to look for new certificates and reload the associated subdomains
 * also delete the old certificates once a day (at 10am)
 */

// Bootstrap
require_once("/usr/share/alternc/panel/class/config_nochk.php");

if (!isset($ssl)) {
    echo "OUPS: update_cert.sh launched, but ssl module not installed, exiting\n";    
    exit();
}

if (posix_getuid()!=0) {
    echo "This script MUST be launched as root, it should be able to overwrite files in /etc/ssl/private\n";
    exit(-1);
}

if (date("H:m")=="10:00") {
    $ssl->delete_old_certificates();
}

$ssl->cron_new_certs();


