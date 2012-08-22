<?php
/*
 $Id: adm_doedit.php,v 1.6 2006/01/24 05:03:30 joe Exp $
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
 Purpose of file: Edit a member's parameters
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
  __("This page is restricted to authorized staff");
  exit();
}

$subadmin=variable_get("subadmin_restriction");

$fields = array (
	"uid" => array ("request", "integer", 0),
	"enabled" => array ("request", "boolean", true),
	"pass" => array ("request", "string", ""),
	"passconf" => array ("request", "string", ""),
	"canpass" => array ("request", "boolean", true),
	"notes" => array ("request", "string", ""),
	"nom" => array ("request", "string", ""),
	"prenom" => array ("request", "string", ""),
	"nmail" => array ("request", "string", ""),
	"type" => array ("request", "string", ""),
	"duration" => array ("request", "integer", 0),
	"reset_quotas" => array ("request", "string", false),
);
getFields($fields);


if ($subadmin==0 && !$admin->checkcreator($uid)) {
  __("This page is restricted to authorized staff");
  exit();
}

if ($pass != $passconf) {
  $error = _("Passwords do not match");
  include("adm_edit.php");
  exit();
}
// When changing its own account, enabled forced to 1.
if ($uid==$mem->user["uid"]) {
  $enabled=1;
 }

if (!$admin->update_mem($uid, $nmail, $nom, $prenom, $pass, $enabled, $canpass, $type, $duration, $notes, $reset_quotas)){
  $error=$err->errstr();
  include("adm_edit.php");
} else {
  $error=_("The member has been successfully edited");
  include("adm_list.php");
}
?>
