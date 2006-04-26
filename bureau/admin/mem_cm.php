<?php
/*
 $Id: mem_cm.php,v 1.6 2004/11/29 17:27:04 anonymous Exp $
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
 Original Author of file:  Benjamin Sonntag
 Purpose of file: Change the email of a member step 2.
 ----------------------------------------------------------------------
*/

require_once("../class/config_nochk.php");

include("head.php");
?>
</head>
<body>
<h3><?php __("Change the email of the account"); ?></h3>
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p></body></html>";
		exit();
	}
?>
<form method="post" action="mem_cm2.php">
	<table border="1" cellspacing="0" cellpadding="4">
		<tr><td colspan="2"><input type="hidden" name="usr" value="<?php echo $usr; ?>" /><input type="hidden" name="cookie" value="<?php echo $cookie; ?>" />
<?php __("Change the email of the account"); ?><br />
		<?php __("Enter the key you got when you requested the mailbox change, then click the OK button."); ?></td></tr>
		<tr><th><label for="cle"><?php __("Key"); ?></label></th><td><input type="text" class="int" name="cle" id="cle" value="<?php echo $cle; ?>" size="8" maxlength="8" /></td></tr>
		<tr><td align="center" colspan="3"><input type="submit" class="inb" name="submit" value="<?php __("OK"); ?>" /></td></tr>
	</table>
</form>
</body>
</html>
