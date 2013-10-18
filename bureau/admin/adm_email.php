<?php
/*
----------------------------------------------------------------------
AlternC - Web Hosting System
Copyright (C) 2000-2012 by the AlternC Development Team.
https://alternc.org/
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
  if ($admin->mail_all_members($subject,$message,$from)) {
    $error=_("The email was successfully sent");
  } else {
    $error=_("There was an error");
  }
}

if (isset($error) && $error) {
        echo "<p class=\"alert alert-danger\">$error</p>";
}

?>
<form method="post" action="adm_email.php">

<table cellspacing="1" cellpadding="4" border="0" align="center" class='tedit'>
	<tr>
	  <th align="right"><b><?php __("From");?></b></th>
	  <td><span><input type="text" name="from" size="45" maxlength="100" tabindex="2" value="<?php echo "no-reply@$L_FQDN" ?>" /></span></td>
	</tr>
	<tr>
	  <th align="right"><b><?php __("Subject");?></b></th>
	  <td><span><input type="text" name="subject" size="45" maxlength="100" tabindex="2" value="" /></span></td>
	</tr>
	<tr>
	  <th align="right" valign="top"> <span><b><?php __("Mail"); ?></b></span></th>
	  <td><span> <textarea name="message" rows="15" cols="35" wrap="virtual" style="width:450px" tabindex="3"></textarea></span></td>
	</tr>
	<tr>
	  <td class="catBottom" align="center" colspan="2"><input type="submit" value="<?php __("Send");?>" name="submit" /></td>
	</tr>
</table>

</form>

<?php include_once('foot.php');?>
