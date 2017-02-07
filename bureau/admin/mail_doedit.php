<?php
/*
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2000-2012 by the AlternC Development Team.
 https://alternc.org/
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
 Purpose of file: Edit mail account settings
 ----------------------------------------------------------------------
*/


require_once("../class/config.php");

$fields = array (
		 "mail_id" =>array ("post","integer",""),
		 "pass" => array ("post","string",""),
		 "passconf" => array("post","string",""),
		 "quotamb" => array("post","integer",0),
		 "enabled" => array("post","boolean",true),
		 "islocal" => array("post","boolean",true),
		 "recipients" => array("post","string",""),
		 );

getFields($fields);

$isedit=true; // if we go back to edit, it will know ;)
$error="";

// We check that email first ... so that we can compare its status with our ...
if (!$res=$mail->get_details($mail_id)) {
  $error=$err->errstr();
  include("main.php");
  exit();
} else {
  
  
  /*
   * checking the password
   */
  if(isset($pass) && $pass != ""){
    if($pass != $passconf){
      $error = _("Passwords do not match");
      include ("mail_edit.php");
      exit();
    } else {
      if (!$mail->set_passwd($mail_id,$pass)) { /* SET THE PASSWORD */
	$error=$err->errstr();
	include ("mail_edit.php");
	exit();
      } else {
	$error.=$err->errstr()."<br />";
      }
    }	
  }


  /* 
   * now the enable/disable status
   */
  if ($res["enabled"] && !$enabled) {
    if (!$mail->disable($mail_id)) { /* DISABLE */
      $error=$err->errstr();
      include ("mail_edit.php");
      exit();
    } else {
      $error.=$err->errstr()."<br />";
    }
  }
  if (!$res["enabled"] && $enabled) {
    if (!$mail->enable($mail_id)) { /* ENABLE */
      $error=$err->errstr();
      include ("mail_edit.php");
      exit();
    } else {
      $error.=$err->errstr()."<br />";
    }
  }


  /* 
   * now the islocal + quota + recipients 
   */
  if (!$mail->set_details($mail_id,$islocal,$quotamb,$recipients)) { /* SET OTHERS */
    $error=$err->errstr();
    include ("mail_edit.php");
    exit();
  } else {
    $error.=$err->errstr()."<br />";
  }


  /* 
   * Other elements by hooks
   */
  $rh=$hooks->invoke("mail_edit_post",array($mail_id));
  if (in_array(false,$res,true)) {
    include ("mail_edit.php");
    exit();
  } else {
    foreach($rh as $h) if ($h) $error.=$h."<br />";
  }

} 

if (!$error || !trim($error,"<br />")) {
	unset($error);
	$success=_("Your email has been edited successfully");
}

$_REQUEST["domain_id"]=$dom->get_domain_byname($res["domain"]);
include("mail_list.php");

