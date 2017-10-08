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
 * Manages the MySQL users of an account
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
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
echo $msg->msg_html_all(true, true);

if($r){ // if there is some userlist
?>
<form method="post" action="sql_users_del.php">
      <?php csrf_get(); ?>
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
  } else {
   $msg->raise("INFO", "mysql", _("You have no sql user at the moment."));
   echo $msg->msg_html_all();
  }
?>
  <span class="ina add"><a href="sql_users_add.php"><?php __("Create a new MySQL user"); ?></a></span>
<?php include_once("foot.php"); ?>
