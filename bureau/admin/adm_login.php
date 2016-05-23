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

/*
 We come into this page in two situations : 
 * with a user id to go to (we check the current account is admin and is allowed to connect to this account)
 * with no parameter when the admin want to go back to his admin account.
 */

$fields = array (
        "id"                => array ("post", "integer", ""),
);
getFields($fields);

// * with no parameter when the admin want to go back to his admin account.  
if ( empty($id) && isset($_COOKIE["oldid"]) && !empty($_COOKIE["oldid"])) {
  // We check the cookie's value : 
  list($newuid,$passcheck)=explode("/",$_COOKIE["oldid"]);
  $newuid=intval($newuid); 
  if (!$newuid) {
    $error=_("Your authentication information are incorrect");
    include("index.php");
    exit();
  }
  $admin->enabled=true;
  $r=$admin->get($newuid);
  if ($passcheck!=md5($r["pass"])) {
    $error=_("Your authentication information are incorrect");
    include("index.php");
    exit();
  }

  if ($r['lastip'] != get_remote_ip() ) {
    $error=_("Your IP is incorrect.");
    include("index.php");
    exit();
  }
  // FIXME we should add a peremption date on the cookie

  // Ok, so we remove the cookie : 
  setcookie('oldid','',0,'/');
  unset($_COOKIE['oldid']);

  // And we go back to the former administrator account : 
  if (!$mem->setid($newuid)) {
    $error=$err->errstr();
    include("index.php");
    exit();
  }

  include_once("adm_list.php");
  exit();
}


//  * with a user id to go to (we check the current account is admin and is allowed to connect to this account) 
if (!$admin->enabled) {
  __("This page is restricted to authorized staff");
  exit();
}

// Depending on subadmin_restriction, a subadmin can (or cannot) connect to account he didn't create
$subadmin=variable_get("subadmin_restriction");
if ($subadmin==0 && !$admin->checkcreator($id)) {
  __("This page is restricted to authorized staff");
  exit();
}

if (!$r=$admin->get($id)) {
  $error=$err->errstr();
} else {
  $oldid=$cuid."/".md5($mem->user["pass"]);
  setcookie('oldid',$oldid,0,'/');
  $_COOKIE['oldid']=$oldid;

  if (!$mem->setid($id)) {
    $error=$err->errstr();
    include("index.php");
    exit();
  }
  // Now we are the other user :) 
  include_once("main.php");
  exit();
}

// If there were an error, let's show it :
include_once("head.php");

?>
<h3><?php __("Member login"); ?></h3>
<?php

if (isset($error) && $error) {
  echo "<p class=\"alert alert-danger\">$error</p>";
}
include_once("foot.php"); 
?>
