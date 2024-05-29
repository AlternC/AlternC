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
 * Change the login / password of a protected folder
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"user"         => array ("post", "string", ""),
	"dir"          => array ("post", "string", ""),
	"newpass"      => array ("post", "string", ""),
	"newpassconf"  => array ("post", "string", ""),
);
getFields($fields);

if ($newpass != $newpassconf) {
	$msg->raise("ERROR", "hta", __("Passwords do not match", "alternc", true));
	include("hta_edituser.php");
	exit();
}

if ($hta->change_pass($user,$newpass,$dir)) {
	$msg->raise("INFO", "hta", __("The password of the user %s has been successfully changed", "alternc", true), $user);
	$is_include=true;
	include_once("hta_edit.php");
} else {
	include("hta_edituser.php");
}
?>
