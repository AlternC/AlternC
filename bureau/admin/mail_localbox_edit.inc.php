<?php 
/*
 mail_localbox_edit.php, author: squidly
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
 Purpose of file: Create a new mail account
 ----------------------------------------------------------------------
*/

require_once("../class/config.php");
if (! $mail_id ) {
	$error="Missing mail_id";
}
$mail_details = $mail->mail_get_details($mail_id);
if (!$mail_details) die ("error on mail details");

if (isset($error) && $error) {
  echo "<p class=\"error\">$error</p><p>&nbsp;</p>";
}

echo "<h3>";
echo sprintf(_("Edition of <b>%s</b>")."<br />",$mail_details['address_full']);
echo "</h3>";?>
<form action="mail_localbox_doedit.php" method="post" name="main" id="main">

<table class="tedit">
  <tr>
    <td>
	  <?php if ($mail_details['is_local']!=0) { 
	echo "<div class=\"warningmsg\">"._("WARNING: turning POP/IMAP off will DELETE the stored messages in this email address. This email address will become a simple redirection.")."</div>"; 
	}else{
	echo "<div class=\"warningmsg\">"._("Activate the localbox, will allow you to store your mails on this server. \n It is a very conveniant way to check your mails while roaming,
		thince they can be accesed remotely by POP or IMAP. It consume space on the server so be sure to verify your quota attribution.")."</div>"; 
	} 
?>
	<p>
	 <input type="radio" name="local" id="local0" class="inc" value="0"<?php cbox($mail_details['is_local']==0); ?> onclick="show('localtbl');"><label for="local0"><?php __("No"); ?></label>
	 <input type="radio" name="local" id="local1" class="inc" value="1"<?php cbox($mail_details['is_local']!=0); ?> onclick="show('localtbl');"><label for="local1"><?php __("Yes"); ?></label>
	</p>
	<br />
    </td>
  </tr>
  <tr class="trbtn">
    <td colspan="2">
      <input type="hidden" class="inb" name="mail_id" value="<?php echo $mail_id ; ?>" />
      <input type="hidden" class="inb" name="is_local" value="<?php if($mail_details['is_local']==0) echo "0"; else echo "1" ; ?>" />
      <input type="submit" class="inb" name="submit" value="<?php __("Change this email address"); ?>" />
      <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='mail_properties.php?mail_id=<?php echo urlencode($mail_id); ?>'"/>
    </td>
  </tr>
</table>

</form>
