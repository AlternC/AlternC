<?php
/*
 $Id: mail_edit.php, author:squidly
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
 Purpose of file: Edit a mailbox.
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$mail_details=$mail->mail_get_details($mail_id)) {
	$error=$err->errstr();
}
if(isset($error) && $error){
 echo "<p class=\"error\">$error</p><p>&nbsp;</p>";
}

?>
<form action="mail_doedit.php" method="post" name="main" id="main">
<fieldset>
  <legend><?php __("Status"); ?></legend>
  <p>
    <?php __("Is this mail enabled ?<br/><i>Having a mail disabled forbid consultation or reception, but do not remove the mailbox or his configuration.</i>"); ?>
  </p>
  <input type="radio" name="enable" id="enable0" class="inc" value="0"<?php cbox($mail_details['enabled']==0); ?> onclick="show('enabletbl');"><label for="enable0"><?php __("Disable"); ?></label>
   <input type="radio" name="enable" id="enable1" class="inc" value="1"<?php cbox($mail_details['enabled']!=0); ?> onclick="show('enabletbl');"><label for="enable1"><?php __("Enable"); ?></label>
</fieldset>
<br/>
<fieldset><legend><?php __("Password"); ?></legend>
  <p>
    <?php __("You need to have a password for some application (example: local mailbox);"); ?>
  </p>
  <p>
<?php
if($mail_details['password'] == ""){
	__("Set a passowrd:");?>
<?php
}else{
	__("Change your password:");
?>
<?php
}
?>
  </p>
  <table>
    <tr>
      <td><label for="pass"><?php __("Password"); ?></label></td>
      <td><input type="password" class="int" name="pass" id="pass" value="" size="20" maxlength="32" onKeyUp="javascript:checkpass();" /></td>
      <td rowspan=2><img src="" id="passimg" alt="" /><br/><label id="passtxt" /></td>
    </tr>
      <td><label for="passconf"><?php __("Confirm password"); ?></label></td>
      <td><input type="password" class="int" name="passconf" id="passconf" value="" size="20" maxlength="32" onKeyUp="javascript:checkpass();" /></td>
    <tr>
    </tr>
  </table>
</fieldset>

<br/>

<input type="hidden" class="inb" name="is_enabled" value="<?php echo $mail_details['enabled'] ; ?>" />
<input type="hidden" class="inb" name="mail_id" value="<?php echo $mail_id ; ?>" />
<input type="hidden" class="inb" name="domain" value="<?php echo $mail_details['domain'] ; ?>" />
<input type="submit" class="inb" name="submit" value="<?php __("Change this email address"); ?>" />
<input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='mail_properties.php?mail_id=<?php echo urlencode($mail_id); ?>'"/>
</form>

<script type="text/javascript">

function checkpass() {
  var pass = document.getElementById('pass').value;
  var passconf = document.getElementById('passconf').value;
  var src ="";
  var alt ="";
  var txt ="";

  if (pass != passconf ) {
    src = "images/check_no.png";
    alt = "KO";
    txt = '<?php echo htmlentities(_("Password does not match")); ?>';
  } else {
    src ="images/check_ok.png";
    alt ="OK";
    txt = '<?php echo htmlentities(_("Password match")); ?>';
  } 

  document.getElementById('passimg').src = src;
  document.getElementById('passimg').alt = alt;
  document.getElementById('passtxt').innerHTML = txt;
}
</script>





