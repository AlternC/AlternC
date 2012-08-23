<?php
/*
 $Id: hta_adduser.php,v 1.6 2006/01/12 01:10:48 anarcat Exp $
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
 Original Author of file: Franck Missoum, Benjamin Sonntag
 Purpose of file: Add a username to a protected folder
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"dir"      => array ("request", "string", ""),
	"user"     => array ("request", "string", ""),
);
getFields($fields);

?>
<h3><?php printf(_("Adding a username in %s"),$dir); ?></h3>
<?php
	if (isset($error) && $error) {
		echo "<p class=\"error\">$error</p>";
	}
?>

<form method="post" action="hta_doadduser.php" name="main" id="main">
<table border="1" cellspacing="0" cellpadding="4">
	<tr>
		<td><input type="hidden" name="dir" value="<?php ehe($dir); ?>" /><?php __("Folder"); ?></td>
		<td><code><?php echo $dir; ?></code></td>
	</tr>
	<tr>
		<td><label for="user"><?php __("Username"); ?></label></td>
		<td><input type="text" class="int" name="user" id="user" value="<?php ehe($user); ?>" size="20" maxlength="64" /></td>
	</tr>
	<tr>
		<td><label for="password"><?php __("Password"); ?></label></td>
		<td><input type="password" class="int" name="password" id="password" value="" size="20" maxlength="64" /></td>
	</tr>
	<tr>
		<td><label for="passwordconf"><?php __("Confirm password"); ?></label></td>
		<td><input type="password" class="int" name="passwordconf" id="passwordconf" value="" size="20" maxlength="64" /></td>
	</tr>
</table>
<br />
<input type="submit" class="inb" value="<?php __("Add this user"); ?>" />
  <input type="button" class="inb" value="<?php __("Cancel"); ?>" onclick="document.location='hta_edit.php?dir=<?php echo urlencode($dir);  ?>';" />
</form>
<script type="text/javascript">
document.forms['main'].user.focus();
</script>
<?php include_once("foot.php"); ?>
