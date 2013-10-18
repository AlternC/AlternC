<?php
/*
 $Id: sql_users_list.php,v 1.8 2006/02/16 16:26:28 nahuel Exp $
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
 Original Author of file: Nahuel ANGELINETTI
 Purpose of file: Manage the MySQL users of a member
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$r=$mysql->get_userslist();
$rdb=$mysql->get_dblist();

?>
<h3><?php __("MySQL Users"); ?></h3>
<hr id="topbar"/>
<br />
<?php
if (isset($error) && $error) {
	echo "<p class=\"alert alert-danger\">$error</p><p>&nbsp;</p>";
}
  if($r){ // if there is some userlist
?>
<form method="post" action="sql_users_del.php">
<table cellspacing="0" cellpadding="4" class="tlist">
   <tr><th>&nbsp;</th><th><?php __("User"); ?></th><th><?php __("Rights"); ?></th><th><?php __("Password");?></th></tr>
<?php
for($i=0;$i<count($r);$i++) {
  $val=$r[$i];
?>
	<tr class="lst">
	  <td align="center">
            <input type="checkbox" class="inc" id="del_<?php echo $val["name"]; ?>" name="del_<?php echo $val["name"]; ?>" value="<?php echo $val["name"]; ?>" />
          </td>
	  <td><label for="del_<?php echo $val["name"]; ?>"><?php echo $val["name"]; ?></label></td>
	  <td><span class="ina configure"><a href="sql_users_rights.php?id=<?php echo $val["name"] ?>"><?php __("Manage the rights"); ?></a></span></td>
	  <td><span class="ina lock"><a href="sql_users_password.php?id=<?php echo $val["name"] ?>"><?php __("Password change"); ?></a></span></td>
	</tr>
<?php


 }
?>

</table>

<br/>
<input type="submit" name="sub" value="<?php __("Delete the checked users"); ?>" class="inb delete" />
</form>

<br/>
<br/>

<?php
  } // if $r
?>
  <span class="ina add"><a href="sql_users_add.php"><?php __("Create a new MySQL user"); ?></a></span>
<?php include_once("foot.php"); ?>
