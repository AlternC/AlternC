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
 * Restore a MySQL database for an account
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */

 require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"id"           => array ("request", "string", ""),
	"filename"     => array ("post", "string", ""),
);
getFields($fields);

$r=$mysql->get_mysql_details($id);

?>
<h3><?php __("MySQL Databases"); ?></h3>
<hr id="topbar"/>
<br />
<?php
echo $msg->msg_html_all();

if (!is_array($r)) {
  echo "<p>"._("You currently have no database defined")."</p>";
  include_once("foot.php");
  exit;
}
?>
<h3 class="restore"><?php printf(_("Restore a MySQL backup for database %s"),$r["db"]); ?></h3>
<?php
echo "<p>";
__("Warning: Write the complete path and the filename. <br />For example if your backups are in the directory /Backups,<br />write /Backups/file.sql.gz (where file.sql.gz is the filename).");
echo "</p>";
?>
<form action="sql_dorestore.php" method="post">
  <?php csrf_get(); ?>
<input type="hidden" name="id" value="<?php ehe($id); ?>" />
<p><label for="restfile"><?php __("Please enter the path and the filename containing SQL data to be restored."); ?></label></p>
<p><input type="text" class="int" id="restfile" name="restfile" size="35" maxlength="255" value="<?php ehe($filename); ?>" /> <input class="inb" type="submit" name="submit" onClick='return restfilenotempty();' value="<?php __("Restore my database"); ?>" /><i>
<br /><?php __("Tip: you can restore a file directly in the File Browser");?></i></p>
</form>
<script type="text/javascript">
  function restfilenotempty() {
    if ( $('#restfile').val() == '' ) {
      alert("<?php __("Please the complete path of the filename");?>");
      return false;
    } else {
      return true;
    }
  }
</script>
<?php
echo "<p>";
__("Note: If the filename ends with .gz, it will be uncompressed before.");
echo "</p>";
?>
<?php include_once("foot.php"); ?>
