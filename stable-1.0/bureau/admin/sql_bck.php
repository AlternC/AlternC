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
include_once("head.php");

$fields = array (
	"id"     => array ("request", "string", ""),
	"bck_mode" => array ("request", "integer", 0),
	"bck_history" => array ("request", "integer", 7),
	"bck_gzip" => array ("request", "integer", 0),
	"bck_dir" => array ("request", "string", "/"),
	
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
<h3><?php printf(_("Manage the SQL backup for database %s"),$r["db"]); ?></h3>

<form action="sql_dobck.php" method="post" id="main" name="main">
<table class="tedit">
<tr>
	<th><label for="bck_mode"><?php __("Do MySQL backup?"); ?></label></th>
	<td>
        <input type="hidden" name="id" value="<?php echo $id; ?>" />
        <input type="radio" class="inc" id="bck_mode0" name="bck_mode" value="0"<?php cbox($r["bck"]==0); ?>><label for="bck_mode0"><?php __("No backup"); ?></label><br />
	<input type="radio" class="inc" id="bck_mode1" name="bck_mode" value="1"<?php cbox($r["bck"]==1); ?>><label for="bck_mode1"><?php __("Weekly backup"); ?></label><br />
	<input type="radio" class="inc" id="bck_mode2" name="bck_mode" value="2"<?php cbox($r["bck"]==2); ?>><label for="bck_mode2"><?php __("Daily backup"); ?></label><br />
	</select></td>
</tr>
<tr>
	<th><label for="bck_history"><?php __("How many backups should be kept?"); ?></label></th>
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
<tr>
	<th><label for="bck_gzip"><?php __("Compress the backups? (gzip)"); ?></label></th>
	<td>

        <input type="radio" class="inc" id="bck_gzip0" name="bck_gzip" value="0"<?php cbox($r["gzip"]==0); ?>><label for="bck_gzip0"><?php __("No"); ?></label><br />
	<input type="radio" class="inc" id="bck_gzip1" name="bck_gzip" value="1"<?php cbox($r["gzip"]==1); ?>><label for="bck_gzip1"><?php __("Yes"); ?></label><br />

</td>
</tr>
<tr>
	<th><label for="bck_dir"><?php __("In which folder do you want to store the backups?"); ?></label></th>
	<td><input type="text" class="int" name="bck_dir" id="bck_dir" size="30" maxlength="255" value="<?php ehe($r["dir"]); ?>" />
<script type="text/javascript">
<!--
  document.write("&nbsp;<input type=\"button\" name=\"bff\" class=\"bff\" onclick=\"browseforfolder('main.bck_dir');\" value=\" <?php __("Choose a folder..."); ?> \" />");
//  -->
</script>
</td>
</tr>

<tr class="trbtn"><td colspan="2">
  <input class="inb" type="submit" name="submit" value="<?php __("Change the MySQL backup parameters"); ?>" />
  <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='sql_list.php'"/>
</td></tr>
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
<script type="text/javascript">
document.forms['main'].bck_mode.focus();
</script>
<?php include_once("foot.php"); ?>
