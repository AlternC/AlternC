<?php
/*
 $Id: mem_admin.php,v 1.2 2003/06/10 08:18:26 root Exp $
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
 Original Author of file: Benjamin Sonntag <benjamin@octopuce.com>
 Purpose of file: Manage administrators preferences
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$fields = array (
	"admlist"    => array ("request", "string", ""),
);
getFields($fields);

if (!$mem->adminpref($admlist)) {
	$error=$err->errstr();
} else {
	$error=_("Your administrator preferences has been successfully changed.");
}

include_once("head.php");

?>
<h3><?php __("Admin preferences"); ?></h3>
<?php
	if (isset($error) && $error) {
		echo "<p class=\"error\">$error</p>";
	}
?>
<?php include_once("foot.php"); ?>
