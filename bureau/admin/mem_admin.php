<?php
/*
 mem_admin.php
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2002-2017 by the AlternC Development Team.
 https://alternc.com/
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
	"admlist"    => array ("post", "string", ""),
);
getFields($fields);

if ($mem->adminpref($admlist)) {
	$msg->raise('Ok', "mem", _("Your administrator preferences has been successfully changed."));
}

include_once("head.php");

?>
<h3><?php __("Admin preferences"); ?></h3>
<?php
echo $msg->msg_html_all();
echo "<p><span class='ina'><a href='mem_param.php'>"._("Click here to continue")."</a></span></p>";

include_once("foot.php");
?>
