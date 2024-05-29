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
 * Change the default quotas
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");

if (!$admin->enabled) {
	$msg->raise("ERROR", "admin", __("This page is restricted to authorized staff", "alternc", true));
	echo $msg->msg_html_all();
	exit();
}
$fields = array (
	"action"    		=> array ("post", "string", ""),
	"type"    		=> array ("post", "string", ""),
	"del_confirm"  		=> array ("post", "string", ""),
);
getFields($fields);

if($action == "add") {

  if($quota->addtype($type)) {
    $msg->raise("INFO", "admin", __("Account type", "alternc", true). " \"".htmlentities($type)."\" ".__("added", "alternc", true));
  } else {
    $msg->raise("ERROR", "admin", __("Account type", "alternc", true). " \"".htmlentities($type)."\" ".__("could not be added", "alternc", true));
  }
  include("adm_defquotas.php");
} else if($action == "delete") {
  if($del_confirm == "y"){
    if(!empty($type)) {
      if($quota->deltype($type)) {
        $msg->raise("INFO", "admin", __("Account type", "alternc", true). " \"".htmlentities($type)."\" ".__("deleted", "alternc", true));
      } else {
        $msg->raise("ERROR", "admin", __("Account type", "alternc", true). " \"".htmlentities($type)."\" ".__("could not be deleted", "alternc", true));
      }
    }
    include("adm_defquotas.php");
  }else{
    include("head.php");
    ?>
    <h3><?php printf(__("Deleting quota %s", "alternc", true),$type); ?> : </h3>

    <form action="adm_dodefquotas.php" method="post">
 <?php csrf_get(); ?>
      <input type="hidden" name="action" value="delete" />
      <input type="hidden" name="type" value="<?php echo $type ?>" />
      <input type="hidden" name="del_confirm" value="y" />
      <p class="alert alert-warning"><?php __("WARNING: Confirm the deletion of the quota"); ?></p>
      <p><?php echo $type; ?></p>
      <blockquote>
      <input type="submit" class="inb ok" name="confirm" value="<?php __("Yes, delete this default quota"); ?>" />&nbsp;&nbsp;
      <input type="button" class="inb cancel" name="cancel" value="<?php __("No, don't delete this default quota"); ?>" onclick="document.location='adm_defquotas.php';" />
      </blockquote>
    </form>
    <?php
    include("foot.php");
  }
} else if($action == "modify") {
  reset($_POST);
  $c=array();
  foreach($_POST as $key => $val) {
    if($key == "action")
      continue;

    list($type, $q) = explode(":", $key, 2);
    $c[$type][$q] = abs(floatval($val));
  }

  if($quota->setdefaults($c)) {
    $msg->raise("INFO", "admin", __("Default quotas successfully changed", "alternc", true));
  } else {
    $msg->raise("ERROR", "admin", __("Default quotas could not be set.", "alternc", true));
  }
  include("adm_panel.php");
}
?>
