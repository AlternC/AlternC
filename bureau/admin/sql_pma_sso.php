<?php
/*
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
*/

/** 
 * Launch a PhpMyAdmin session using PMA's SSO system
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */

require_once("../class/config.php");

$fields = array (
	"id"     => array ("request", "string", ""),
);
getFields($fields);


if ($r=$mysql->php_myadmin_connect()) {
  // SSO of PhpMyAdmin
  $_SESSION['PMA_single_signon_user'] = $r["login"];
  $_SESSION['PMA_single_signon_password'] = $r["pass"];
  $_SESSION['PMA_single_signon_host'] = $r["host"]; // pma >= 2.11
  session_write_close();

  // Destroy any existing phpmyadmin session
  setcookie("phpMyAdmin", "", time() - 3600);

  if ($id!="") {
    $r=$mysql->get_mysql_details($id); 
    if ($r) {
      $db="?db=".$id;
    }
  } else {
    $db="";
  }
  // finally redirect to phpMyAdmin :
  header("Location: /alternc-sql/index.php".$db);
  exit();
}

include_once("head.php");
echo '<h3>'.__("SQL Admin", "alternc", true).'</h3>';

echo $msg->msg_html_all();

include_once("foot.php"); 

?>
