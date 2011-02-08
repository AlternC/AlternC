<?php
/*
 $Id: sql_users_rights.php,v 1.8 2006/02/16 16:26:28 nahuel Exp $
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

$fields = array (
	"id" => array ("request", "string", ""),
);
getFields($fields);

$r=$mysql->get_user_dblist($id);

?>
<h3><?php __("Change this user's password"); echo " - ".$mem->user["login"]."_".$id ?></h3>
<hr id="topbar"/>
<br />
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p><p>&nbsp;</p>";
	}

?>

<form method="post" action="sql_users_dopassword.php">
<input type="hidden" name="id" value="<?php echo $id; ?>" />
<table cellspacing="0" cellpadding="4">
  <tr>
    <td><label for="password"><?php __("Password"); ?></label></td>
    <td><input type="password" class="int" name="password" id="password" value="" size="20" maxlength="64" /></td>
  </tr>
  <tr>
    <td><label for="passwordconf"><?php __("Confirm password"); ?></label></td>
    <td><input type="password" class="int" name="passwordconf" id="passwordconf" value="" size="20" maxlength="64" /></td>
  </tr>
  <tr>
    <td>
      <input type="submit" class="inb" value="<?php __("Change user password"); ?>" />
      <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='sql_users_list.php'"/>
    </td>
  </tr>
</table>
</form>

<?php include_once("foot.php"); ?>
