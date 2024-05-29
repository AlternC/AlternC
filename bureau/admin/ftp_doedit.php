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
 * change settings of an FTP account
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");
$fields = array (
  "id"        => array ("post", "integer", ""),
  "create"        => array ("post", "integer", ""),
  "pass"        => array ("post", "string", ""),
  "passconf"        => array ("post", "string", ""),
  "prefixe"        => array ("post", "string", ""),
  "login"        => array ("post", "string", ""),
  "dir"        => array ("post", "string", ""),
);
getFields($fields);

if (! $id && !$create) { //not a creation and not an edit
  $msg->raise("ERROR", "ftp", __("Error: neither a creation nor an edition", "alternc", true));
  include("ftp_list.php");
  exit();
}

if (! $id ) { //create
  $r=$ftp->add_ftp($prefixe,$login,$pass,$dir);
} else { // edit
  $r=$ftp->put_ftp_details($id,$prefixe,$login,$pass,$dir);
}

if (!$r) {
  $is_include=true;
  $rr[0]["prefixe"]=$prefixe;
  $rr[0]["login"]=$login;
  $rr[0]["dir"]=$dir;
  include_once("ftp_edit.php");
  exit();
} else {
  if ($create)
    $msg->raise("INFO", "ftp", __("The FTP account has been successfully created", "alternc", true));
  else
    $msg->raise("INFO", "ftp", __("The FTP account has been successfully saved", "alternc", true));

  include("ftp_list.php");
  exit();
}

?>
