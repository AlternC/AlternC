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

/* Toutes les pages du bureau passent ici. On utilise une sémaphore pour 
   s'assurer que personne ne pourra accéder à 2 pages du bureau en même temps.
*/

/*
  Si vous voulez mettre le bureau en maintenance, décommentez le code ci-dessous
  et mettez votre ip dans le IF pour que seule votre ip puisse accéder au bureau : 
*/

/* // Uncomment the following lines and put your IP between the "" to put the dekstop in maintenance mode : 
if (getenv("REMOTE_ADDR")!="81.56.98.108") {
  echo "Le bureau AlternC est en vacances jusqu'a minuit pour maintenance.<br>
Merci de revenir plus tard.";
  exit();
}
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

if (!get_magic_quotes_gpc()) {
  echo "MAGIC QUOTES GPC IS DISABLED ! It's a bug in your php4 configuration, please fix it !!";
  exit();
}



/* PHPLIB inclusions : */
$root="/var/alternc/bureau/";
/* Server Domain Name */
$host=getenv("HTTP_HOST");

/* Global variables (AlternC configuration) */
require_once($root."class/local.php");

require_once($root."class/db_mysql.php");
require_once($root."class/functions.php");
require_once($root."class/variables.php");

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

if (!$_SERVER["HTTPS"]) {
  $conf=variable_init();
  if ($conf["force_https"]) {
    header("Location: https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
  }
}

$classes=array();
/* CLASSES PHP4 : automatic include : */
$c=opendir($root."class/");
while ($di=readdir($c)) {
  if (ereg("^m_(.*)\\.php$",$di,$match)) { // $
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
bindtextdomain("alternc", "/var/alternc/bureau/locales");

if (!$do_not_set_lang_env) {
  include("lang_env.php");
}

$mem=new m_mem();
$err=new m_err();

/* Check the User identity (if required) */
if (!defined('NOCHECK')) {
  if (!$mem->checkid()) {
    $error=$err->errstr();
    include("index.php");
    exit();
  }
} 

for($i=0;$i<count($classes);$i++) {
  if ($classes[$i]!="mem" && $classes[$i]!="err") {
    $name2=$classes[$i];
    $name1="m_".$name2;
    $$name2= new $name1();
  }
}

?>
