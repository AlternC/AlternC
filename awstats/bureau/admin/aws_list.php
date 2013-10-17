<?php
/*
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2000-2012 by the AlternC Development Team. 
 https://alternc.org/ 
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
 Purpose of file: List awstats statistics and manage them.
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");
?>

<h3><?php __("Statistics List"); ?></h3>
<hr id="topbar"/>
<br />
<?php if (!empty($error)) { echo "<p class=\"error\">$error</p>"; $error=''; } ?>
<p>
<?php

$nosta=false;
if (!$r=$aws->get_list()) {
	$error=$err->errstr();
	$nosta=true;
}

if (!empty($error)) { echo "<p class=\"error\">$error</p>"; $error=''; } 
?>

<span class="ina"><a href="aws_users.php"><?php __("Manage allowed users' accounts"); ?></a></span><br /><br />

<?php
if ($quota->cancreate("aws")) { ?>
  <span class="ina"><a href="aws_add.php"><?php __("Create new Statistics"); ?></a></span><br />
<?php } // cancreate ?>
</p>

<?php if (!$nosta) { ?>

<form method="post" action="aws_del.php">
<table cellspacing="0" cellpadding="4">
    <tr><th colspan="2"><?php __("Action"); ?></th><th><?php __("Domain name"); ?></th><th><?php __("Allowed Users"); ?></th><th><?php __("View the statistics"); ?></th></tr>
<?php
reset($r);
$col=1;
while (list($key,$val)=each($r)) {
	$col=3-$col;
?>
	<tr class="lst<?php echo $col; ?>">
		<td><input type="checkbox" class="inc" id="del_<?php echo $val["id"]; ?>" name="del_<?php echo $val["id"]; ?>" value="<?php echo $val["id"]; ?>" /></td>
	   <td><div class="ina"><a href="aws_edit.php?id=<?php echo $val["id"] ?>"><img src="images/edit.png" alt="<?php __("Edit"); ?>" title="<?php __("Edit"); ?>" /><?php __("Edit"); ?></a></div></td>
		<td class='retour-auto'><label for="del_<?php echo $val["id"]; ?>" ><?php echo $val["hostname"] ?></label></td>
		<td><?php echo $val["users"] ?></td>
		<td><div class="ina"><a href="/cgi-bin/awstats.pl?config=<?php echo $val["hostname"]; ?>" target="_blank" ><img src="images/stat.png" alt="<?php __("View the statistics"); ?>" /><?php __("View the statistics"); ?></a></div></td>
	</tr>
<?php } // while ?>

<tr><td colspan="5"><input type="submit" class="inb" name="submit" onClick='return confirm("<?php __("Are you sure you want to delete the selected statistics?");?>");' value="<?php __("Delete the checked Statistics"); ?>" /></td></tr>
</table>
</form>
<?php
} // if !nosta

include_once("foot.php"); 
?>
