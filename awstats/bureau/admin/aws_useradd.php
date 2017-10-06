<?php
/*
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2000-2012 by the AlternC Development Team. 
 https://alternc.org/ 
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
 Purpose of file: Create a new awstat account
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$fields = array (
	"prefixe"  => array ("request", "string", ""),
	"login"    => array ("request", "string", ""),
	"pass"     => array ("request", "string", ""),
	"passconf" => array ("request", "string", "")
);
getFields($fields);

if ($pass != $passconf) {
        $msg->raise('Error', "aws", _("Passwords do not match"));
}else{
	$r=$aws->add_login($prefixe.(($login)?"_":"").$login,$pass);

	if ($r) {
		$msg->raise('Ok', "aws", _("The Awstat account has been successfully created"));
	}
}

include("aws_users.php");
exit();

?>
