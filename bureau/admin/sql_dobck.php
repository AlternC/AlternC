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
 * Receive the post for the management of the Backup
 * of MySQL database
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */

require_once("../class/config.php");

if ( ! variable_get('sql_allow_users_backups') ) {
  include_once('head.php');
  echo "<p class=\"alert alert-warning\">"._("You aren't allowed to access this page. Contact your administrator if you want to.")."</p>";
  include_once('foot.php');
  exit;
}


$fields = array (
	"id"     => array ("post", "string", ""),
	"bck_mode" => array ("post", "integer", 0),
	"bck_history" => array ("post", "integer", 7),
	"bck_gzip" => array ("post", "integer", 0),
	"bck_dir" => array ("post", "string", "/"),
	
);
getFields($fields);


$mysql->put_mysql_backup($id,$bck_mode,$bck_history,$bck_gzip,$bck_dir); 

if ($msg->has_msgs("ERROR")) {
	include("sql_bck.php");
	exit();
} else {
	$msg->raise("INFO", "mysql", _("Your backup parameters has been successfully changed."));
}
include("sql_list.php");
?>
