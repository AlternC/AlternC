<?php 

/*
 mail_redirection_edit.php, author: squidly
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
?>

<form action="mail_redirection_doedit.php" method="post" name="main" id="main">
<table>
<?php
if (!isset($mail_id) || is_null($mail_id)) { // if no mail_id
  die('missing mail id ERROR');
}

$lst_rcp=$mail_redirection->recipients_get_array($mail_id) ;
$nb_rcp=count($lst_rcp);
for ($ii=0;$ii <= count($lst_rcp)+10; $ii++) { 
  ?>
  <tr>
    <td>
      <input type="text" class="int" name="rcp[<?php echo $ii?>]" id="rcp-<?php echo $ii?>" value="<?php 
	if (isset($lst_rcp[$ii])){ ehe($lst_rcp[$ii]);} ?>" size="20" maxlength="255" onKeyUp="javascript:is_valid_mail(<?php echo $ii; ?>);" />
    </td>
    <td><img id="valid-rcp-<?php echo $ii?>" alt="" src="" ></td>
    <td><a href="#" onclick="javascript:delete_one_recipients(<?php echo $ii?>);" ><?php __("Delete");?></a></td>
  </tr>
<?php
}  // foreach ?>
  <tr>
    <td colspan=3 align="right">
      <input type="button" class="inb" name="clear" value="<?php __("Clear all redirections"); ?>" onclick="javascript:delete_all_recipients();"/>
    </td>
  </tr>
  <tr>
    <td colspan=3>
<input type="hidden" class="inb" name="mail_id" value="<?php echo $mail_id ; ?>" />
      <input type="submit" class="inb" name="submit" value="<?php __("Change this email address"); ?>" />
      <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='mail_properties.php?mail_id=<?php echo urlencode($mail_id); ?>'"/>
    </td>
  </tr>
</table>
<script type="text/javascript">

function delete_one_recipients(idelem) {
  document.getElementById('rcp-'+idelem).value='';
  is_valid_mail(idelem);
}

function delete_all_recipients() {
  var answer = confirm ("<?php __("Are you sure you want to clear all redirections?");?>");
  if (answer) {
    <?php for ($ii=0;$ii <= count($lst_rcp)+10; $ii++) { ?>
    delete_one_recipients(<?php echo $ii;?>);
    <?php } // for ?>
  }
}



<?php for ($ii=0;$ii <= count($lst_rcp)+10; $ii++) { ?>
is_valid_mail(<?php echo $ii;?>);
<?php } // for ?>


</script>
</form>
