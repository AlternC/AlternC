<?php

# AUTO GENERATED FILE
# Modify template in /etc/alternc/templates/
# and launch alternc.install if you want 
# to modify this file.

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

$cfg['SuhosinDisableWarning'] = true;
$cfg['ShowCreateDb'] = false; 
$cfg['ShowChgPassword'] = false; 
$cfg['LoginCookieRecall'] = false;
$cfg['blowfish_secret'] = '%%PHPMYADMIN_BLOWFISH%%';

$i = 1;

// Magic auth with AlternC
// If SSO doesn't work, redirect to the second server
$cfg['Servers'][$i]['connect_type']  = 'tcp';    // How to connect to MySQL server ('tcp' or 'socket')
$cfg['Servers'][$i]['hide_db']       = 'information_schema';
$cfg['Servers'][$i]['auth_type']     = 'signon';
$cfg['Servers'][$i]['SignonSession'] = 'AlternC_Panel'; // must be the same as AlternC Panel
$cfg['Servers'][$i]['verbose']       = 'Single Sign On virtual server'; // human name
$cfg['Servers'][$i]['SignonURL']     = '/alternc-sql/index.php?server=2'; // if login fail, where to go ?
$cfg['Servers'][$i]['LogoutURL']     = '/index.php'; // go to panel main page when you logout

// Start the auto-generated list of db-server by alternc.install
