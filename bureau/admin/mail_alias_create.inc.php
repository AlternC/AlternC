<?php 
/*
 mail_alias_create.php,v 1.3 2006/01/12 01:10:48 anarcat Exp $
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
 Original Author of file: Benjamin Sonntag
 Purpose of file: Create a new mail account
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$details=$mail->mail_get_details($mail_id)) {
	$error=$err->errstr();
	echo $error;
}
?>
<?php

if (isset($error) && $error) {
	echo "<p class=\"error\">$error</p>";
}

$dom_list = $mail->enum_domains;
?>
<form action="mail_alias_doedit.php" method="post" name="mail_create" id="main" onsubmit="return is_valid_mail(document.getElementById('mail_arg').value+document.getElementById('dom_id').options[document.getElementById('dom_id').selectedIndex].text);">
<table>
<tr> 
    <td> 
      <input type="text" class="inb" name="mail_arg" id="mail_arg" value="" size="20" maxlength="255"  /> 
    </td> 
    <td><select name="dom_id" id="dom_id" ><?php foreach($dom_list as $key => $val){ ?><option value="<?php echo urlencode($val['id']) ?>"><?php echo "@".$val["domaine"] ?> </option><?php } ?> </select><td>
    <td><input type="submit" class="inb" name="submit" value="<?php __("Create this alias"); ?>" /></td>
</table>
<input type="hidden" class="inb" name="mail_id" value="<?php echo $mail_id ; ?>" />
<input type="hidden" class="inb" name="address_full" id="address_full" value="<?php echo $details["address_full"] ; ?>" />


</form>
