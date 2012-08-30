<?php

bindtextdomain("changepass", "/var/alternc/bureau/locales");

$do_not_set_lang_env=1;
$root="/var/alternc/bureau/";

global $L_MYSQL_HOST,$L_MYSQL_DATABASE,$L_MYSQL_LOGIN,$L_MYSQL_PWD;

require_once($root."class/local.php");
require_once($root."class/functions.php");
require_once($root."class/m_err.php");

global $er,$fe,$username,$db, $admin, $classes, $mail, $err;

require_once($root."class/db_mysql.php");

if (!class_exists("DB_system")) {

  // Classe héritée de la classe db de la phplib.
  class DB_system extends DB_Sql {
    var $Host,$Database,$User,$Password;
    function DB_system() {
      global $L_MYSQL_HOST,$L_MYSQL_DATABASE,$L_MYSQL_LOGIN,$L_MYSQL_PWD;
      $this->Host     = $L_MYSQL_HOST;
      $this->Database = $L_MYSQL_DATABASE;
      $this->User     = $L_MYSQL_LOGIN;
      $this->Password = $L_MYSQL_PWD;
    }
  }
  $db= new DB_system();
}


$err=new m_err();

require_once($root."class/m_admin.php");
$admin=new m_admin();
$classes[]="admin";
require_once($root."class/m_mail.php");
$mail=new m_mail();
$classes[]="mail";


?>
