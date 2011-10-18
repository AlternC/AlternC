<?php

/**
 * Special phpmyadmin configuration for AlternC
 *
 * We setup two new servers:
 *
 * i: a server with a hardcoded username/password corresponding to the
 * one setup in the AlternC panels
 *
 * i+1: a regular server with a cookie-based auth
 *
 * the content of this file will be included in the
 * /etc/phpmyadmin/config.inc.php
 */

include_once('/var/alternc/bureau/class/local.php');
$cfg['SuhosinDisableWarning'] = true;

$i = 1;

$cfg['Servers'][$i]['host']          = $GLOBALS['L_MYSQL_HOST']; // MySQL hostname or IP address
$cfg['Servers'][$i]['connect_type']  = 'tcp';    // How to connect to MySQL server ('tcp' or 'socket')
$cfg['Servers'][$i]['auth_type']     = 'config';    // Authentication method (config, http or cookie based)?
$cfg['Servers'][$i]['user']          = $_COOKIE["REMOTE_USER"];    ;      // MySQL user
$cfg['Servers'][$i]['password']      = $_COOKIE["REMOTE_PASSWORD"]; ;          // MySQL password (only needed
//                                                    // with 'config' auth_type)

$i++;

// Uncomment to override the default configuration
$cfg['Servers'][$i]['host']          = $GLOBALS['L_MYSQL_HOST']; // MySQL hostname or IP address
$cfg['Servers'][$i]['connect_type']  = 'tcp';    // How to connect to MySQL server ('tcp' or 'socket')
$cfg['Servers'][$i]['auth_type']     = 'cookie';    // Authentication method (config, http or cookie based)?

?>
