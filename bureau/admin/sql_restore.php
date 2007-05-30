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

include("head.php");
if (!$r=$mysql->get_mysql_details($id)) {
	$error=$err->errstr();
}

?>
</head>
<body>
<h3><?php __("MySQL Databases"); ?></h3>
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p><p>&nbsp;</p>";
	}

if (is_array($r)) {
?>
<h3><?php printf(_("Restore a SQL backup for database %s"),$r["db"]); ?></h3>

<form action="sql_dorestore.php" method="post">
<input type="hidden" name="id" value="<?php echo $id ?>" />
<table cellspacing="0" cellpadding="4">
<tr class="lst2">
	<th><label for="restfile"><?php __("Please choose the filename containing SQL data to be restored."); ?></label></th>
<td><select class="int" id="restfile" name="restfile">
<?php
// Open a known directory, and proceed to read its contents
$dir = getuserpath(). $r['dir'];
if (is_dir($dir)) {
  if ($dh = opendir($dir)) {
    while (($file = readdir($dh)) !== false) {
      if (filetype($dir . '/' . $file) == 'file') {
        echo '<option value="' . $r['dir'] . '/' . $file . '">'. $file . '</option>';
      }
    }
    closedir($dh);
  }
}
?>
</select></td>
</tr>
<tr>
<td colspan="2"><input class="inb" type="submit" name="submit" value="<?php __("Restore my database"); ?>" /></td>
</tr>
</table>
</form>
<?php __("OR");?>
<form action="sql_dorestore.php" method="post">
<input type="hidden" name="id" value="<?php echo $id ?>" />
<table cellspacing="0" cellpadding="4">
<tr class="lst2">
	<th><label for="restfile"><?php __("Please enter the filename containing SQL data to be restored."); ?></label></th>
	<td><input type="text" class="int" id="restfile" name="restfile" size="30" maxlength="255" value="" /></td>
</tr>
<tr>
<td colspan="2"><input class="inb" type="submit" name="submit" value="<?php __("Restore my database"); ?>" /></td>
</tr>
</table>
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
</body>
</html>
