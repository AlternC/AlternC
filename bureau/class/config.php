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

// To enable the display of the alternc debug error, do the following :
// # touch /etc/alternc/alternc_display_php_error
if (file_exists('/etc/alternc/alternc_display_php_error')) {
  ini_set('display_errors', true);
}

session_start();

/*
  Si vous voulez mettre le bureau en maintenance, décommentez le code ci-dessous
  et mettez votre ip dans le IF pour que seule votre ip puisse accéder au bureau : 
*/

/* * /
if (getenv("REMOTE_ADDR")!="127.0.0.1") {
  echo "Le bureau AlternC est en vacances jusqu'a minuit pour maintenance.<br>
Merci de revenir plus tard.";
  exit();
}
/* */

/* Toutes les pages du bureau passent ici. On utilise une sémaphore pour 
   s'assurer que personne ne pourra accéder à 2 pages du bureau en même temps.
*/
// 1. Get a semaphore id for the alternc magic number (18577)
$alternc_sem = sem_get ( 18577 );
// 2. Declare the shutdown function, that release the semaphore
function alternc_shutdown() {
  global $alternc_sem;
  @sem_release( $alternc_sem );
}
// 3. Register the shutdown function 
register_shutdown_function("alternc_shutdown");
// 4. Acquire the semaphore : with that process, 
sem_acquire( $alternc_sem );

if (ini_get("safe_mode")) {
  echo "SAFE MODE IS ENABLED for the web panel ! It's a bug in your php or apache configuration, please fix it !!";
  exit();
}

// For people who want to authenticate with HTTP AUTH
if (isset($_GET['http_auth'])) $http_auth=strval($_GET['http_auth']);
if (isset($http_auth) && $http_auth) {
    if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
        header('WWW-Authenticate: Basic realm="Test Authentication System"');
        header('HTTP/1.0 401 Unauthorized');
	exit();
    }
}
if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
  // Gruiiik
  $_REQUEST["username"]=$_SERVER['PHP_AUTH_USER'];
  $_REQUEST["password"]=$_SERVER['PHP_AUTH_PW'];
 }


$help_baseurl="http://www.aide-alternc.org/";

/* Server Domain Name */
$host=getenv("HTTP_HOST");

/* Global variables (AlternC configuration) */
require_once(dirname(__FILE__)."/local.php");

// Define constants from vars of /etc/alternc/local.sh
// The you can't choose where is the AlternC Panel 
define('ALTERNC_MAIL',     "$L_ALTERNC_MAIL");
define('ALTERNC_HTML',     "$L_ALTERNC_LOC");
define('ALTERNC_LOGS',     "$L_ALTERNC_LOGS");
define('ALTERNC_PANEL',    "/usr/lib/alternc/panel");
define('ALTERNC_LOCALES',  ALTERNC_PANEL."/locales");

/* PHPLIB inclusions : */
$root=ALTERNC_PANEL."/";

require_once($root."/class/db_mysql.php");
require_once($root."/class/functions.php");
require_once($root."class/variables.php");

// Redirection si appel à https://(!fqdn)/
if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]=="on" && $host!=$L_FQDN) {
  header("Location: https://$L_FQDN/");
}


// Classe héritée de la classe db de la phplib.
/**
* Class for MySQL management in the bureau 
*
* This class heriting from the db class of the phplib manages
* the connection to the MySQL database.
*/

class DB_system extends DB_Sql {
  var $Host,$Database,$User,$Password;

  /**
  * Creator
  */
  function DB_system() {
    global $L_MYSQL_HOST,$L_MYSQL_DATABASE,$L_MYSQL_LOGIN,$L_MYSQL_PWD;
    $this->Host     = $L_MYSQL_HOST;
    $this->Database = $L_MYSQL_DATABASE;
    $this->User     = $L_MYSQL_LOGIN;
    $this->Password = $L_MYSQL_PWD;
  }
}

$db= new DB_system();

// Current User ID = the user whose commands are made on behalf of.
$cuid=0;


$classes=array();
/* CLASSES PHP : automatic include : */
$c=opendir($root."class/");
while ($di=readdir($c)) {
  if (preg_match("#^m_(.*)\\.php$#",$di,$match)) { // $
    $name1="m_".$match[1];
    $name2=$match[1];
    $classes[]=$name2;
    require_once($root."class/".$name1.".php");
  }
}
closedir($c);
/* THE DEFAULT CLASSES ARE :
   dom, ftp, mail, quota, bro, admin, mem, mysql, err
*/


/* Language */
include_once("lang_env.php");

$mem=new m_mem();
$err=new m_err();
$authip=new m_authip();
$hooks=new m_hooks();

/* Check the User identity (if required) */
if (!defined('NOCHECK')) {
  if (!$mem->checkid()) {
    $error=$err->errstr();
    include("index.php");
    exit();
  }
} 

for($i=0;$i<count($classes);$i++) {
  $name2=$classes[$i];
  if (isset($$name2)) continue; // for already instancied class like mem, err or authip
  $name1="m_".$name2;
  $$name2= new $name1();
}

$oldid=intval(isset($_COOKIE['oldid'])?$_COOKIE['oldid']:'');
$isinvited=false;
if ($admin->enabled) $isinvited=true;

if ($oldid && $oldid!=$cuid) {
  $isinvited=true;
}

?>
