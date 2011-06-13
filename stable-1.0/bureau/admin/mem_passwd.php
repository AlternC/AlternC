<?php
/*
 $Id: mem_passwd.php,v 1.1.1.1 2003/03/26 17:41:29 root Exp $
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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$mem->passwd($oldpass,$newpass,$newpass2)) {
	$error=$err->errstr();
} else {
	$error=_("Your password has been successfully changed.");
}

include_once("head.php");

?>
<div align="center"><h3><?php __("Password change"); ?></h3></div>
<?php
	if ($error) {
		echo "<font color=red>$error</font>";
		include("foot.php");
		exit();
	}
?>
<?php include_once("foot.php"); ?>