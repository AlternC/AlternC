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
<h3><?php printf(_("Edit an email address of the domain %s"),$domain); ?> : </h3>
<hr id="topbar"/>
<br />
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
<table class="tedit">
	<tr><th colspan="2"><input type="hidden" name="email" value="<?php echo $email; ?>" />
<input type="hidden" name="domain" value="<?php echo $domain; ?>" />
<?php printf(_("Edit the email address <b>%s</b>"),$email); ?></th></tr>
<?php if (! is_null($res['trash_info']) && $res['trash_info']->is_trash ) { ?>
    <tr><th colspan="2"><span style="color: red"><?php __("This account is a temporary account.<br/>It will be delete on "); echo $res['trash_info']->human_display(); ?></span></th></tr>
<?php } ?>
								  <tr><td><label for="pop"><?php __("Is it a POP/IMAP account?"); ?></label></td>
<td>
<p>
 <input type="radio" name="pop" id="pop0" class="inc" value="0"<?php cbox($pop==0); ?> onclick="hide('poptbl');"><label for="pop0"><?php __("No"); ?></label>
 <input type="radio" name="pop" id="pop1" class="inc" value="1"<?php cbox($pop==1); ?> onclick="show('poptbl');"><label for="pop1"><?php __("Yes"); ?></label>
</p>
<div id="poptbl">
<table class="tedit" >
	<tr><td><label for="pass"><?php __("POP/IMAP password"); ?></label></td><td><input type="password" class="int" name="pass" id="pass" value="<?php ehe($pass); ?>" size="20" maxlength="32" /></td></tr>
	<tr><td><label for="passconf"><?php __("Confirm password"); ?></label></td><td><input type="password" class="int" name="passconf" id="passconf" value="<?php echo $pass; ?>" size="20" maxlength="32" /></td></tr>
</table>
</div>
<br />
  <?php if ($pop==1) { 
echo "<div class=\"warningmsg\">"._("WARNING: turning POP/IMAP off will DELETE the stored messages in this email address. This email address will become a simple redirection.")."</div>"; 
} ?>
</td></tr>

    <tr><td><label for="alias"><?php __("Redirections<br />Other recipients:"); ?></label></td><td>(<?php __("one email per line"); ?>)<br /><textarea class="int" cols="32" rows="5" name="alias" id="alias"><?php echo $alias; ?></textarea></td></tr>
<tr><td>
   <?php echo __("Informations for temporary account"); ?><br/>
   <span style="color: red;"><?php __("All this account information will <br/> be deleted at expiration time");?></span>
</td><td>
    <?php include_once("trash_dateselect.php"); ?>
</td></tr>

<tr class="trbtn"><td colspan="2">
  <input type="submit" class="inb" name="submit" value="<?php __("Change this email address"); ?>" />
  <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='mail_list.php?domain=<?php echo urlencode($domain); ?>'"/>
</td></tr>
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
<?php if ($pop==0) { ?>
  hide('poptbl'); 
  <?php } ?>
document.forms['main'].setAttribute('autocomplete', 'off');
</script>
<?php include_once("foot.php"); ?>
