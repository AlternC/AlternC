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
 * Create a new MySQL database for the account 
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */

require_once("../class/config.php");

$fields = array (
        "dbn"                 => array ("post", "string", ""),
);

getFields($fields);

if (!$quota->cancreate("mysql")) {
  $msg->raise("ALERT", "mysql", _("Can't create a database: your quota is over"));
  include("sql_list.php");
  exit;
}

$q=$quota->getquota("mysql");

if($q['u'] > 0){
  $dbname=$mem->user["login"]."_".$dbn;
} else {
  $dbname=$mem->user["login"];
}

if($mysql->add_db($dbname)) {
  $msg->raise("INFO", "mysql", _("The database '%s' has been created."),$dbname);
  header('Location: sql_getparam.php?dbname='.htmlentities($dbname));
} else {
  include("sql_list.php");
}


?>
