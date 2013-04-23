<?php
/*
 $Id: adm_slaveaccount.php,v 1.2 2006/02/17 18:57:02 olivier Exp $
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
 Purpose of file: Manage list of allowed accounts for zone transfers
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
	__("This page is restricted to authorized staff");
	exit();
}

$fields = array (
	"delaccount"   => array ("request", "string", ""),

	"newlogin"   => array ("request", "string", ""),
	"newpass"    => array ("request", "string", ""),
);
getFields($fields);

if ($delaccount) {
	// Delete an account
	if ($dom->del_slave_account($delaccount)) {
		$error=_("The requested account has been deleted. It is now denied.");
	}
}
if ($newlogin) {
	// Add an account
	if ($dom->add_slave_account($newlogin,$newpass)) {
		$error=_("The requested account address has been created. It is now allowed.");
		unset($newlogin); unset($newpass);
	}
}

include_once ("head.php");

?>
<h3><?php __("Manage allowed accounts for slave zone transfers"); ?></h3>
<hr id="topbar"/>
<br />
<?php
	if (isset($error) && $error) {
	  echo "<p class=\"error\">$error</p>";
	}

$c=$dom->enum_slave_account();

if (is_array($c)) {

?>
<p>
<?php __("Here is the list of the allowed accounts for slave dns synchronization. You can configure the alternc-slavedns package on your slave server and give him the login/pass that will grant him access to your server's domain list. "); ?>
</p>

<table class="tlist">
<tr><th><?php __("Action"); ?></th><th><?php __("Login"); ?></th><th><?php __("Password"); ?></th></tr>
<?php
$col=1;
for($i=0;$i<count($c);$i++) {
 $col=3-$col;
?>

<tr class="lst<?php echo $col; ?>">
   <td class="center"><div class="ina"><a href="adm_slaveaccount.php?delaccount=<?php echo urlencode($c[$i]["login"]); ?>"><img src="images/delete.png" alt="<?php __("Delete"); ?>" /><?php __("Delete"); ?></a></div></td>
  <td><?php ehe($c[$i]["login"]); ?></td>
  <td><?php ehe($c[$i]["pass"]); ?></td>
</tr>
<?php
}
?>
</table>
    <?php } ?>
<p><?php __("If you want to allow a new server to access your domain list, give him an account."); ?></p>
<form method="post" action="adm_slaveaccount.php" name="main" id="main">
<table class="tedit">
<tr><th><label for="newlogin"><?php __("Login"); ?></label></th><th><label for="newpass"><?php __("Password"); ?></label></th></tr>
<tr>
	<td><input type="text" class="int" value="<?php ehe(  isset($newlogin)?$newlogin:'') ; ?>" id="newlogin" name="newlogin" maxlength="64" size="32" /><br/><br/></td>
	<td><input type="password" class="int" value="<?php ehe( (isset($newpass)?$newpass:'') ) ; ?>" id="newpass" name="newpass" maxlength="64" size="32" /><?php display_div_generate_password(DEFAULT_PASS_SIZE,"#newpass"); ?></td>
</tr>
<tr class="trbtn"><td colspan="2">
	<input type="submit" value="<?php __("Add this account to the allowed list"); ?>" class="inb" />
  </td></tr>
</table>
<script type="text/javascript">
document.forms['main'].newlogin.focus();
document.forms['main'].setAttribute('autocomplete', 'off');
</script>

</form>
<?php include_once("foot.php"); ?>
