<?php
/*
 $Id: sql_doadd.php,v 1.2 2003/06/10 07:20:29 root Exp $
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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$fields = array (
        "dbn"                 => array ("post", "string", ""),
);
getFields($fields);
if (!$quota->cancreate("mysql")) {
  $error=_("You ");
  include("sql_list.php");
  exit;
}
$q=$quota->getquota("mysql");

if($q['u'] > 0){
  $dbname=$mem->user["login"]."_".$dbn;
} else {
  $dbname=$mem->user["login"];
}

if(!$mysql->add_db($dbname)) {
  $error=$err->errstr();
  include("sql_list.php");
  exit;
}


include("sql_list.php");

?>
