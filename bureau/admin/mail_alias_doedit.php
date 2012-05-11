<?
/*
 mail_alias_doedit.php, author: squidly
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
	"mail_arg" => array ("request", "string",0),
	"mail_id" => array ("request", "integer",0),
	"domain" => array ("request", "string",0),
	"address_full" => array ("request", "string",0),
	"dom_id" => array ("request", "integer",0),
	"rcp" =>array("request", "array", "")
);
getFields($fields);

$alias_retour=array ();
$alias_retour=$mail->create($dom_id,$mail_arg);
//setting up the alias.
if(!$mail_alias->setalias($alias_retour["mail_id"],$address_full)){
//if fails redirect to creation with error message.
$error=sprintf(_("Alias: %s already created"), $mail_arg);
include("mail_alias_create.inc.php");
}else{
//redirection to the properties list.
header ("Location: mail_properties.php?mail_id=$mail_id");
}




