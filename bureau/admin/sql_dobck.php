<?php
/*
 $Id: sql_dobck.php,v 1.3 2003/06/10 07:20:29 root Exp $
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
 Purpose of file: Manage the MySQL Backup
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if ( ! variable_get('sql_allow_users_backups') ) {
  include_once('head.php');
  echo "<p class=\"error\">"._("You aren't allowed to access this page. Contact your administrator if you want to.")."</p>";
  include_once('foot.php');
  exit;
}


$fields = array (
	"id"     => array ("request", "string", ""),
	"bck_mode" => array ("request", "integer", 0),
	"bck_history" => array ("request", "integer", 7),
	"bck_gzip" => array ("request", "integer", 0),
	"bck_dir" => array ("request", "string", "/"),
	
);
getFields($fields);


if (!$mysql->put_mysql_backup($id,$bck_mode,$bck_history,$bck_gzip,$bck_dir)) {
	$error=$err->errstr();
	include("sql_bck.php");
	exit();
} else {
	$error=_("Your backup parameters has been successfully changed.");
}
include("sql_list.php");
?>
