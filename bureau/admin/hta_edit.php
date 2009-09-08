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
 Original Author of file: Franck Missoum
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
<?php
	if (!count($r)) {
		echo "<p class=\"error\">".sprintf(_("No authorized user in %s"),$dir)."</p>";
		echo "<a href=\"hta_adduser.php?dir=$dir\">"._("Add a username")."</a><br />";
		echo "<br /><small><a href=\"bro_main.php?R=$dir\">"._("File browser")."</a><br /></small>";
		include_once("foot.php");
		exit();
	}
reset($r);

?>
<form method="post" action="hta_dodeluser.php">
<table cellspacing="0" cellpadding="4">
	<tr>
		<th colspan="2" ><input type="hidden" name="dir" value="<?php echo $dir?>">&nbsp;</th>
		<th><?php __("Username"); ?></th>
	</tr>
<?php
$col=1;

for($i=0;$i<count($r);$i++){
	$col=3-$col;
?>
	<tr class="lst<?php echo $col; ?>">
		<td align="center"><input type="checkbox" class="inc" name="d[]"" value="<?php echo $r[$i]?>" /></td>
		<td><a href="hta_edituser.php?user=<?php echo $r[$i]?>&amp;dir=<?php echo $dir?>"><img src="images/edit.png" alt="<?php __("Edit"); ?>" /></a></td>
		<td><?php echo $r[$i]; ?></td>
	</tr>
<?php
}
?>
<tr><td colspan="3"><input type="submit" class="inb" name="submit" value="<?php __("Delete the checked users"); ?>" /></td></tr>
</table>
</form>

<p>
<a href="hta_adduser.php?dir=<?php echo $dir ?>"><?php __("Add a username"); ?></a>
</p>
<p>
<small><a href="bro_main.php?R=<?php echo $dir ?>"><?php __("File browser"); ?></a></small>
</p>
<?php include_once("foot.php"); ?>