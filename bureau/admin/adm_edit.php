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
 * Show a form to edit an account
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");
include_once("head.php");

if (!$admin->enabled) {
	$msg->raise("ERROR", "admin", __("This page is restricted to authorized staff", "alternc", true));
	echo $msg->msg_html_all();
	exit();
}

$fields = array (
	"uid"    => array ("request", "integer", 0),
);
getFields($fields);

$subadmin=variable_get("subadmin_restriction");

if ($subadmin==0 && !$admin->checkcreator($uid)) {
	$msg->raise("ERROR", "admin", __("This page is restricted to authorized staff", "alternc", true));
	echo $msg->msg_html_all();
	exit();
}

$r=$admin->get($uid);

$c=$admin->listPasswordPolicies();
$passwd_classcount = $c['adm']['classcount'];

?>
<h3><?php __("Member Edition"); ?></h3>
<hr id="topbar"/>
<br />
<?php
echo $msg->msg_html_all();
?>
<form method="post" action="adm_doedit.php" name="main" id="main" autocomplete="off">
  <?php csrf_get(); ?>

<table class="tedit">
<tr>
	<th><input type="hidden" name="uid" value="<?php echo $uid ?>" />
<?php __("Username"); ?></th>
	<td><?php echo $r["login"]; ?></td>
</tr>
<tr>
	<th><label><?php __("Account Enabled?"); ?></label></th>
	<td>
	<?php if ($r["uid"]==$mem->user["uid"]) { ?>
  <?php __("You cannot disable your own account."); ?>
  <?php } else { ?>
        <input type="radio" class="inc" id="enabled0" name="enabled" value="0"<?php cbox($r["enabled"]==0); ?> /><label for="enabled0"><?php __("No"); ?></label><br />
	<input type="radio" class="inc" id="enabled1" name="enabled" value="1"<?php cbox($r["enabled"]==1); ?> /><label for="enabled1"><?php __("Yes"); ?></label><br />	
	<?php } ?>
	</td>
</tr>

<tr>
	<th><label for="pass"><?php __("Password"); ?></label></th>
	<td><input type="password" class="int" id="pass" autocomplete="off" name="pass" value="" size="20" maxlength="64" /><?php display_div_generate_password(DEFAULT_PASS_SIZE,"#pass","#passconf",$passwd_classcount); ?></td>
</tr>
<tr>
	<th><label for="passconf"><?php __("Confirm password"); ?></label></th>
	<td><input type="password" class="int" id="passconf" autocomplete="off" name="passconf" value="" size="20" maxlength="64" /></td>
</tr>
<tr>
	<th><label><?php __("Password change allowed?"); ?></label></th>
	<td>
        <input type="radio" class="inc" id="canpass0" name="canpass" value="0"<?php cbox($r["canpass"]==0); ?>/><label for="canpass0"><?php __("No"); ?></label><br />
	<input type="radio" class="inc" id="canpass1" name="canpass" value="1"<?php cbox($r["canpass"]==1); ?>/><label for="canpass1"><?php __("Yes"); ?></label><br />	
	</td>
</tr>
 <tr>
	<th><label for="notes"><?php __("Notes"); ?></label></th>
	<td><textarea name="notes" id="notes" class="int" cols="32" rows="5"><?php ehe($r['notes']); ?></textarea></td>
</tr>
<tr>
	<th><label for="nom"><?php echo __("Surname", "alternc", true)."</label> / <label for=\"prenom\">".__("First Name", "alternc", true); ?></label></th>
	<td><input type="text" class="int" name="nom" id="nom" value="<?php ehe($r["nom"]); ?>" size="20" maxlength="128" />&nbsp;/&nbsp;<input type="text" class="int" name="prenom" id="prenom" value="<?php ehe($r["prenom"]); ?>" size="20" maxlength="128" /></td>
</tr>
<tr>
	<th><label for="nmail"><?php __("Email address"); ?></label></th>
	<td><input type="text" class="int" name="nmail" id="nmail" value="<?php ehe($r["mail"]); ?>" size="30" maxlength="128" /></td>
</tr>
<tr>
	<th><label for="type"><?php __("Account type"); ?></label></th>
	<td><select name="type" id="type" class="inl">
	<?php
        eoption($quota->listtype(), $r['type'], true);
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

<br/>

<?php if($r['duration']) { ?>
<form method="post" action="adm_dorenew.php">
   <?php csrf_get(); ?>
<input type="hidden" name="uid" value="<?php echo $uid ?>" />
<table border="1" cellspacing="0" cellpadding="4" class="tedit">
<tr>
	<th><label for="periods"><?php __("Renew for") ?></label></th>
	<td><input name="periods" id="periods" type="text" size="2" value="1"/><?php echo ' ' . __('period(s, "alternc", true)') ?></td>
</tr>
<tr>
	<td colspan="2" align="center"><input type="submit" class="inb" name="submit" value="<?php __("Renew"); ?>" />
</td>
</tr>
</table>
</form>
<?php } /* Renewal */ ?>

<p>
<?php
if ($mem->user["uid"]==2000 && $r["uid"]!=2000) {  // Only ADMIN (2000) can change the admin status of accounts
if ($r["su"]) {
?>
<b><?php __("This account is a super-admin account"); ?></b>
<br/>
<br/>
<?php if ($admin->onesu()) {
  __("There is only one administrator account, you cannot turn this account back to normal");
} else {
?>
<span class="ina"><a href="adm_donosu.php?uid=<?php echo $r["uid"]; ?>"><?php __("Turn this account back to normal"); ?></a></span>
<?php }
} else { ?>
<span class="ina"><a href="adm_dosu.php?uid=<?php echo $r["uid"]; ?>"><?php __("Make this account a super admin one"); ?></a></span>
<?php } ?>
</p>

<p><?php
	}
if ($c=$admin->get($r["creator"])) {
  printf(__("Account created by %s", "alternc", true),$c["login"]);
 }
?>
</p>
<script type="text/javascript">
 document.forms['main'].pass.focus();
</script>
<?php include_once("foot.php"); ?>
