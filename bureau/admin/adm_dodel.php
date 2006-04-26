<?php
/*
 $Id: adm_dodel.php,v 1.2 2004/05/19 14:23:06 benjamin Exp $
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
 Purpose of file: Delete a member
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
  __("This page is restricted to authorized staff");
  exit();
}


if (!is_array($d)) {
  $d[]=$d;
}

reset($d);
while (list($key,$val)=each($d)) {
  if (!$admin->checkcreator($val)) {
    __("This page is restricted to authorized staff");
    exit();
  }
  if (!($u=$admin->get($val)) || !$admin->del_mem($val)) {
    $error.=sprintf(_("Member '%s' does not exist"),$val)."<br />";
  } else {
    $error.=sprintf(_("Member %s successfully deleted"),$u["login"])."<br />";
  }
}
include("adm_list.php");
exit();

?>
