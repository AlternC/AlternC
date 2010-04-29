<?php
/*
 $Id: adm_edit.php,v 1.13 2006/01/24 05:03:30 joe Exp $
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2006 Le réseau Koumbit Inc.
 http://koumbit.org/
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
 Purpose of file: Show a form to edit a member
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

if (!$admin->enabled) {
	__("This page is restricted to authorized staff");
	exit();
}

$fields = array (
	"uid"    => array ("request", "integer", 0),
);
getFields($fields);

$subadmin=variable_get("subadmin_restriction");

if ($subadmin==0 && !$admin->checkcreator($uid)) {
	__("This page is restricted to authorized staff");
	exit();
}

if (!$r=$admin->get($uid)) {
	$error=$err->errstr();
}

?>
<h3><?php __("Member Edition"); ?></h3>
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p>";
	}
?>
<form method="post" action="adm_doedit.php" name="main" id="main">
<table class="tedit">
<tr>
	<th><input type="hidden" name="uid" value="<?php echo $uid ?>" />
<?php __("Username"); ?></th>
	<td><?php echo $r["login"]; ?></td>
</tr>
<tr>
	<th><label for="enabled"><?php __("Account Enabled?"); ?></label></th>
	<td>
        <input type="radio" class="inc" id="enabled0" name="enabled" value="0"<?php cbox($r["enabled"]==0); ?>><label for="enabled0"><?php __("No"); ?></label><br />
	<input type="radio" class="inc" id="enabled1" name="enabled" value="1"<?php cbox($r["enabled"]==1); ?>><label for="enabled1"><?php __("Yes"); ?></label><br />	
	</td>
</tr>

<tr>
	<th><label for="pass"><?php __("Password"); ?></label></th>
	<td><input type="password" class="int" id="pass" name="pass" value="" size="20" maxlength="64" /></td>
</tr>
<tr>
	<th><label for="passconf"><?php __("Confirm password"); ?></label></th>
	<td><input type="password" class="int" id="passconf" name="passconf" value="" size="20" maxlength="64" /></td>
</tr>
<tr>
	<th><label for="canpass"><?php __("Can he change its password"); ?></label></th>
	<td>
        <input type="radio" class="inc" id="canpass0" name="canpass" value="0"<?php cbox($r["canpass"]==0); ?>><label for="canpass0"><?php __("No"); ?></label><br />
	<input type="radio" class="inc" id="canpass1" name="canpass" value="1"<?php cbox($r["canpass"]==1); ?>><label for="canpass1"><?php __("Yes"); ?></label><br />	
	</td>
</tr>
 <tr>
	<th><label for="notes"><?php __("Notes"); ?></label></th>
	<td><textarea name="notes" id="notes" class="int" cols="32" rows="5"><?php echo $r['notes']; ?></textarea></td>
</tr>
<tr>
	<th><label for="nom"><?php echo _("Surname")."</label> / <label for=\"prenom\">"._("First Name"); ?></label></th>
	<td><input type="text" class="int" name="nom" id="nom" value="<?php echo $r["nom"]; ?>" size="20" maxlength="128" />&nbsp;/&nbsp;<input type="text" class="int" name="prenom" id="prenom" value="<?php echo $r["prenom"]; ?>" size="20" maxlength="128" /></td>
</tr>
<tr>
	<th><label for="nmail"><?php __("Email address"); ?></label></th>
	<td><input type="text" class="int" name="nmail" id="nmail" value="<?php echo $r["mail"]; ?>" size="30" maxlength="128" /></td>
</tr>
<tr>
	<th><label for="type"><?php __("Account type"); ?></label></th>
	<td><select name="type" id="type" class="inl">
	<?php
	$db->query("SELECT distinct(type) FROM defquotas ORDER by type");
	while($db->next_record()) {
	  $type = $db->f("type");
	  echo "<option value=\"$type\"";
	  if($type == $r['type'])
	    echo " selected";
	  echo ">$type</option>";
	}
?></select>&nbsp; <input type="checkbox" name="reset_quotas" id="reset_quotas" class="inc" /><label for="reset_quotas"><?php __("Reset quotas to default?") ?></label></td>
</tr>
<tr>
	<th><label for="duration"><?php __("Period"); ?></label></th>
	<td><?php echo duration_list('duration', $r['duration']) ?></td>
</tr>
<tr class="trbtn"><td colspan="2">
  <input type="submit" class="inb" name="submit" value="<?php __("Edit this account"); ?>" />
  <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='adm_list.php'" />
	  
</td>
</tr>
</table>
</form>

<?php if($r['duration']) { ?>
<p>
<form method="post" action="adm_dorenew.php">
<input type="hidden" name="uid" value="<?php echo $uid ?>" />
<table border="1" cellspacing="0" cellpadding="4">
<tr>
	<th><label for="periods"><?php __("Renew for") ?></label></th>
	<td><input name="periods" id="periods" type="text" size="2" value="1"/><?php echo ' ' . _('period(s)') ?></td>
</tr>
<tr>
	<td colspan="2" align="center"><input type="submit" class="inb" name="submit" value="<?php __("Renew"); ?>" />
</td>
</tr>
</table>
</form>
</p>
<?php } /* Renouvellement */ ?>

<p>
<?php
if ($mem->user["uid"]==2000) {  // Only ADMIN (2000) can change the admin status of accounts
if ($r["su"]) {
?>
<p><b><?php __("This account is a super-admin account"); ?></b></p>
<?php if ($admin->onesu()) {
  __("There is only one administrator account, you cannot turn this account back to normal");
} else {
?>
<p><span class="ina"><a href="adm_donosu.php?uid=<?php echo $r["uid"]; ?>"><?php __("Turn this account back to normal"); ?></a></span></p>
<?php }
} else { ?>
<p><span class="ina"><a href="adm_dosu.php?uid=<?php echo $r["uid"]; ?>"><?php __("Make this account a super admin one"); ?></a></span></p>
<?php } ?>
</p>


<p><?php
	}
$c=$admin->get($r["creator"]);
printf(_("Account created by %s"),$c["login"]);
?>
</p>
<script type="text/javascript">
 document.forms['main'].pass.focus();
 document.forms['main'].setAttribute('autocomplete', 'off');
</script>
<?php include_once("foot.php"); ?>
