<?php
/*
 $Id: sql_users_add.php,v 1.5 2004/05/19 14:23:06 nahuel Exp $
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
 Purpose of file:
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"usern"     => array ("request", "string", ""),
	"password"    => array ("request", "string", ""),
	"passconf"    => array ("request", "string", ""),
);
getFields($fields);

?>
<h3><?php __("Create a new MySQL user"); ?></h3>
<hr id="topbar"/>
<br />
<?php
	if (isset($error) && $error) {
		echo "<p class=\"error\">$error</p>";
		if (isset($fatal) && $fatal) {
?>
<?php include_once("foot.php"); ?>

<?php
			exit();
		}
	}
?>
<form method="post" action="sql_users_doadd.php" id="main" name="main">
<table class="tedit">
<tr>
  <th><label for="usern"><?php __("Username"); ?></label></th>
  <td><span class="int" id="usernpfx"><?php echo $mem->user["login"]; ?>_</span><input type="text" class="int" name="usern" id="usern" value="<?php ehe($usern); ?>" size="20" maxlength="20" /></td>
</tr>
<tr>
  <th><label for="password"><?php __("Password"); ?></label></th>
  <td><input type="password" class="int" name="password" id="password" size="26"/></td>
</tr>
<tr>
  <th><label for="password"><?php __("Confirm password"); ?></label></th>
  <td><input type="password" class="int" name="passconf" id="passconf" size="26"/></td>
</tr>

<tr class="trbtn"><td colspan="2">
  <input type="submit" class="inb" name="submit" value="<?php __("Create this new MySQL user"); ?>" />
  <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='sql_users_list.php'"/>
</td></tr>
</table>
</form>
<script type="text/javascript">
  if (document.forms['main'].usern.text!='') {
    document.forms['main'].password.focus();
  } else {
    document.forms['main'].usern.focus();
  }
  document.forms['main'].setAttribute('autocomplete', 'off');

</script>
<?php include_once("foot.php"); ?>
