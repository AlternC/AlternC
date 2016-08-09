<?php

/*
  $Id: config.php,v 1.12 2005/12/18 09:51:32 benjamin Exp $
  ----------------------------------------------------------------------
  AlternC - Web Hosting System
  Copyright (C) 2002 by the AlternC Development Team.
  http://alternc.org/
  ----------------------------------------------------------------------
  Based on:
  Valentin Lacambre's web hosting softwares: http://altern.org/
  ----------------------------------------------------------------------
  LICENSE

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License (GPL)
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  To read the license please visit http://www.gnu.org/copyleft/gpl.html
  ----------------------------------------------------------------------
  Original Author of file: Benjamin Sonntag
  Purpose of file: General configuration file for AlternC Desktop
  ----------------------------------------------------------------------
 */

define('DO_XHPROF_STATS', FALSE);
if (DO_XHPROF_STATS) {
    require_once('/usr/share/alternc/panel/admin/xhprof_header.php');
}

// To enable the display of the alternc debug error, do the following :
// # touch /etc/alternc/alternc_display_php_error
if (file_exists('/etc/alternc/alternc_display_php_error')) {
    ini_set('display_errors', '1');
}
session_name('AlternC_Panel');
session_start();

/*
  Si vous voulez mettre le bureau en maintenance, decommentez le code ci-dessous
  et mettez votre ip dans le IF pour que seule votre ip puisse acceder au bureau :
 */

/* * /
  if (getenv("REMOTE_ADDR")!="127.0.0.1") {
  echo "Le bureau AlternC est en vacances jusqu'a minuit pour maintenance.<br>
  Merci de revenir plus tard.";
  exit();
  }
  /* */

if (ini_get("safe_mode")) {
    echo _("SAFE MODE IS ENABLED for the web panel ! It's a bug in your php or apache configuration, please fix it !!");
    exit();
}

// For people who want to authenticate with HTTP AUTH
if (isset($_GET['http_auth']))
    $http_auth = strval($_GET['http_auth']);
if (isset($http_auth)) {
    if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
        header('WWW-Authenticate: Basic realm="AlternC Authentication"');
        header('HTTP/1.0 401 Unauthorized');
        exit();
    }
}
if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
    // Gruiiik
    $_REQUEST["username"] = $_SERVER['PHP_AUTH_USER'];
    $_REQUEST["password"] = $_SERVER['PHP_AUTH_PW'];
}

// proper srand (not using time(), which is what PHP does!)
list($usec, $sec) = explode(" ", microtime());
mt_srand($usec * 1000000);

$help_baseurl = "http://www.aide-alternc.org/";

/* Server Domain Name */
$host = getenv("HTTP_HOST");

/* Global variables (AlternC configuration) */
require_once(dirname(__FILE__) . "/local.php");

// Define constants from vars of /etc/alternc/local.sh
// The you can't choose where is the AlternC Panel 
define("DEFAULT_PASS_SIZE", 8);
define('ALTERNC_MAIL', "$L_ALTERNC_MAIL");
define('ALTERNC_HTML', "$L_ALTERNC_HTML");
if (isset($L_ALTERNC_LOGS_ARCHIVE)) {
    define('ALTERNC_LOGS_ARCHIVE', "$L_ALTERNC_LOGS_ARCHIVE");
}
define('ALTERNC_LOGS', "$L_ALTERNC_LOGS");
define('ALTERNC_PANEL', "/usr/share/alternc/panel");
define('ALTERNC_LOCALES', ALTERNC_PANEL . "/locales");
define('ALTERNC_LOCK_JOBS', '/var/run/alternc/jobs-lock');
define('ALTERNC_LOCK_PANEL', '/var/lib/alternc/panel/nologin.lock');
define('ALTERNC_APACHE2_GEN_TMPL_DIR', '/etc/alternc/templates/apache2/');
define('ALTERNC_VHOST_DIR', "/var/lib/alternc/apache-vhost/");
define('ALTERNC_VHOST_FILE', ALTERNC_VHOST_DIR . "vhosts_all.conf");
define('ALTERNC_VHOST_MANUALCONF', ALTERNC_VHOST_DIR . "manual/");


/* PHPLIB inclusions : */
$root = ALTERNC_PANEL . "/";

require_once($root . "/class/db_mysql.php");
require_once($root . "/class/functions.php");
require_once($root . "/class/variables.php");


// Classe h�rit�e de la classe db de la phplib. 
/** 
 * Class for MySQL management in the bureau  
 * 
 * This class heriting from the db class of the phplib manages 
 * the connection to the MySQL database. 
 */ 
  
class DB_system extends DB_Sql { 
  function __construct() { 
      global $L_MYSQL_HOST,$L_MYSQL_DATABASE,$L_MYSQL_LOGIN,$L_MYSQL_PWD; 
      parent::__construct($L_MYSQL_DATABASE, $L_MYSQL_HOST, $L_MYSQL_LOGIN, $L_MYSQL_PWD);
   } 
} 

$db = new DB_system();
// $db = new Sql($L_MYSQL_DATABASE, $L_MYSQL_HOST, $L_MYSQL_LOGIN, $L_MYSQL_PWD);

// Current User ID = the user whose commands are made on behalf of.
$cuid = 0;


$classes = array();
/* CLASSES PHP : automatic include : */
foreach (glob($root . "class/m_*.php") as $di) {
    if (preg_match("#${root}class/m_(.*)\\.php$#", $di, $match)) { // $
        $classes[] = $match[1];
        require_once($di);
    }
}
/* THE DEFAULT CLASSES ARE :
  dom, ftp, mail, quota, bro, admin, mem, mysql, err, variables
 */

// Load file for the system class.
// Those class will not be build by default.
// They may contain forbidden action for the panel, for example: exec, system
// or files operations
// We can imagine load those class only for command-line scripts.
foreach (glob($root . "class/class_system_*.php") as $fcs) {
    if (is_readable($fcs))
        require_once($fcs);
}

/* Language */
include_once("lang_env.php");

$mem = new m_mem();
$err = new m_err();
$authip = new m_authip();
$hooks = new m_hooks();


// https: Redirection if not calling https://!fqdn or if https is forced
if ((variable_get('force_https', '0', "This variable is set to 0 (default) if users can access the management desktop through HTTP, otherwise we force HTTPS")&&(!isset($_SERVER["HTTPS"])|| ($_SERVER["HTTPS"] != "on")))) {
    // do not redirect if access is not by HTTP(s)
    if (isset($_SERVER['REQUEST_URI'])) {
      header("Location: https://$L_FQDN".$_SERVER['REQUEST_URI']);
      exit;
    }
}

// CHECK CSRF for ALL POSTS : 
// you MUST add csrf_get(); after ALL <form method="post"> in AlternC !

$fatalcsrf=false;
if (count($_POST) && !defined("NOCSRF")) {
  if (csrf_check()<=0) {
    $error=$err->errstr();
    // We will trigger the error LATER in the code => need initialization of classes
    $fatalcsrf=true;
  }
}

/* Check the User identity (if required) */
if (!defined('NOCHECK')) {
    if (!$mem->checkid()) {
        if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
            header('WWW-Authenticate: Basic realm="AlternC Authentication"');
            header('HTTP/1.0 401 Unauthorized');
            exit();
        }
        $error = $err->errstr();
        include("$root/admin/index.php");
        exit();
    }
}

for ($i = 0; $i < count($classes); $i++) {
    $name2 = $classes[$i];
    if (isset($$name2))
        continue; // for already instancied class like mem, err or authip
    $name1 = "m_" . $name2;
    $$name2 = new $name1();
}

$oldid = intval(isset($_COOKIE['oldid']) ? $_COOKIE['oldid'] : '');
$isinvited = false;
if ($admin->enabled)
    $isinvited = true;

if ($oldid && $oldid != $cuid) {
    $isinvited = true;
}

// Init some vars
variable_get('hosting_tld', '', 'This is a FQDN that designates the main hostname of the service. For example, hosting_tld determines in what TLD the "free" user domain is created. If this is set to "example.com", a checkbox will appear in the user creation dialog requesting the creator if he wants to create the domain "username.example.com".', array('desc' => 'Wanted FQDN', 'type' => 'string'));

variable_get('subadmin_restriction', '0', "This variable sets the way the account list works for accounts other than 'admin' (2000). 0 (default) = admin other than admin/2000 can see their own account, but not the other one 1 = admin other than admin/2000 can see any account by clicking the ''show all accounts'' link.", array('desc' => 'Shared access activated?', 'type' => 'boolean'));

variable_get('auth_ip_ftp_default_yes', '1', "This variable sets if you want to allow all IP address to access FTP by default. If the user start to define some IP or subnet in the allow list, only those he defined will be allowed.", array('desc' => 'Allow by default?', 'type' => 'boolean'));

if ((variable_get('sql_max_username_length', NULL)==NULL)||(variable_get('sql_max_database_length', NULL)==NULL)) {
    $result = $db->query("SELECT (SELECT CHARACTER_MAXIMUM_LENGTH length FROM information_schema.columns  WHERE TABLE_SCHEMA='mysql' and TABLE_NAME='user' and COLUMN_NAME='User') username, (SELECT CHARACTER_MAXIMUM_LENGTH length FROM information_schema.columns  WHERE TABLE_SCHEMA='mysql' and TABLE_NAME='db' and COLUMN_NAME='Db') `database`");
    if ($db->next_record($result)) {
        $variable = $db->Record;
	$variable['username']=min(128, $variable['username']);
	$variable['database']=min($variable['database'], $variable['username']);
	variable_set('sql_max_username_length', $variable['username'], 'Maximum length allowed for SQL usernames by your SQL server (do not modify unless you know what you do)');
	variable_set('sql_max_database_length', $variable['database'], 'Maximum length allowed for SQL database names by your SQL server (do not modify unless you know what you do)');
    }

}

if ($fatalcsrf) {
    require_once("main.php");
    exit();
}

