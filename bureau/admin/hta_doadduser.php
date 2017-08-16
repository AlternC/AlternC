 <?php
/*
 $Id: hta_doadduser.php,v 1.2 2006/01/12 01:10:48 anarcat Exp $
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
 Original Author of file: Franck Missoum
 Purpose of file: Add a username to a protected folder
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
$fields = array (
        "dir"		=> array ("post", "string", ""),
        "user"		=> array ("post", "string", ""),
        "password"	=> array ("post", "string", ""),
        "passwordconf"	=> array ("post", "string", ""),
);
getFields($fields);


if ($password != $passwordconf) {
	$msg->raise("Error", "hta", _("Passwords do not match"));
	include("hta_adduser.php");
	exit();
}

if (!$hta->add_user($user, $password, $dir)) {
	include ("hta_adduser.php");
} else {
	$msg->raise("Ok", "hta", _("The user %s was added to th protected folder %s"), array($user, $dir)); // à traduire
	include ("hta_edit.php");
}
?>
