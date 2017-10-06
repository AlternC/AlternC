<?php
/*
 $Id: sql_users_doadd.php,v 1.2 2003/06/10 07:20:29 nahuel Exp $
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
 Original Author of file: Nahuel ANGELINETTI
 Purpose of file:
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$fields = array (
	"usern"     => array ("post", "string", ""),
	"password"    => array ("post", "string", ""),
	"passconf"    => array ("post", "string", ""),
);
getFields($fields);

if (!empty($usern)) {
  if (!$mysql->add_user($usern,$password,$passconf)) {
    include("sql_users_add.php");
    exit;
  } else {
    $username=$mem->user["login"]."_".$usern;
    $msg->raise("INFO", "mysql", _("The user '%s' has been successfully created."),$username);
  }
} else {
  $usern=$mem->user["login"];
  if (!$mysql->add_user($usern,$password,$passconf)) {
    include("sql_users_add.php");
    exit;
  } else {
    $username=$mem->user["login"];
    $msg->raise("INFO", "mysql", _("The user '%s' has been successfully created."),$username);
  }
}

include("sql_users_list.php");

?>
