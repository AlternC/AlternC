<?php
/*
 $Id: sql_list.php,v 1.8 2006/02/16 16:26:28 benjamin Exp $
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
 Purpose of file: Manage the MySQL database of a member
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$rdb=$mysql->get_dblist();
$r=$mysql->get_userslist();

?>
<h3><?php __("MySQL Databases"); ?></h3>
<hr id="topbar"/>
<br />
<?php
	if (isset($error) && $error) {
		echo "<p class=\"error\">$error</p>";
	}
  if(!$r || empty($r)){
    echo "<p class=\"error\">"._("You have no sql user at the moment.")."</p>";  
  }
?>

<table class="tedit">
	<tr>
<?php __("Your current settings are"); ?> 
</tr><tr>
		<th><?php __("MySQL Server"); ?> : </th>
		<td><code><?php echo $mysql->dbus->HumanHostname; ?></code></td>
	</tr>
</table>
<?php
if($rdb){
?>
<form method="post" action="sql_del.php" name="main" id="main">
<table class="tlist">
   <tr><th>&nbsp;</th><th><?php __("Database"); ?></th><th><?php __("Backup"); ?></th><th><?php __("Restore"); ?></th><th><?php __("Settings"); ?></th><th><?php __("Size"); ?></th></tr>

<?php
$col=1;
for($i=0;$i<count($rdb);$i++) {
  $val=$rdb[$i];
  $val['size'] = $mysql->get_db_size($val['db']);
  $col=3-$col;
?>
	<tr  class="lst<?php echo $col; ?>">
		<td align="center"><input type="checkbox" class="inc" id="del_<?php echo $val["db"]; ?>" name="del_<?php echo $val["db"]; ?>" value="<?php echo ($val["db"]); ?>" /></td>
	   	<td><label for="del_<?php echo $val["db"]; ?>"><?php echo $val["db"]; ?></label></td>
		<td><div class="ina"><a href="sql_bck.php?id=<?php echo $val["db"] ?>"><?php __("Backup"); ?></a></div></td>
		<td><div class="ina"><a href="sql_restore.php?id=<?php echo $val["db"] ?>"><?php __("Restore"); ?></a></div></td>
		<td><div class="ina"><a href="sql_getparam.php?dbname=<?php echo $val["db"] ?>"><?php __("Settings"); ?></a></div></td>
		<td><code><?php echo format_size($val["size"]); ?></code></td>
	</tr>
<?php


 }
?>
<tr><td colspan="5">
   <input type="submit" name="sub" value="<?php __("Delete the checked databases"); ?>" class="inb" />
</td></tr>
</table>
</form>
<?php
}
?>
<p>&nbsp;</p>

<?php if ($quota->cancreate("mysql")) { ?>
<p>  <span class="ina"><a href="sql_add.php"><?php __("Create a new MySQL database"); ?></a></span> </p>
<?php } 
?>
<script type="text/javascript">
document.forms['main'].pass.focus();
document.forms['main'].setAttribute('autocomplete', 'off');
</script>
<?php include_once("foot.php"); ?>
