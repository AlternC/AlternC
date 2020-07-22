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
 * Form to edit a mailbox parameters.
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"mail_id" =>array ("request","integer",""),
	"pass" => array ("post","string",""),
	"passconf" => array("post","string",""),
	"quotamb" => array("post","integer",0),
	"enabled" => array("post","boolean",true),
	"islocal" => array("post","boolean",true),
	"recipients" => array("post","string",""),
);
getFields($fields);

if (!$res=$mail->get_details($mail_id)) {
  include("mail_list.php");
  exit();
} else {
  
  foreach($res as $key=>$val) $$key=$val;
  $quotamb=$quota;

  if ($islocal && $mailbox_action=="DELETE") $islocal=false;
  
  if (isset($isedit) && $isedit) getFields($fields); // we came from a POST, so let's get the request again ...

?>
<h3><?php printf(_("Editing the email %s"),$res["address"]."@".$res["domain"]); ?></h3>
<hr id="topbar"/>
<br />


<?php
$c=$admin->listPasswordPolicies();
$passwd_classcount = $c['pop']['classcount'];

echo $msg->msg_html_all();
?>

<form action="mail_doedit.php" method="post" name="main" id="main" autocomplete="off">
   <?php csrf_get(); ?>
<!-- honeypot fields -->
<input type="text" style="display: none" id="fakeUsername" name="fakeUsername" value="" />
<input type="password" style="display: none" id="fakePassword" name="fakePassword" value="" />

<input type="hidden" name="mail_id" value="<?php ehe($mail_id); ?>" />
<input type="hidden" name="new_account" value="<?php echo isset($new_account)?$new_account:false;?>" />
<table class="tedit">
  <tr><th colspan="2"><b><?php __("Is this email enabled?"); ?></b></th></tr>

  <tr><td style="width: 50%; text-align: justify"><?php __("You can enable or disable this email anytime. This will bounce any mail received on this address, but will not delete the stored email, or the redirections or password."); ?><br />
</td>
    <td>
      <p>
	<input type="radio" name="enabled" id="enabled0" class="inc" value="0"<?php cbox($enabled==0); ?> /><label for="enabled0"><?php __("No (email disabled)"); ?></label>
	<input type="radio" name="enabled" id="enabled1" class="inc" value="1"<?php cbox($enabled==1); ?> /><label for="enabled1"><?php __("Yes (email enabled)"); ?></label>
      </p>
  </td></tr>

  <tr><th colspan="2"><b><?php __("Is it a POP/IMAP account?"); ?></b></th></tr>
  <tr><td style="width: 50%; text-align: justify"><?php __("POP/IMAP accounts are receiving emails in the server. To read those emails, you can use a Webmail, or a mail client such as Thunderbird. If you don't use POP/IMAP, you can configure your email to be a redirection to other existing emails. The maximum size is in megabytes, use 0 to make it infinite."); ?><br />
<p>&nbsp;</p>
<?php if ($islocal) { ?>
<p><?php printf(_('This mailbox is currently using %1$s / %2$s'),format_size($used),format_size($quotabytes)); ?></p>
<?php } ?>
<?php if ($mailbox_action=="DELETE") { ?>
<p class="alert alert-warning"><?php __("This mailbox is pending deletion. You can recover its mails by setting it to 'Yes' NOW!"); ?></p>
<?php } ?>
</td>
    <td>
      <p>
	<input type="radio" name="islocal" id="islocal0" class="inc" value="0"<?php !isset($new_account)?cbox($islocal==0):""; ?> onclick="popoff()" /><label for="islocal0"><?php __("No"); ?></label>
	<input type="radio" name="islocal" id="islocal1" class="inc" value="1"<?php !isset($new_account)?cbox($islocal==1):cbox($islocal==0); ?> onclick="popon();" /><label for="islocal1"><?php __("Yes"); ?></label>
      </p>
      <div id="poptbl">
	<table class="tedit" >
          <tr id='mail_edit_pass' style='display: none;'><td colspan='2'><a href='javascript:mail_edit_pass();'><?php __("Click here to edit the existing password");?></a></td></tr>
	  <tr id='mail_edit_pass1'><td><label for="pass"><?php __("Enter a POP/IMAP password"); ?></label></td><td><input type="password" class="int" autocomplete="off" name="pass" id="pass" value="" size="20"/><?php display_div_generate_password(DEFAULT_PASS_SIZE,"#pass","#passconf",$passwd_classcount); ?></td></tr>
	  <tr id='mail_edit_pass2'><td><label for="passconf"><?php __("Confirm password"); ?></label></td><td><input type="password" class="int" autocomplete="off" name="passconf" id="passconf" value="" size="20"/></td></tr>
	  <tr><td><label for="quotamb"><?php __("Maximum allowed size of this Mailbox"); ?></label></td><td><input type="text" class="int intleft" style="text-align: right" name="quotamb" id="quotamb" value="<?php ehe($quotamb); ?>" size="7" maxlength="6" /><span class="int intright"><?php __("MB"); ?></span></td></tr>
	</table>
      </div>
  </td></tr>
  <?php if ($islocal) { ?>
<tr id="turnoff" style="display: none;"><td colspan="2" class="alert alert-warning"><?php __("WARNING: turning POP/IMAP off will DELETE the stored messages in this email address."); ?></td></tr>
<?php } ?>
  <tr><th colspan="2"><b><?php __("Is it a redirection to other email addresses?"); ?></b></th></tr>

  <tr><td style="width: 50%; text-align: justify"><label for="recipients"><?php __("If you want to send emails received on this address to other addresses, even outside this server, enter those recipients here."); ?></label></td><td>(<?php __("one recipient per line"); ?>)<br /><textarea class="int" cols="32" rows="5" name="recipients" id="recipients"><?php echo $recipients; ?></textarea></td></tr>
<?php 
   
   $html=$hooks->invoke("hook_mail_edit_html",array($mail_id,$type));
foreach($html as $h) echo $h; 

?>
<tr class="trbtn"><td colspan="2">
  <input type="submit" class="inb ok" name="submit" value="<?php __("Change this email address"); ?>" />
  <input type="button" class="inb cancel" name="cancel" value="<?php __("Cancel"); ?>" onclick="window.history.go(-1);"/>
</td></tr>
</table>
</form>

<?php
}
?>
<script type="text/javascript">

function popoff() {
    $('#turnoff').show(); 
    $('#poptbl').addClass('grey'); 
    $('#pass').attr("disabled", "disabled");
    $('#quotamb').attr("disabled", "disabled");
    $('#passconf').attr("disabled", "disabled");
}
function popon() {
    $('#turnoff').hide(); 
    $('#poptbl').removeClass('grey'); 
    $('#pass').removeAttr("disabled");
    $('#quotamb').removeAttr("disabled");
    $('#passconf').removeAttr("disabled");
}

function mail_edit_pass() {
  $('#mail_edit_pass').toggle();
  $('#mail_edit_pass1').toggle();
  $('#mail_edit_pass2').toggle();
}

</script>
<?php 
if (isset($res) && !empty($res['password']) ) { 
  echo '<script type="text/javascript">mail_edit_pass();</script>';
} // if $islocal 

include_once("foot.php"); 
?>
