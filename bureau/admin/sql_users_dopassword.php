<?php
/*
 $Id: sql_users_rights.php,v 1.8 2006/02/16 16:26:28 nahuel Exp $
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
 Original Author of file: Nahuel ANGELINETTI
 Purpose of file: Manage the MySQL users of a member
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
$fields = array (
	"id"              => array ("request", "string", ""),
	"password"        => array ("request", "string", ""),
	"passwordconf"    => array ("request", "string", ""),
);
getFields($fields);

$mysql->change_user_password($id,$password,$passwordconf);

include("sql_users_list.php");

?>
