<?php
/*
 $Id: hta_doedituser.php,v 1.4 2006/01/12 01:10:48 anarcat Exp $
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
 Purpose of file: Change a username / password from a protected folder
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"user"         => array ("request", "string", ""),
	"dir"          => array ("request", "string", ""),
	"newpass"      => array ("request", "string", ""),
	"newpassconf"  => array ("request", "string", ""),
);
getFields($fields);

if ($newpass != $newpassconf) {
	$error = _("Passwords do not match");
	include("hta_edituser.php");
	exit();
}

if (!$hta->change_pass($user,$newpass,$dir)) {
		$error=$err->errstr();
}

?>
<h3><?php printf(_("Change the user %s in the protected folder %s"),$user,$dir); ?></h3>
<hr id="topbar"/>
<br />
<?php
	if (isset($error) && $error) {
		echo "<p class=\"alert alert-danger\">$error</p>";
	}
	else {
		echo "<p>".sprintf(_("The password of the user %s has been successfully changed"),$user)."</p>";
	}
	echo "<p><span class=\"ina\"><a href=\"hta_edit.php?dir=$dir\">"._("Click here to continue")."</a></span></p>";
?>
<?php include_once("foot.php"); ?>
