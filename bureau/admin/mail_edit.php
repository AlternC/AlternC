<?php
/*
 $Id: mail_edit.php,v 1.6 2006/01/12 01:10:48 anarcat Exp $
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
 Original Author of file: Benjamin Sonntag
 Purpose of file: Edit a mailbox.
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"email"     => array ("request", "string", ""),
	"domain"    => array ("request", "string", ""),
);
getFields($fields);

if (!$res=$mail->get_mail_details($email))
{
	$error=$err->errstr();
	echo $error;
}
else
{

?>
<h3><?php printf(_("Edit a mailbox of the domain %s"),"http://$domain"); ?> : </h3>
<?php
if ($error_edit) {
	echo "<p class=\"error\">$error_edit</p>";
	$error_edit="";

} else {
	$pop=$res["pop"];
	$pass=$res["password"];
	$alias=$res["alias"];
} ?>

<form action="mail_doedit.php" method="post" name="main" id="main">
<table border="1" cellspacing="0" cellpadding="4">
	<tr><th colspan="2"><input type="hidden" name="email" value="<?php echo $email; ?>" />
<input type="hidden" name="domain" value="<?php echo $domain; ?>" />
<?php printf(_("Edit the mailbox %s"),$email); ?></th></tr>
        <tr><td><label for="ispop"><?php __("Is it a POP account?"); ?></label></td><td><input id="ispop" type="checkbox" class="inc" name="pop" value="1" <?php if ($pop=="1") echo "checked=\"checked\""; ?> /><?php if ($pop) { __("WARNING: turning POP off will DELETE the mailbox and its content"); }?></td></tr>
	<tr><td><label for="pass"><?php __("POP password"); ?></label></td><td><input type="password" class="int" name="pass" id="pass" value="<?php echo $pass; ?>" size="20" maxlength="32" /></td></tr>
	<tr><td><label for="passconf"><?php __("Confirm password"); ?></label></td><td><input type="password" class="int" name="passconf" id="passconf" value="<?php echo $pass; ?>" size="20" maxlength="32" /></td></tr>
	<tr><td><label for="alias"><?php __("Other recipients"); ?></label></td><td>(<?php __("One email per line"); ?>)<br /><textarea class="int" cols="32" rows="5" name="alias" id="alias"><?php echo $alias; ?></textarea></td></tr>
	<tr><td colspan="2"><input type="submit" class="inb" name="submit" value="<?php __("Change this mailbox"); ?>" /></td></tr>
</table>
</form>
<p><small>
<?php __("help_mail_edit"); ?>
</small></p>
<?php
}
?>
<script type="text/javascript">
document.forms['main'].email.focus();
</script>
<?php include_once("foot.php"); ?>
