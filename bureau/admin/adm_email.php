<?php
/*
$Id: adm_email.php,v 1.1 2005/09/05 10:55:48 arnodu59 Exp $
----------------------------------------------------------------------
AlternC - Web Hosting System
Copyright (C) 2005 by the AlternC Development Team.
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

include("head.php");

if (!$admin->enabled) {
    __("This page is restricted to authorized staff");
    exit();
}

$fields = array (
	"subject"    		=> array ("post", "string", ""),
	"message"    		=> array ("post", "string", ""),
	"from"    		=> array ("post", "string", ""),
	"submit"    		=> array ("post", "string", ""),
);
getFields($fields);

?>
<h3><?php __("Send an email to all members"); ?></h3>
<?php

if ( !empty($submit) ) {
  if ($admin->mailallmembers($subject,$message,$from)) {
    $error=_("The email was successfully sent");
  } else {
    $error=_("There was an error");
  }
}

if (isset($error) && $error) {
        echo "<p class=\"error\">$error</p>";
}

?>
<form method="post" action="adm_email.php">

<table cellspacing="1" cellpadding="4" border="0" align="center">
	<tr>
	  <td align="right"><b><?php __("From");?></b></td>
	  <td><span><input type="text" name="from" size="45" maxlength="100" tabindex="2" value="<?php echo "no-reply@$L_FQDN" ?>" /></span></td>
	</tr>
	<tr>
	  <td align="right"><b><?php __("Subject");?></b></td>
	  <td><span><input type="text" name="subject" size="45" maxlength="100" tabindex="2" value="" /></span></td>
	</tr>
	<tr>
	  <td align="right" valign="top"> <span><b><?php __("Mail"); ?></b></span>
	  <td><span> <textarea name="message" rows="15" cols="35" wrap="virtual" style="width:450px" tabindex="3"></textarea></span>
	</tr>
	<tr>
	  <td class="catBottom" align="center" colspan="2"><input type="submit" value="<?php __("Send");?>" name="submit" /></td>
	</tr>
</table>

</form>

<?php include_once('foot.php');?>
