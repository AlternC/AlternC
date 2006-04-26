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
	__("This page is restricted to authorized staff");
	exit();
}

if($_POST["action"] == "add") {
  $type = $_POST['type'];

  $quota->addtype($type);
  $error=_("Account type"). " \"$type\" "._("added");
} else if($_POST["action"] == "delete") {
  $quota->deltype($_POST['type']);
  $error=_("Account type"). " \"$type\" "._("deleted");
} else if($_POST["action"] == "modify") {
  reset($_POST);
  $c=array();
  foreach($_POST as $key => $val) {
    if($key == "action")
      continue;

    list($type, $q) = explode(":", $key, 2);
    $c[$type][$q] = $val;
  }

  $quota->setdefaults($c);

  $error=_("Default quotas successfully changed");
}
include("adm_panel.php");
exit;
?>