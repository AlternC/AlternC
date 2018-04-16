<?php
// *****************************************************************************
// 
// Alternc bootstrapping                  
// bureau/class/config.php file is -not- test friendly
// @todo streamline test and prod 
// 
// *****************************************************************************

// Autoloading 
// ***********
$pathList                               = array_merge( array("."),explode(PATH_SEPARATOR,get_include_path()));
set_include_path(implode(PATH_SEPARATOR, $pathList));
require_once('AutoLoader.php');
AutoLoader::registerDirectory('lib');
AutoLoader::registerDirectory('../bureau/class');
AutoLoader::registerDirectory('.');

define('ALTERNC_PANEL',realpath(__DIR__."/../bureau"));; // Custom
define('PHPUNIT_DATASETS_PATH',realpath(__DIR__."/tests/_datasets"));
require_once ALTERNC_PANEL."/class/db_mysql.php";
require_once ALTERNC_PANEL."/class/functions.php";


// General variables setup
// *********************
if(is_readable('local.sh')){
    $configFile                         = file_get_contents('local.sh');
} else if(is_readable('local.sh_generic')){
    $configFile                         = file_get_contents('local.sh_generic');
} else {
    throw new Exception("You must provide a local.sh file", 1 );
}
$configFile                             = explode("\n",$configFile);
$compat                                 = array('DEFAULT_MX'   => 'MX',
    'MYSQL_USER'   => 'MYSQL_LOGIN',
    'MYSQL_PASS'   => 'MYSQL_PWD',
    'NS1_HOSTNAME' => 'NS1',
    'NS2_HOSTNAME' => 'NS2'
);
foreach ($configFile as $line) {
    if (preg_match('/^([A-Za-z0-9_]*) *= *"?(.*?)"?$/', trim($line), $matches)) {
        //$GLOBALS['L_'.$matches[1]]      = $matches[2];
        eval('$L_'.$matches[1].' = $matches[2];'); # Ugly, but work with phpunit...
        if (isset($compat[$matches[1]])) {
            $GLOBALS['L_'.$compat[$matches[1]]]  =      $matches[2];
        }
    }
}


// Constants and globals
// ********************

// Define constants from vars of local.sh
if( !defined("ALTERNC_MAIL") ) { define('ALTERNC_MAIL', "$L_ALTERNC_MAIL"); };
if( !defined("ALTERNC_HTML") ) { define('ALTERNC_HTML', "$L_ALTERNC_HTML"); };
if( !defined("ALTERNC_LOGS") ) { define('ALTERNC_LOGS', "$L_ALTERNC_LOGS"); };
if(isset($L_ALTERNC_LOGS_ARCHIVE)){
 define('ALTERNC_LOGS_ARCHIVE', "$L_ALTERNC_LOGS_ARCHIVE");
}
if( !defined("ALTERNC_LOCALES") ) { define('ALTERNC_LOCALES', ALTERNC_PANEL."/locales"); };
if( !defined("ALTERNC_LOCK_JOBS") ) { define('ALTERNC_LOCK_JOBS', '/var/run/alternc/jobs-lock'); };
if( !defined("ALTERNC_LOCK_PANEL") ) { define('ALTERNC_LOCK_PANEL', '/var/lib/alternc/panel/nologin.lock'); };
if( !defined("ALTERNC_APACHE2_GEN_TMPL_DIR") ) { define('ALTERNC_APACHE2_GEN_TMPL_DIR', '/etc/alternc/templates/apache2/'); };
if( !defined("ALTERNC_VHOST_DIR") ) { define('ALTERNC_VHOST_DIR', "/var/lib/alternc/apache-vhost/"); };
if( !defined("ALTERNC_VHOST_FILE") ) { define('ALTERNC_VHOST_FILE', ALTERNC_VHOST_DIR."vhosts_all.conf"); };
if( !defined("ALTERNC_VHOST_MANUALCONF") ) { define('ALTERNC_VHOST_MANUALCONF', ALTERNC_VHOST_DIR."manual/"); };
define("THROW_EXCEPTIONS", TRUE);

$root = ALTERNC_PANEL."/";

// Create test directory
foreach (array(ALTERNC_MAIL, ALTERNC_HTML, ALTERNC_LOGS) as $crdir ) {
  if (! is_dir($crdir)) {
    mkdir($crdir, 0777, true);
  }
}

// Database variables setup
// ***********************
// Default values
$database                               = "alternc_test";
$user                                   = "root";
$password                               = "";
// Local override
if ( is_readable("my.cnf") ) {
    $mysqlConfigFile                      = file("my.cnf");
} else if(is_readable('my.cnf_generic')){
    $mysqlConfigFile                      = file('my.cnf_generic');
} else {
    throw new Exception("You must provide a my.cnf file", 1 );
}

foreach ($mysqlConfigFile as $line) {
  if (preg_match('/^([A-Za-z0-9_]*) *= *"?(.*?)"?$/', trim($line), $matches)) {
      switch ($matches[1]) {
      case "user":
        $user                           = $matches[2];
      break;
      case "password":
        $password                       = $matches[2];
      break;
      case "database":
        $database                       = $matches[2];
      break;
    }
  }
  if (preg_match('/^#alternc_var ([A-Za-z0-9_]*) *= *"?(.*?)"?$/', trim($line), $matches)) {
    $$matches[1]                        = $matches[2];
  }
}


/**
* Class for MySQL management in the bureau 
*
* This class heriting from the db class of the phplib manages
* the connection to the MySQL database.
*/
class DB_system extends DB_Sql { 
  function __construct($database, $user, $password) { 
    parent::__construct($database, '127.0.0.1', $user, $password);
  } 
} 

// Creates database from schema 
// *********************************************

echo "*** In progress: importing mysql.sql\n";
$queryList = array(
    "mysql -u $user --password='$password' -e 'DROP DATABASE IF EXISTS $database '",
    "mysql -u $user --password='$password' -e 'CREATE DATABASE $database'",
    "mysql -u $user --password='$password' $database < ".__DIR__."/../install/mysql.sql"
);
foreach ($queryList as $exec_command) {
    exec($exec_command,$output,$return_var);
    if(  $return_var){
        throw new \Exception("[!] Mysql exec error : $exec_command \n Error : \n ".print_r($output,true));
    }
}
echo "*** In progress: mysql.sql imported\n";

$db                                     = new \DB_system($database, $user, $password);
$cuid                                   = 0;
$mem                                    = new \m_mem();
$err                                    = new \m_err();
$authip                                 = new \m_authip();
$hooks                                  = new \m_hooks();
$bro                                    = new \m_bro();
