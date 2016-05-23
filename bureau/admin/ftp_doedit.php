<?php
/*
 $Id: ftp_doedit.php,v 1.3 2006/01/12 01:10:48 anarcat Exp $
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
 Purpose of file: Editing an ftp account
 ----------------------------------------------------------------------
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

if ($pass != $passconf) {
  $error = _("Passwords do not match");
  include_once("head.php");
  echo "<h3>"._("Create a FTP account")."</h3><p class=\"alert alert-danger\">$error</p>";
  include("foot.php");
  exit();
}

if (! $id && !$create) { //not a creation and not an edit
  $error=_("Error: neither a creation nor an edition");
  include("ftp_list.php");
  exit();
}

if (! $id ) { //create
  $r=$ftp->add_ftp($prefixe,$login,$pass,$dir);
} else { // edit
  $r=$ftp->put_ftp_details($id,$prefixe,$login,$pass,$dir);
}

if (!$r) {
  $error=$err->errstr();
  $is_include=true;
  $rr[0]["prefixe"]=$prefixe;
  $rr[0]["login"]=$login;
  $rr[0]["dir"]=$dir;
  include_once("ftp_edit.php");
  exit();
} else {
if ($create) {
  $error=_("The ftp account has been successfully created");
} else {
  $error=_("The ftp account has been successfully saved");
}
  include("ftp_list.php");
  exit();
}

?>
