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

<?php
if($rdb){
?>
<form method="post" action="sql_del.php" name="main" id="main">
<table class="tlist">
   <tr><th>&nbsp;</th><th><?php __("Database"); ?></th><?php if ( variable_get('sql_allow_users_backups') ) { ?><th><?php __("Backup"); ?></th><?php } // sql_allow_users_backups ?><th><?php __("Restore"); ?></th><th><?php __("Show Settings"); ?></th><th><?php __("Size"); ?></th></tr>

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
<?php if ( variable_get('sql_allow_users_backups') ) { ?>
		<td><div class="ina down"><a href="sql_bck.php?id=<?php echo $val["db"] ?>"><?php __("Backup"); ?></a></div></td>
<?php } // sql_allow_users_backups ?>
		<td><div class="ina up"><a href="sql_restore.php?id=<?php echo $val["db"] ?>"><?php __("Restore"); ?></a></div></td>
		<td><div class="ina configure"><a href="sql_getparam.php?dbname=<?php echo $val["db"] ?>"><?php __("Show Settings"); ?></a></div></td>
		<td><code><?php echo format_size($val["size"]); ?></code></td>
	</tr>
<?php


 }
?>
<tr><td colspan="5">
   <input type="submit" name="sub" value="<?php __("Delete the checked databases"); ?>" class="inb delete" />
</td></tr>
</table>
</form>
<?php
}
?>
<p>&nbsp;</p>

<?php if ($quota->cancreate("mysql")) {
  $q=$quota->getquota("mysql");
  if($q['u'] == 0 ){
?>
<p>  <span class="ina"><a href="sql_doadd.php"><?php __("Create a new MySQL database"); ?></a></span> </p>
<?php }else{
?>
<form method="post" action="sql_doadd.php" id="main2" name="main2">
<table class="tedit">
<tr>
  <th><label for="dbn"><?php __("MySQL Database"); ?></label></th>
  <td>
	<span class="int" id="dbnpfx"><?php echo $mem->user["login"]; ?>_</span><input type="text" class="int" name="dbn" id="dbn" value="" size="20" maxlength="30" />
  </td>
</tr>
</table>
<br />
<input type="submit" class="inb add" name="submit" value="<?php __("Create this new MySQL database."); ?>" onClick="return false_if_empty('dbn', '<?php echo addslashes(_("Can't have empty MySQL suffix"));?>');" />
</form>
<?php
}
}
?>
<script type="text/javascript">
//document.forms['main'].pass.focus();
//document.forms['main'].setAttribute('autocomplete', 'off');
</script>
<?php include_once("foot.php"); ?>
