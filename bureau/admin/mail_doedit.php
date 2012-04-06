<?php
/*
 $Id: mail_doedit.php, author : squidly
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
 Purpose of file: Create a new mail account
 ----------------------------------------------------------------------
*/


require_once("../class/config.php");

$fields = array (
	"dom_id" =>array ("request","integer",""),
	"mail_id" => array ("request","integer",""),
	"pass" => array ("request","string",""),
	"passconf" => array("request","string",""),
	"is_enabled" => array("request","string",""),
	"enable" => array("request","string","")
);

getFields($fields);

/*
* checking the password
*/

if(isset($pass) && $pass != ""){
	if($pass != $passconf){
		$error = _("Password do not match");
		include ("mail_edit.php");
		exit();
	}else{
		//adding the password
		$mail->setpasswd($mail_id,$pass);
		header ("Location: /mail_properties.php?mail_id=$mail_id");
	}	
}
/*
* checking the activation state of the mail
* redirecting according to it.
*/
if($is_enabled == 1){
	if(intval($enable)==0){
		//desactivation	
		$mail->disable($mail_id);
		header ("Location: /mail_properties.php?mail_id=$mail_id");
	}else{
		$error = _("Already Activated");
		include ("mail_edit.php");
		exit();
	}
}elseif($is_enabled == 0){
	if(intval($enable)==0){
		// c'est dja inactif
		$error = _("Already disabled ");
		include ("mail_edit.php");
		exit();
	}else{
		//Activation
		$mail->enable($mail_id);
		header ("Location: /mail_properties.php?mail_id=$mail_id");
	}

}


