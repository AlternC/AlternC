<?php
/*
 $Id: sql_bck.php,v 1.8 2003/10/09 00:54:58 root Exp $
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
 Purpose of file: Manage the MySQL Backup
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$r=$mysql->get_mysql_details($id)) {
	$error=$err->errstr();
}

include("head.php");
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
<h3><?php printf(_("Manage the SQL backup for database %s"),$r["db"]); ?></h3>

<form action="sql_dobck.php" method="post" id="main" name="main">
<table cellspacing="0" cellpadding="4">
<tr class="lst2">
	<th><label for="bck_mode"><?php __("Do sql backup?"); ?></label></th>
	<td>
        <input type="hidden" name="id" value="<?php echo $id; ?>" />
        <select class="inl" name="bck_mode" id="bck_mode">
	<option value="0"<?php if ($r["bck"]==0) echo " selected=\"selected\""; ?>><?php __("No backup"); ?></option>
	<option value="1"<?php if ($r["bck"]==1) echo " selected=\"selected\""; ?>><?php __("Weekly backup"); ?></option>
	<option value="2"<?php if ($r["bck"]==2) echo " selected=\"selected\""; ?>><?php __("Daily backup"); ?></option>
	</select></td>
</tr>
<tr class="lst1">
	<th><label for="bck_history"><?php __("How many backup should be kept?"); ?></label></th>
	<td><select class="inl" name="bck_history" id="bck_history">
	<?php
	for($i=1;$i<20;$i++) {
		echo "<option";
		if ($r["history"]==$i) echo " selected=\"selected\"";
		echo ">$i</option>";
	}
	?>
	</select></td>
</tr>
<tr class="lst2">
	<th><label for="bck_gzip"><?php __("Compress the backups? (gzip)"); ?></label></th>
	<td><select class="inl" name="bck_gzip" id="bck_gzip">
	<option value="0"<?php if ($r["gzip"]==0) echo " selected=\"selected\""; ?>><?php __("No"); ?></option>
	<option value="1"<?php if ($r["gzip"]==1) echo " selected=\"selected\""; ?>><?php __("Yes"); ?></option>
	</select></td>
</tr>
<tr class="lst1">
	<th><label for="bck_dir"><?php __("In which folder do you want to store the backups?"); ?></label></th>
	<td><input type="text" class="int" name="bck_dir" id="bck_dir" size="30" maxlength="255" value="<?php echo $r["dir"]; ?>" />
<script type="text/javascript">
<!--
  document.write("&nbsp;<input type=\"button\" name=\"bff\" onclick=\"browseforfolder('main.bck_dir');\" value=\" ... \" class=\"inb\" />");
//  -->
</script>
</td>
</tr>
<tr>
<td colspan="2"><input class="inb" type="submit" name="submit" value="<?php __("Change the SQL backup parameters"); ?>" /></td>
</tr>

</table>
</form>
<?php
	$mem->show_help("sql_bck");
	} else {
  echo "<p>";
__("You currently have no database defined");
 echo "</p>";
	}
?>

</body>
</html>
