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
?>
<h3><?php __("Change this user's password"); echo " - ".$id ?></h3>
<hr id="topbar"/>
<br />
<?php
$r=$mysql->get_user_dblist($id);
if (!$r) {
  $error=$err->errstr();
}

if (! empty($error) ) {
  echo "<p class=\"alert alert-danger\">$error</p>";
  require_once('foot.php');
  die();
}

?>

<form method="post" action="sql_users_dopassword.php" autocomplete="off">
<input type="hidden" name="id" value="<?php echo $id; ?>" />

<!-- honeypot fields -->
<input type="text" style="display: none" id="fakeUsername" name="fakeUsername" value="" />
<input type="password" style="display: none" id="fakePassword" name="fakePassword" value="" />

<table cellspacing="0" cellpadding="4" class="tedit">
  <tr>
    <th><label for="password"><?php __("Password"); ?></label></th>
    <td><input type="password" class="int" autocomplete="off" name="password" id="password" value="" size="20" maxlength="64" /><?php display_div_generate_password(DEFAULT_PASS_SIZE,"#password","#passwordconf"); ?></td>
  </tr>
  <tr>
    <th><label for="passwordconf"><?php __("Confirm password"); ?></label></th>
    <td><input type="password" class="int" autocomplete="off" name="passwordconf" id="passwordconf" value="" size="20" maxlength="64" /></td>
  </tr>
</table>
<br/>
<input type="submit" class="inb" value="<?php __("Change user password"); ?>" />
<input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='sql_users_list.php'"/>
</form>

<?php include_once("foot.php"); ?>
