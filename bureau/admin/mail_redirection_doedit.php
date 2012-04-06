<?
/*
 mail_redirection_doedit.php, author: squidly
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
	"alias" => array ("request", "string",0),
	"mail_id" => array ("request", "integer",0),
	"domain" => array ("request", "string",0),
	"address_full" => array ("request", "string",0),
	"dom_id" => array ("request", "integer",0),
	"rcp" =>array("request", "array", "")
);
getFields($fields);
//obon on crée les redirect..
$tst=$mail_redirection->setredirection($mail_id, $rcp);
if($tst==false){
	$error="One ore more redirection could not be added";
}



//redirection si tout se passe bien.
header ("Location: mail_properties.php?mail_id=$mail_id"); 

?>
