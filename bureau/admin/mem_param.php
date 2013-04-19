<?php
/*
 $Id: mem_param.php,v 1.8 2004/07/23 14:03:57 anonymous Exp $
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
 Original Author of file:  Benjamin Sonntag
 Purpose of file: Allow the customization of the user interface
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

include_once("head.php");

$fields = array (
	"help_setting" 	=> array ("request", "string", ""),
	"showhelp" 	=> array ("request", "integer", ""),
);
getFields($fields);


if (!empty($help_setting)) {
	$mem->set_help_param($showhelp);
	$error=_("Your help setting has been updated.");
}

?>
<div align="center"><h3><?php __("Settings of your account"); ?></h3></div>
<?php
	if (isset($error) && $error) {
		echo "<font color=red>$error</font>";
		include_once("foot.php");
		exit();
	}
?>
<hr id="topbar"/>
<p>
<?php __("Password change"); ?> : <br />
<?php

if (!$mem->user["canpass"]) {
  __("You cannot change your password");
  echo "</p>";

} else {

 __("help_chg_passwd"); ?>
</p>
<form method="post" action="mem_passwd.php" name="main" id="main">
<table border="1" cellspacing="0" cellpadding="4" class="tedit" >
<tr><th><?php __("Old password"); ?></th><td><input type="password" class="int" name="oldpass" value="<?php isset($oldpass) ? : $oldpass=""; echo $oldpass; ?>" size="20" maxlength="128" /></td></tr>
<tr><th><?php __("New password"); ?> (1)</th><td><input type="password" class="int" id="newpass" name="newpass" value="<?php isset($newpass) ? : $newpass=""; echo $newpass;  ?>" size="20" maxlength="60" /><?php display_div_generate_password(DEFAULT_PASS_SIZE,"#newpass","#newpass2"); ?></td></tr>
<tr><th><?php __("New password"); ?> (2)</th><td><input type="password" class="int" id="newpass2" name="newpass2" value="<?php isset($newpass2) ? : $newpass2=""; echo $newpass2;?>" size="20" maxlength="61" /></td></tr>
<tr class="trbtn"><td colspan="3"><input type="submit" class="inb" name="submit" value="<?php __("Change my password"); ?>" /></td></tr>
</table>
</form>
<br />
<?php } ?>
<hr id="topbar2"/>
<form method="post" action="mem_chgmail.php">
	<table border="1" cellspacing="0" cellpadding="4" class="tedit">
		<tr><td colspan="2"><?php __("Change the email of the account"); ?><br />
		<?php __("help_chg_mail"); ?></td></tr>
		<tr><th><?php __("Current mailbox"); ?></th><td><big><code><?php echo $mem->user["mail"]; ?></code></big></td></tr>
		<tr><th><?php __("New mailbox"); ?></th><td><input type="text" class="int" name="newmail" value="<?php   isset($newmail) ? : $newmail=""; echo $newmail;?>" size="40" maxlength="128" /></td></tr>
		<tr class="trbtn"><td colspan="3"><input type="submit" class="inb" name="submit" value="<?php __("Change my email address"); ?>" /></td></tr>
	</table>
</form>
<br />
<hr id="topbar3"/>
<form method="post" action="mem_param.php">
	<table border="1" cellspacing="0" cellpadding="4" class="tedit">
		<tr><td colspan="2"><?php __("Online help settings"); ?><br />
		<?php __("help_help_settings"); ?></td></tr>
		<tr><th><label for="showhelp"><?php __("Do you want to see the help texts and links on each page?"); ?></label></th><td><input type="checkbox" class="inc" id="showhelp" name="showhelp" value="1" <?php if ($mem->get_help_param()) echo "checked=\"checked\""; ?> /></td></tr>
		<tr class="trbtn"><td colspan="3"><input type="submit" class="inb" name="help_setting" value="<?php __("Change these settings"); ?>" /></td></tr>
	</table>
</form>
<br />
<?php
if ($mem->user["su"]) {
?>
<hr id="topbar4"/>
<p>
<?php __("Admin preferences"); ?> :
</p>
<form method="post" action="mem_admin.php">
<table border="1" cellspacing="0" cellpadding="4" class="tedit">
<tr><th><?php __("Members list view"); ?></th><td><select name="admlist" class="inl">
<option value="0"<?php if ($mem->user["admlist"]==0) echo " selected=\"selected\""; ?>><?php __("Large view"); ?></option>
<option value="1"<?php if ($mem->user["admlist"]==1) echo " selected=\"selected\""; ?>><?php __("Short view"); ?></option>
</select></td></tr>
<tr class="trbtn"><td colspan="2"><input type="submit" class="inb" name="submit" value="<?php __("Change my admin preferences"); ?>" /></td></tr>
</table>
</form>
<br />
<?php } ?>
<hr id="topbar5"/>
<script type="text/javascript">
document.forms['main'].oldpass.focus();
</script>

<?php include_once("foot.php"); ?>
