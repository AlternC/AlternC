<?php


/* Global variables (AlternC configuration) */
require_once("/usr/share/alternc/panel/class/local.php");

// Define constants from vars of /etc/alternc/local.sh
// The you can't choose where is the AlternC Panel 
define('ALTERNC_MAIL',     "$L_ALTERNC_MAIL");
define('ALTERNC_HTML',     "$L_ALTERNC_HTML");
if(isset($L_ALTERNC_LOGS_ARCHIVE))
  define('ALTERNC_LOGS_ARCHIVE',  "$L_ALTERNC_LOGS_ARCHIVE");
define('ALTERNC_LOGS',     "$L_ALTERNC_LOGS");
define('ALTERNC_PANEL',    "/usr/share/alternc/panel");
define('ALTERNC_LOCALES',  ALTERNC_PANEL."/locales");
define('ALTERNC_LOCK_JOBS', '/var/run/alternc/jobs-lock');
define('ALTERNC_LOCK_PANEL', '/var/lib/alternc/panel/nologin.lock');

/* PHPLIB inclusions : */
$root=ALTERNC_PANEL."/";

require_once($root."class/db_mysql.php");
require_once($root."class/functions.php");


global $L_MYSQL_HOST,$L_MYSQL_DATABASE,$L_MYSQL_LOGIN,$L_MYSQL_PWD,$db,$dbh;

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

// we do both: 
$db= new DB_system();
$dbh = new PDO("mysql:host=".$L_MYSQL_HOST.";dbname=".$L_MYSQL_DATABASE, $L_MYSQL_LOGIN,$L_MYSQL_PWD,
	       array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8;")
	       );


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
//include_once("../../class/lang_env.php");

$variables=new m_variables();
$mem=new m_mem();
$err=new m_err();
$authip=new m_authip();
$hooks=new m_hooks();


for($i=0;$i<count($classes);$i++) {
  $name2=$classes[$i];
  if (isset($$name2)) continue; // for already instancied class like mem, err or authip
  $name1="m_".$name2;
  $$name2= new $name1();
}

