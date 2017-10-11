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
 * edit an email account settings
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");

$fields = array (
		 "mail_id" =>array ("post","integer",""),
		 "new_account" =>array ("post","integer",""),
		 "pass" => array ("post","string",""),
		 "passconf" => array("post","string",""),
		 "quotamb" => array("post","integer",0),
		 "enabled" => array("post","boolean",true),
		 "islocal" => array("post","boolean",true),
		 "recipients" => array("post","string",""),
		 );

getFields($fields);

$isedit=true; // if we go back to edit, it will know ;)

// We check that email first ... so that we can compare its status with our ...
if (!$res=$mail->get_details($mail_id)) {
  include("mail_list.php");
  exit();
} else {
  
  
  /*
   * checking the password
   */
  if($pass != $passconf){
    $msg->raise("ERROR", "mail", _("Passwords do not match"));
    include ("mail_edit.php");
    exit();
  } else {
    $canbeempty = ($islocal != 1 || ($islocal == 1 && !$new_account))?true:false;
    if ($new_account || !empty($pass) || $islocal != 1) {
      if ($islocal != 1)
        $pass = ""; 

      if (!$mail->set_passwd($mail_id,$pass,$canbeempty)) { /* SET THE PASSWORD */
        include ("mail_edit.php");
        exit();
      }
    } else if (!$new_account && empty($pass) && $islocal == 1 && $res['password'] == "") {
      if (!$mail->set_passwd($mail_id,$pass, false)) { /* SET THE PASSWORD */
        include ("mail_edit.php");
        exit();
      }
    }
  }	


  /* 
   * now the enable/disable status
   */
  if ($res["enabled"] && !$enabled) {
    if (!$mail->disable($mail_id)) { /* DISABLE */
      include ("mail_edit.php");
      exit();
    }
  }
  if (!$res["enabled"] && $enabled) {
    if (!$mail->enable($mail_id)) { /* ENABLE */
      include ("mail_edit.php");
      exit();
    }
  }


  /* 
   * now the islocal + quota + recipients 
   */
  if (!$mail->set_details($mail_id,$islocal,$quotamb,$recipients)) { /* SET OTHERS */
    include ("mail_edit.php");
    exit();
  }


  /* 
   * Other elements by hooks
   */
  $rh=$hooks->invoke("mail_edit_post",array($mail_id));
  if (in_array(false,$rh,true)) {
    include ("mail_edit.php");
    exit();
  } else {
    foreach($rh as $h) if ($h) $msg->raise("ERROR", "mail", $h);
  }

} 

if ($new_account)
  $msg->raise("INFO", "mail", _("Your email has been created successfully"));
else
  $msg->raise("INFO", "mail", _("Your email has been edited successfully"));

$_REQUEST["domain_id"]=$dom->get_domain_byname($res["domain"]);
include("mail_list.php");

