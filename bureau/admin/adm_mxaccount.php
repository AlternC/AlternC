<?php
/*
 $Id: adm_mxacount.php,v 1.2 2006/02/17 18:57:02 olivier Exp $
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
 Purpose of file: Manage list of allowed accounts for secondary mx
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
	$msg->raise("ERROR", "admin", _("This page is restricted to authorized staff"));
	echo $msg->msg_html_all();
	exit();
}

$fields = array (
	"delaccount"   => array ("request", "string", ""),
	"newlogin"   => array ("post", "string", ""),
	"newpass"    => array ("post", "string", ""),
);
getFields($fields);

if ($delaccount) {
	// Delete an account
	if ($mail->del_slave_account($delaccount)) {
		$msg->raise("INFO", "admin", _("The requested account has been deleted. It is now denied."));
	}
}
if ($newlogin) {
	// Add an account
	if ($mail->add_slave_account($newlogin,$newpass)) { 
		$msg->raise("INFO", "admin", _("The requested account address has been created. It is now allowed."));
		$newlogin='';$newpass='';
	}
}

include_once("head.php");

$c=$admin->listPasswordPolicies();
$passwd_classcount = $c['adm']['classcount'];

?>
<h3><?php __("Manage allowed accounts for secondary mx"); ?></h3>
<hr id="topbar"/>
<br />
<?php
$c=$mail->enum_slave_account();

echo $msg->msg_html_all();

if (is_array($c)) {

?>
<p>
<?php __("Here is the list of the allowed accounts for secondary mx management. You can configure the alternc-secondarymx package on your secondary mx server and give him the login/pass that will grant him access to your server's mx-hosted domain list. "); ?>
</p>

<table border="0" cellpadding="4" cellspacing="0" class='tlist'>
<tr><th><?php __("Action"); ?></th><th><?php __("Login"); ?></th><th><?php __("Password"); ?></th></tr>
<?php
for($i=0;$i<count($c);$i++) { ?>

<tr class="lst">
<td class="center"><div class="ina delete"><a href="adm_mxaccount.php?delaccount=<?php echo urlencode($c[$i]["login"]); ?>"><?php __("Delete"); ?></a></div></td>
<td><?php echo $c[$i]["login"]; ?></td>
<td><?php echo $c[$i]["pass"]; ?></td>
</tr>
<?php
}
?>
</table>
    <?php } ?>
<p><?php __("If you want to allow a new server to access your mx-hosted domain list, give him an account."); ?></p>
<form method="post" action="adm_mxaccount.php" name="main" id="main" autocomplete="off">
  <?php csrf_get(); ?>
<!-- honeypot fields -->
<input type="text" style="display: none" id="fakeUsername" name="fakeUsername" value="" />
<input type="password" style="display: none" id="fakePassword" name="fakePassword" value="" />

<table class="tedit">
<tr><th><label for="newlogin"><?php __("Login"); ?></label></th><th><label for="newpass"><?php __("Password"); ?></label></th></tr>
<tr>
	<td><input type="text" class="int" value="<?php ehe($newlogin); ?>" id="newlogin" name="newlogin" maxlength="64" size="32" /><br/><br/></td>
	<td><input type="password" class="int" autocomplete="off" value="<?php ehe($newpass); ?>" id="newpass" name="newpass" maxlength="64" size="32" /><?php display_div_generate_password(DEFAULT_PASS_SIZE,"#newpass","",$passwd_classcount); ?></td>
</tr>
<tr class="trbtn"><td colspan="2">
	<input type="submit" value="<?php __("Add this account to the allowed list"); ?>" class="inb" />
  </td></tr>
</table>

</form>

<script type="text/javascript">
document.forms['main'].newlogin.focus();
</script>
<?php include_once("foot.php"); ?>
