<?php
/*
 $Id: adm_login.php,v 1.4 2005/04/01 17:13:10 benjamin Exp $
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
 Purpose of file: Connect a super-user to another account
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

// If we just want to return to our previous session
$oldid=intval($_COOKIE['oldid']);
if ($oldid) {
  setcookie('oldid','',0,'/admin/');

  $db->query("select lastip from membres where uid='$oldid';");
  $db->next_record();
  if ($db->f("lastip") != getenv("REMOTE_ADDR") ) {
    die('Error : bad IP address');
  }

  if (!$mem->setid($oldid)) {
    $oldid=null;
    $error=$err->errstr();
    include("index.php");
    exit();
  }
  $oldid=null;
  include_once("main.php");
  exit();
}

if (!$admin->enabled) {
  __("This page is restricted to authorized staff");
  exit();
}

$fields = array (
		 "id"    => array ("request", "integer", 0),
		 );
getFields($fields);

$subadmin=variable_get("subadmin_restriction");

if ($subadmin==0 && !$admin->checkcreator($id)) {
  __("This page is restricted to authorized staff");
  exit();
}

if (!$r=$admin->get($id)) {
  $error=$err->errstr();
} else {
  setcookie('oldid',$cuid,0,'/admin/');
  if (!$mem->setid($id)) {
    $error=$err->errstr();
    include("index.php");
    exit();
  }
  
  include_once("main.php");
  exit();
}

include_once("head.php");

?>
<h3><?php __("Member login"); ?></h3>
<?php

if ($error) {
  echo "<p class=\"error\">$error</p>";
  include_once("foot.php");
  exit();
}
?>
<?php include_once("foot.php"); ?>
