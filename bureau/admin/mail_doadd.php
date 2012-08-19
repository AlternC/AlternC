<?php
/*
 $Id: mail_doadd.php, author: squidly
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
	"mail_arg"     => array ("request", "string", ""),
	"domain_id"    => array ("request", "integer", ""),
	"domain"    => array ("request", "string", ""),
);
getFields($fields);


$res= array();
//FIXME seems good but maybe can be done in a more fashion way.
$res=$mail->create($domain_id,$mail_arg,$domain);

//once the mail created redirection to mail_properties.php, with the mail_id as parameters ( + domain_id )
if($res["mail_id"]== null){
	header ("Location: /mail_list.php?domain=$domain&domain_id=$domain_id");
}else{
	$test= 'mail_properties.php?mail_id='.$res["mail_id"]."&domain_id=$domain_id";
	header("Location: /$test");
}
?>
