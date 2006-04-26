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

include("head.php");
?>
</head>
<body>
<h3><?php printf(_("Add a mail to the domain %s"),"http://$domain"); ?> : </h3>
<?php
if ($error) {
  echo "<p class=\"error\">$error</p>";
} 

?>
<form action="mail_doadd.php" method="post">
<table border="1" cellspacing="0" cellpadding="4">
<input type="hidden" name="many" value="<?php echo intval($many); ?>" />
	<tr><td><input type="hidden" name="domain" value="<?php echo $domain ?>" />
<label for="email"><?php __("Email address"); ?></label></td><td><input class="int" type="text" name="email" id="email" value="<?php echo $email ?>" size="20" maxlength="32" />@<?php echo $domain ?></td></tr>
	<tr><td><label for="ispop"><?php __("Is it a POP account?"); ?></label></td><td><input id="ispop" class="inc" type="checkbox" name="pop" value="1" <?php if ($pop=="1") echo "checked=\"checked\""; ?> /></td></tr>
	<tr><td><label for="pass"><?php __("POP password"); ?></label></td><td><input class="int" type="password" name="pass" id="pass" value="<?php echo $pass; ?>" size="20" maxlength="32" /></td></tr>
	<tr><td><label for="passconf"><?php __("Confirm password"); ?></label></td><td><input class="int" type="password" name="passconf" id="passconf" value="<?php echo $pass; ?>" size="20" maxlength="32" /></td></tr>
	<tr><td><label for="alias"><?php __("Other recipients"); ?></label></td><td>(<?php __("One email per line"); ?>)<br /><textarea class="int" cols="32" rows="5" name="alias" id="alias"><?php echo $alias; ?></textarea></td></tr>
	<tr><td colspan="2"><input type="submit" class="inb" name="submit" value="<?php __("Create this mailbox"); ?>" /></td></tr>
</table>
</form>
<p><small>
<?php __("help_mail_add"); ?>
</small></p>

</body>
</html>
