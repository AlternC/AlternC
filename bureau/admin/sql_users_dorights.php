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
 * Manages the MySQL users Rights
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */

 require_once("../class/config.php");

$fields = array (
  "id"        => array ("post", "string", ""),
);
getFields($fields);

foreach($_POST as $k=>$v) {
  $keys[$k]=$v;
}

$cleanrights=$mysql->available_sql_rights();
foreach($mysql->get_dblist() as $d){
  $rights=array();
  foreach ($cleanrights as $r) {
    if (isset($keys[$d['db'].'_'.$r])) {
      $rights[]=$r; 
    }
  }  
  //add if empty rights
  $mysql->set_user_rights($id,$d['db'],$rights);
}

$msg->raise("INFO", "mysql", __("The rights has been successfully applied to the user", "alternc", true));

include("sql_users_list.php");

?>
