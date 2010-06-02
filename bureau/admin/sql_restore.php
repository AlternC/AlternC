<?php
/*
 $Id: sql_restore.php,v 1.5 2003/06/10 13:16:11 root Exp $
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

$fields = array (
	"id"     => array ("request", "string", ""),
);
getFields($fields);

if (!$r=$mysql->get_mysql_details($id)) {
	$error=$err->errstr();
}

?>
<h3><?php __("MySQL Databases"); ?></h3>
<hr id="topbar"/>
<br />
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p><p>&nbsp;</p>";
	}

if (is_array($r)) {
?>
<h3><?php printf(_("Restore a MySQL backup for database %s"),$r["db"]); ?></h3>
<?php
echo "<p>";
__("Warning: Write the complete path and the filename. <br />For example if your backups are in the directory /Backups,<br />write /Backups/file.sql.gz (where file.sql.gz is the filename).");
echo "</p>";
?>
<form action="sql_dorestore.php" method="post">
<input type="hidden" name="id" value="<?php echo $id ?>" />
<p><label for="restfile"><?php __("Please enter the path and the filename containing SQL data to be restored."); ?></label></p>
<p><input type="text" class="int" id="restfile" name="restfile" size="35" maxlength="255" value="" /> <input class="inb" type="submit" name="submit" value="<?php __("Restore my database"); ?>" /></p>
</form>
<?php
echo "<p>";
__("Note: If the filename ends with .gz, it will be uncompressed before.");
echo "</p>";
	} else {

echo "<p>";
__("You currently have no database defined");
 echo "</p>";

	}
?>
<?php include_once("foot.php"); ?>
