<?php
/*
 $Id: adm_dodefquotas.php,v 1.3 2006/01/24 05:03:30 joe Exp $
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2006 Le réseau Koumbit Inc.
 http://koumbit.org/
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
 Purpose of file: Manage the default quotas
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
	$msg->raise("ERROR", "admin", _("This page is restricted to authorized staff"));
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
    $msg->raise("INFO", "admin", _("Account type"). " \"".htmlentities($type)."\" "._("added"));
  } else {
    $msg->raise("ERROR", "admin", _("Account type"). " \"".htmlentities($type)."\" "._("could not be added"));
  }
  include("adm_defquotas.php");
} else if($action == "delete") {
  if($del_confirm == "y"){
    if(!empty($type)) {
      if($quota->deltype($type)) {
        $msg->raise("INFO", "admin", _("Account type"). " \"".htmlentities($type)."\" "._("deleted"));
      } else {
        $msg->raise("ERROR", "admin", _("Account type"). " \"".htmlentities($type)."\" "._("could not be deleted"));
      }
    }
    include("adm_defquotas.php");
  }else{
    include("head.php");
    ?>
    <h3><?php printf(_("Deleting quota %s"),$type); ?> : </h3>

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
    $msg->raise("INFO", "admin", _("Default quotas successfully changed"));
  } else {
    $msg->raise("ERROR", "admin", _("Default quotas could not be set."));
  }
  include("adm_panel.php");
}
?>
