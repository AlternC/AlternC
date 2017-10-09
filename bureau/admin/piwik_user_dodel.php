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
 * delete piwik users
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */
require_once("../class/config.php");

$fields = array (
  "confirm_del"    	=> array ("post", "string", ""),
  "login"    		=> array ("post", "string", ""),
);
getFields($fields);

if (empty($login)) {
  $msg->raise("ERROR", "piwik", _("Missing login parameters"));
  include('piwik_userlist.php'); 
  exit;
} 

if(!empty($confirm_del)) {
  // does this piwik acount still have websites ? can we delete it then ?
  if ($piwik->user_has_sites()) {
    $msg->raise("ALERT", "piwik", _("To be able to delete the last user account, you must first remove all the piwik sites"));
  } else if ($piwik->user_delete($login) ) {
    $msg->raise("INFO", "piwik", _("Account %s has been successfully deleted"), $login);
  }

  include('piwik_userlist.php'); 
  exit;
}

include_once('head.php');

?>
<h3><?php __("Piwik accounts deletion confirm"); ?></h3>
<hr id="topbar"/>
<br />
  <?php printf(_("Do you really want to delete the Piwik account %s ?"),$login);?>
<br />
<br />

  <form method="post" action="piwik_user_dodel.php" name="main" id="main">
  <?php csrf_get(); ?>
    <input type="hidden" name="login"  value="<?php ehe($login);?>" />
    <input type="submit" class="inb" name="confirm_del" value="<?php __("Delete")?>" />
    <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='piwik_userlist.php'" />
  </form>
  
<?php
  include_once('foot.php');
  exit();
?>
