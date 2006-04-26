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

$i = 1;

$cfg['Servers'][$i]['host']          = 'localhost'; // MySQL hostname or IP address
$cfg['Servers'][$i]['port']          = '';          // MySQL port - leave blank for default port
$cfg['Servers'][$i]['socket']        = '';          // Path to the socket - leave blank for default socket
$cfg['Servers'][$i]['connect_type']  = 'socket';    // How to connect to MySQL server ('tcp' or 'socket')
$cfg['Servers'][$i]['extension']     = 'mysql';     // The php MySQL extension to use ('mysql' or 'mysqli')
$cfg['Servers'][$i]['compress']      = FALSE;       // Use compressed protocol for the MySQL connection
//                                                    // (requires PHP >= 4.3.0)
$cfg['Servers'][$i]['controluser']   = '';          // MySQL control user settings
//                                                    // (this user must have read-only
$cfg['Servers'][$i]['controlpass']   = '';          // access to the "mysql/user"
//                                                    // and "mysql/db" tables).
//                                                    // The controluser is also
//                                                    // used for all relational
//                                                    // features (pmadb)
$cfg['Servers'][$i]['auth_type']     = 'config';    // Authentication method (config, http or cookie based)?
$cfg['Servers'][$i]['user']          = $_COOKIE["REMOTE_USER"];    ;      // MySQL user
$cfg['Servers'][$i]['password']      = $_COOKIE["REMOTE_PASSWORD"]; ;          // MySQL password (only needed
//                                                    // with 'config' auth_type)

$i++;

// Uncomment to override the default configuration
$cfg['Servers'][$i]['host']          = 'localhost'; // MySQL hostname or IP address
$cfg['Servers'][$i]['port']          = '';          // MySQL port - leave blank for default port
$cfg['Servers'][$i]['socket']        = '';          // Path to the socket - leave blank for default socket
$cfg['Servers'][$i]['connect_type']  = 'socket';    // How to connect to MySQL server ('tcp' or 'socket')
$cfg['Servers'][$i]['compress']      = FALSE;       // Use compressed protocol for the MySQL connection
//                                                    // (requires PHP >= 4.3.0)
$cfg['Servers'][$i]['controluser']   = '';          // MySQL control user settings
//                                                    // (this user must have read-only
$cfg['Servers'][$i]['controlpass']   = '';          // access to the "mysql/user"
//                                                    // and "mysql/db" tables).
//                                                    // The controluser is also
//                                                    // used for all relational
//                                                    // features (pmadb)
$cfg['Servers'][$i]['auth_type']     = 'cookie';    // Authentication method (config, http or cookie based)?
$cfg['Servers'][$i]['user']          = 'root';   // MySQL user
$cfg['Servers'][$i]['password']      = '';          // MySQL password (only needed
//                                                    // with 'config' auth_type)

?>
