<?php
/*
 $Id: mail_add.php,v 1.6 2006/01/12 01:10:48 anarcat Exp $
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
 Original Author of file: Benjamin Sonntag, Franck Missoum
 Purpose of file: Ask for the values required to create a mail account
	 or a mail alias
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"domain"    => array ("request", "string", ""),
	"many"      => array ("request", "integer", 0),
	"pop"     => array ("request", "integer", 1),
);
getFields($fields);

?>
<h3><?php printf(_("Add a mail to the domain %s"),$domain); ?> : </h3>
<?php
if ($error) {
  echo "<p class=\"error\">$error</p>";
}

?>

<form action="mail_doadd.php" method="post" name="main" id="main">
  <input type="hidden" name="domain" value="<?php echo $domain; ?>" />
 <table class="tedit">
<tr><td>
  <label for="email"><?php __("Email address"); ?></label></td><td><input class="int" type="text" name="email" id="email" value="<?php ehe($email); ?>" size="20" maxlength="32" /><span class="int" id="emaildom">@ <?php echo $domain ?></span>
  </td></tr>
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
</td></tr>

    <tr><td><label for="alias"><?php __("Redirections<br />Other recipients:"); ?></label></td><td>(<?php __("one email per line"); ?>)<br /><textarea class="int" cols="32" rows="5" name="alias" id="alias"><?php echo $alias; ?></textarea></td></tr>
<tr class="trbtn"><td colspan="2">
<input type="hidden" name="many" value="<?php echo intval($many); ?>" />
  <input type="submit" class="inb" name="submit" value="<?php __("Create this email address"); ?>" />
  <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='mail_list.php?domain=<?php echo urlencode($domain); ?>'"/>
</td></tr>
</table>
</form>

<p><small>
<?php __("help_mail_add"); ?>
</small></p>
<script type="text/javascript">
document.forms['main'].email.focus();
document.forms['main'].setAttribute('autocomplete', 'off');
</script>
<?php include_once("foot.php");
