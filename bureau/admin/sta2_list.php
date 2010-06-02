<?php
/*
 $Id: sta2_list.php,v 1.3 2005/05/08 20:23:11 arnaud-lb Exp $
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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

include_once("head.php");

?>
<h3><?php __("Raw Statistics List"); ?></h3>
<hr id="topbar"/>
<br />
<?php
	if ($quota->cancreate("sta2")) { ?>
<p>
<span class="ina"><a href="sta2_add_raw.php"><?php __("Create new Raw Statistics (apache)"); ?></a></span>
</p>
<?php  	}

	if ($error) {
		echo "<p class=\"error\">$error</p>";
	}

if (!$r=$sta2->get_list_raw()) {
        $error=$err->errstr();
	echo "<p class=\"error\">$error</p>";
} else {

?>

<form method="post" action="sta2_del_raw.php">
<table class="tlist">
    <tr><th colspan="2"><?php __("Actions"); ?></th><th><?php __("Domain name"); ?></th><th><?php __("Folder"); ?></th><th><?php __("View"); ?></th></tr>
<?php
reset($r);
$col=1;
while (list($key,$val)=each($r))
        {
        $col=3-$col;
?>
        <tr class="lst<?php echo $col; ?>">
                <td><input type="checkbox" class="inc" name="del_<?php echo $val["id"]; ?>" value="<?php echo $val["id"]; ?>" /></td>
	   <td><div class="ina"><a href="sta2_edit_raw.php?id=<?php echo $val["id"] ?>"><img src="images/edit.png" alt="<?php __("Edit"); ?>" alt="<?php __("Edit"); ?>" /><?php __("Edit"); ?></a></div></td>
                <td><?php echo $val["hostname"] ?></td>
                <td><code>/<?php echo $val["folder"] ?></code></td>
                <td><?php
        if ($uv=$bro->viewurl($val["folder"], $val["hostname"].'.log')) echo "<a href=\"$uv\">"._("View")."</a>";
?>&nbsp;</td>
        </tr>
<?php
        }
?>

<tr><td colspan="5"><input type="submit" class="inb" name="submit" value="<?php __("Delete the checked Raw Statistics (apache)"); ?>" /></td></tr>
</table>
</form>


<?php } ?>
<?php include_once("foot.php"); ?>
