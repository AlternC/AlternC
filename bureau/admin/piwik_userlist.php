<?php
/*
 $Id: piwik_userlist.php, author: squidly
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
 Purpose of file: listing of mail accounts 
 ----------------------------------------------------------------------
*/

require_once("../class/config.php");
include_once("head.php");

$userslist = $piwik->users_list();
$quotapiwik = $quota->getquota('piwik');

// TODO - Put the limit of piwik users (here at 3) as a variable in alternC
if ($quotapiwik['t'] > 0 && count($userslist) < 3) {
?>
<h3><?php __("Create a new piwik account");?></h3>
<?php
echo $msg->msg_html_all("<li>", true, true);
?>
<form method="post" action="piwik_addaccount.php" id="main" name="addaccount" >
 <?php csrf_get(); ?>
	<table class="tedit">
	<tr>
	<th><label for="account_name"><?php __("Account Name"); ?></label></th>   <!-- à traduire -->
	<td><span class="int" id="account_namefx"><?php echo $mem->user["login"]; ?>_</span><input type="text" class="int" name="account_name" size="20" id="account_name" maxlength="32" value=""/>
	</tr>
	<tr>
	<th><label for="account_mail"><?php __("Linked Account Email"); ?></label></th>  <!-- à traduire -->
	<td><input type="text" class="int" name="account_mail" size="20" id="account_mail" maxlength="32" value="<?php if (count($userslist) == 0) {echo $mem->user["mail"];}?>"/>
	</tr>
	<tr class="trbtn"><td colspan="2">
	<input type="submit" name="submit" class="inb" value="<?php __("Create"); ?>" />
	</tr>
	</table>
	<i>(<?php ehe("Max. 3 accounts"); ?>)</i>
</form>
<script type="text/javascript">
    document.forms['main'].account_name.focus();
</script>
<br/>
<hr/>
<?php
} else {
  $msg->raise('Info', "piwik", _("You cannot add any new Piwik account, your quota is over."));
} // cancreate piwik
?>

<h3><?php __("Existing Piwik accounts"); ?></h3>
<?php 
echo $msg->msg_html_all("<li>", true, true);
// printVar($piwik->dev());

if (empty($userslist)){
	$msg->raise('Info', "piwik", _("No existing Piwik accounts")); // à traduire (ou à corriger)
	echo $msg->msg_html_all();
} else {
?>

<table class="tlist">
    <tr><th/><th><?php __("Username");?></th><th align=center><?php __("Connect"); ?></th></tr>
<?php

$col=1;
foreach ($userslist as $user ){
	unset($piwik_pwd);
	$form_id="main_".$user->login;

        $db->query("SELECT passwd FROM piwik_users WHERE login = '$user->login'");
        if ($db->next_record()) {
          $piwik_pwd = $db->f('passwd');
        }

	$col=3-$col;
	?>
	<tr class="lst_clic<?php echo $col; ?>">
	<td>
		<div class="ina">
		  <form method="post" action="piwik_user_dodel.php" name="<?php echo $form_id; ?>" id="<?php echo $form_id; ?>">
		    <?php csrf_get(); ?>
		    <input type="hidden" name="login" value="<?php ehe($user->login);?>" />
		    <input type="button" class="ina" name="delete" value="<?php __("Delete"); ?>" onclick="document.getElementById('<?php echo $form_id; ?>').submit();" style="background: url('/images/delete.png') no-repeat 3px 3px; padding-left: 16px;" />
		  </form>
		</div>
	</td>
	<td><?php echo $user->login ?></td>
	  <!--<td><div class="ina"><a href="<?php printf('%s?module=Login&action=logme&login=%s&password=%s', $piwik->url(), $user->login, $user->password); ?>" target="_blank"><?php __('Connect'); ?></a></td>-->
          <td>
            <?php
            if ($piwik_pwd) {
            ?>
              <div class="ina"><a href="<?php printf('%s?module=Login&action=logme&login=%s&password=%s', $piwik->url(), $user->login, $piwik_pwd); ?>" target="_blank"><?php __('Connect'); ?></a>
            <?php
            } else {
            ?>
              <div class="ina"><img src="images/warning.png" onmouseover='$("#alert_div_msg").show();' onmouseout='$("#alert_div_msg").hide();'></div>
            <?php } ?>
          </td>
	</tr>
	<?php
} // foreach userlist 
} // empty userlist
?>

</table>
<div class="ina" id="alert_div_msg" style="display:none;background-color:yellow;padding:5px;border:2px solid black;margin-top:3em;";> <!-- à traduire -->
  <?php __("An error occurred. It was not possible to retrieve the access information to the Piwik interface") ?>
</div>
<?php include_once("foot.php"); ?>
