<?php
/*
 $Id: sql_dorestore.php,v 1.4 2004/08/31 14:25:50 anonymous Exp $
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
 Purpose of file: Manage the MySQL Restore
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

include_once("head.php");

if (!$r=$mysql->get_mysql_details($id)) {
        $error=$err->errstr();
}
?>
<h3><?php __("MySQL Databases"); ?></h3>
<hr id="topbar"/>
<br />
<?php
if ($r["enabled"]) {
?>
<h3><?php __("Restore a SQL backup"); ?></h3>
<p>
<?php
if (!$mysql->restore($restfile,true,$id))  {
	$error=$err->errstr();
} else {
	$error=_("Your database has been restored, check out the previous text for error messages.");
}

echo "<p class=\"error\">$error</p><p>&nbsp;</p>";
} else {

__("You currently have no database defined");

}
?>
</p>
<?php include_once("foot.php"); ?>
