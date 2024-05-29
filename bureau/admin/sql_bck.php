<?php
/*
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
*/

/** 
 * Form to manage MySQL database backup for an account
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */

require_once("../class/config.php");
include_once("head.php");

?>
<h3 class="backup"><?php __("MySQL Databases - Configure backups"); ?></h3>
<hr id="topbar"/>
<br />
<?php

if ( ! variable_get('sql_allow_users_backups') ) {
  echo "<p class=\"alert alert-danger\">".__("You aren't allowed to access this page. Contact your administrator if you want to.", "alternc", true)."</p>";
  include_once('foot.php');
  exit;
}

$fields = array (
	"id"     => array ("request", "string", ""),
	"bck_mode" => array ("post", "integer", 0),
	"bck_history" => array ("post", "integer", 7),
	"bck_gzip" => array ("post", "integer", 0),
	"bck_dir" => array ("post", "string", "/"),
	
);
getFields($fields);

$r=$mysql->get_mysql_details($id); 

echo $msg->msg_html_all();

if (is_array($r)) {
?>
<h3><?php printf(__("Manage the SQL backup for database %s", "alternc", true),$r["db"]); ?></h3>

<form action="sql_dobck.php" method="post" id="main" name="main">
 <?php csrf_get(); ?>
<table class="tedit">
<tr>
	<th><label><?php __("Do MySQL backup?"); ?></label></th>
	<td>
        <input type="hidden" name="id" value="<?php ehe($id); ?>" />
        <input type="radio" class="inc" id="bck_mode0" name="bck_mode" value="0"<?php cbox($r["bck"]==0); ?>/><label for="bck_mode0"><?php __("No backup"); ?></label><br />
	<input type="radio" class="inc" id="bck_mode1" name="bck_mode" value="1"<?php cbox($r["bck"]==1); ?>/><label for="bck_mode1"><?php __("Weekly backup"); ?></label><br />
	<input type="radio" class="inc" id="bck_mode2" name="bck_mode" value="2"<?php cbox($r["bck"]==2); ?>/><label for="bck_mode2"><?php __("Daily backup"); ?></label><br />
	</td>
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
	<th><label><?php __("Compress the backups? (gzip)"); ?></label></th>
	<td>

        <input type="radio" class="inc" id="bck_gzip0" name="bck_gzip" value="0"<?php cbox($r["gzip"]==0); ?>/><label for="bck_gzip0"><?php __("No"); ?></label><br />
	<input type="radio" class="inc" id="bck_gzip1" name="bck_gzip" value="1"<?php cbox($r["gzip"]==1); ?>/><label for="bck_gzip1"><?php __("Yes"); ?></label><br />

</td>
</tr>
<tr>
	<th><label for="bck_dir"><?php __("In which folder do you want to store the backups?"); ?></label></th>
	<td><input type="text" class="int" name="bck_dir" id="bck_dir" size="30" maxlength="255" value="<?php ehe($r["dir"]); ?>" />
	<?php display_browser( isset($r["dir"])?$r["dir"]:"" , "bck_dir" ); ?>
</td>
</tr>

<tr class="trbtn"><td colspan="2">
  <input class="inb ok" type="submit" name="submit" value="<?php __("Change the MySQL backup parameters"); ?>" />
  <input type="button" class="inb cancel" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='sql_list.php'"/>
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
<?php include_once("foot.php"); ?>
