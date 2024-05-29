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
 * Any ADMIN account can impersonate to any other account by using this page.
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");

/*
 We come into this page in two situations : 
 * with a user id to go to (we check the current account is admin and is allowed to connect to this account)
 * with no parameter when the admin want to go back to his admin account.
 */

$fields = array (
        "id"                => array ("get", "integer", ""),
);
getFields($fields);

// * with no parameter when the admin want to go back to his admin account.  
if ( empty($id) && isset($_COOKIE["oldid"]) && !empty($_COOKIE["oldid"])) {
  // We check the cookie's value : 
  list($newuid,$passcheck)=explode("/",$_COOKIE["oldid"]);
  $newuid=intval($newuid); 
  if (!$newuid) {
    $msg->raise("ERROR", "admin", __("Your authentication information are incorrect", "alternc", true));
    include("index.php");
    exit();
  }
  $admin->enabled=true;
  $r=$admin->get($newuid);
  if ($passcheck!=md5($r["pass"])) {
    $msg->raise("INFO", "admin", __("Your authentication information are incorrect", "alternc", true));
    include("index.php");
    exit();
  }

  // Ok, so we remove the cookie : 
  setcookie('oldid','',0,'/');
  unset($_COOKIE['oldid']);

  // And we go back to the former administrator account : 
  if (!$mem->setid($newuid)) {
    include("index.php");
    exit();
  }

  include_once("adm_list.php");
  exit();
}


//  * with a user id to go to (we check the current account is admin and is allowed to connect to this account) 
if (!$admin->enabled) {
  $msg->raise("ERROR", "admin", __("This page is restricted to authorized staff", "alternc", true));
  echo $msg->msg_html_all();
  exit();
}

// Depending on subadmin_restriction, a subadmin can (or cannot) connect to account he didn't create
$subadmin=variable_get("subadmin_restriction");
if ($subadmin==0 && !$admin->checkcreator($id)) {
  $msg->raise("ERROR", "admin", __("This page is restricted to authorized staff", "alternc", true));
  echo $msg->msg_html_all();
  exit();
}

if ($r=$admin->get($id)) {
  $oldid=$cuid."/".md5($mem->user["pass"]);
  setcookie('oldid',$oldid,0,'/');
  $_COOKIE['oldid']=$oldid;

  if (!$mem->setid($id)) {
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
echo $msg->msg_html_all();

include_once("foot.php"); 
?>
