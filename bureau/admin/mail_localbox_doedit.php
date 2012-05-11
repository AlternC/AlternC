<?
/*
 mail_localbox_doedit.php, author: squidly
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
	"local" => array ("request", "integer",0),
	"mail_id" => array ("request", "integer",0),
	"is_local" => array ("request", "integer",0)
);
getFields($fields);
/*si local vaut non =
	si boite pas deja locale : ne rien faire
	si boite locale: suprimmer entré dans mailbox
	niveau system virer le dossier.
  si local vaut oui =
	si boite pas deja local: ajout entré table mailbox
	si boite locale: dire a l'utilisateur que c'est deja le cas.
*/
print_r($is_local);
//if we are already processing a localy hosted mail
if(isset($is_local) && intval($is_local) == 1){
	//if user chose yes to localbox
	if($local == 1){
		$error = _("Already Activated");
		header ("Location: /mail_properties.php?mail_id=$mail_id");
	}else{
		$mail_localbox->unset_localbox($mail_id);
		header ("Location: /mail_properties.php?mail_id=$mail_id");
	}
}elseif( intval($is_local) == 0 ){

	if($local == 0){
		$error = _("Already disactivated");
		header ("Location: /mail_properties.php?mail_id=$mail_id");
	}else{
		//echo "processing mail to localbox";
		$mail_localbox->set_localbox($mail_id);
		header ("Location: /mail_properties.php?mail_id=$mail_id");
	}



}


