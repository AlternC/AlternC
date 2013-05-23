<?php
/*
 $Id: hta_list.php,v 1.5 2003/08/20 13:08:28 root Exp $
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
 Purpose of file: List the users in a protected folder
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

if (!$r=$hta->ListDir()) {
	$error=$err->errstr();
} else {
	reset($r);
}

?>
<h3><?php __("Protected folders list"); ?></h3>
<hr id="topbar"/>
<br />
<?php
	if (isset($error) && $error) {
		echo "<p class=\"error\">$error</p>";
	}

	if (!is_array($r)) {
		echo "<p><span class=\"ina\"><a href=\"hta_add.php\">"._("Protect a folder")."</a></span><br />";
		$mem->show_help("hta_list");
		echo "</p>";
		include_once("foot.php");
		exit();
	}

?>

<p>
<?php 
__("You can set passwords to protect some of your folders.<br/>This will create .htaccess and .htpasswd files that restrict access to these directory and to any sub-elements.");
// __("help_hta_list");
$mem->show_help("hta_list2");
?>
</p>

<form method="post" action="hta_del.php">
<table class="tlist">
  <tr><th colspan="2"> </th><th><?php __("Folder"); ?></th></tr>
<?php


for($i=0;$i<count($r);$i++){
?>
	<tr  class="lst">
		<td align="center"><input type="checkbox" class="inc" name="del_<?php echo $r[$i] ?>" value="<?php echo $r[$i] ?>" /></td>
		<td>
<div class="ina lock"><a href="hta_edit.php?dir=<?php echo $r[$i]?>"><?php __("Edit login and passwords"); ?></a></div>
</td>
		<td><?php echo '<a href="bro_main.php?R='.urlencode($r[$i]).'">'.htmlspecialchars($r[$i]).'</a>'; ?></td>
	</tr>
    <?php
	}
?>
</table>
<br />
<input type="submit" class="ina up" name="submit" value="<?php __("Unprotect the checked folders"); ?>" />
			<span class="ina add"><a href="hta_add.php"><?php __("Protect a folder"); ?></a></span>
</form>

<p>
<?php $mem->show_help("hta_list"); ?>
</p>
<?php include_once("foot.php"); ?>
