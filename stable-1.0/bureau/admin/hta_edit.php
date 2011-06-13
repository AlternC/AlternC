<?php
/*
 $Id: hta_edit.php,v 1.4 2003/06/10 13:16:11 root Exp $
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
 Purpose of file: Edit a protected folder
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"dir"      => array ("request", "string", ""),
);
getFields($fields);

if (!$dir) {
	$error=_("No folder selected!");
} else {
	$r=$hta->get_hta_detail($dir);
	if (!$r) {
		$error=$err->errstr();
	}
}

?>
<h3><?php printf(_("List of authorized user in folder %s"),$dir); ?></h3>
<hr id="topbar"/>
<br />
<?php
	if (!count($r)) {
		echo "<p class=\"error\">".sprintf(_("No authorized user in %s"),$dir)."</p>";
	} else {
	  reset($r);
	  
?>
<form method="post" action="hta_dodeluser.php">
<table cellspacing="0" cellpadding="4">
	<tr>
   <th colspan="2" ><input type="hidden" name="dir" value="<?php echo $dir?>"> </th>
		<th><?php __("Username"); ?></th>
	</tr>
<?php
$col=1;

for($i=0;$i<count($r);$i++){
	$col=3-$col;
?>
	<tr class="lst<?php echo $col; ?>">
		<td align="center"><input type="checkbox" class="inc" name="d[]" value="<?php echo $r[$i]?>" /></td>
		<td>
<div class="ina"><a href="hta_edituser.php?user=<?php echo $r[$i]?>&amp;dir=<?php echo $dir?>"><img src="icon/encrypted.png" alt="<?php __("Change this user's password"); ?>" /><?php __("Change this user's password"); ?></a></div>
</td>
		<td><?php echo $r[$i]; ?></td>
	</tr>
<?php
}
?>
</table>
<br />
<input type="submit" class="inb" name="submit" value="<?php __("Delete the checked users"); ?>" />
</form>

<?php } ?>
<p>
<span class="inb"><a href="bro_main.php?R=<?php echo $dir ?>"><?php __("Show this folder's content in the File Browser"); ?></a></span>
</p>

<p>&nbsp;</p>

<hr />
<p><?php __("Adding an authorized user"); ?></p>

<form method="post" action="hta_doadduser.php" name="main" id="main">
<table class="tedit">
	<tr>
		<td><input type="hidden" name="dir" value="<?php echo $dir ?>" /><?php __("Folder"); ?></td>
		<td><code><?php echo $dir; ?></code></td>
	</tr>
	<tr>
		<td><label for="user"><?php __("Username"); ?></label></td>
		<td><input type="text" class="int" name="user" id="user" value="" size="20" maxlength="64" /></td>
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
</form>
<script type="text/javascript">
document.forms['main'].user.focus();
document.forms['main'].setAttribute('autocomplete', 'off'); 
</script>

<?php include_once("foot.php"); ?>
