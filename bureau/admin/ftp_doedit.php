<?php
/*
 $Id: ftp_doedit.php,v 1.3 2006/01/12 01:10:48 anarcat Exp $
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
 Purpose of file: Editing an ftp account
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$id) {
	$error=_("No account selected!");
} else {
	if ($pass != $passconf) {
        	$error = _("Passwords do not match");
        	include("ftp_edit.php");
        	exit();
	}

	$r=$ftp->put_ftp_details($id,$prefixe,$login,$pass,$dir);
	if (!$r) {
		$error=$err->errstr();
		include("ftp_edit.php");
		exit();
	} else {
		$error=_("The ftp account has been successfully changed");
		include("ftp_list.php");
		exit();
	}
}

include_once("head.php");

?>
<div align="center"><h3><?php __("Editing an FTP account"); ?></h3></div>
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p>";
	}
?>
<?php include_once("foot.php"); ?>
