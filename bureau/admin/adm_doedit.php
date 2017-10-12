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
 * Edit an account settings (name, password, etc.)
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");

if (!$admin->enabled) {
  $msg->raise("ERROR", "admin", _("This page is restricted to authorized staff"));
  echo $msg->msg_html_all();
  exit();
}

$subadmin=variable_get("subadmin_restriction");

$fields = array (
	"uid" => array ("post", "integer", 0),
	"enabled" => array ("post", "boolean", true),
	"pass" => array ("post", "string", ""),
	"passconf" => array ("post", "string", ""),
	"canpass" => array ("post", "boolean", true),
	"notes" => array ("post", "string", ""),
	"nom" => array ("post", "string", ""),
	"prenom" => array ("post", "string", ""),
	"nmail" => array ("post", "string", ""),
	"type" => array ("post", "string", ""),
	"duration" => array ("post", "integer", 0),
	"reset_quotas" => array ("post", "string", false),
);
getFields($fields);


if ($subadmin==0 && !$admin->checkcreator($uid)) {
  $msg->raise("ERROR", "admin", _("This page is restricted to authorized staff"));
  echo $msg->msg_html_all();
  exit();
}

if ($pass != $passconf) {
  $msg->raise("ERROR", "admin", _("Passwords do not match"));
  include("adm_edit.php");
  exit();
}
// When changing its own account, enabled forced to 1.
if ($uid==$mem->user["uid"]) {
  $enabled=1;
}

if (!$admin->update_mem($uid, $nmail, $nom, $prenom, $pass, $enabled, $canpass, $type, $duration, $notes, $reset_quotas)){
  include("adm_edit.php");
} else {
  $msg->raise("INFO", "admin", _("The member has been successfully edited"));
  include("adm_list.php");
}
