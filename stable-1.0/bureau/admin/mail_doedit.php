<?php
/*
 $Id: mail_doedit.php,v 1.5 2006/01/12 01:10:48 anarcat Exp $
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
 Original Author of file: Benjamin Sonntag, Franck Missoum
 Purpose of file: DO edit a mailbox, or alias
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$error_edit="";
$trash=new m_trash();
$trash->getfromform();


$fields = array (
	"domain"    => array ("request", "string", ""),
	"email"       => array ("request", "string", ""),
	"pop"       => array ("request", "integer", 0),
	"pass"       => array ("request", "string", ""),
	"passconf"       => array ("request", "string", ""),
	"alias"       => array ("request", "string", ""),
);
getFields($fields);


if ($pass != $passconf) {
	$error = _("Passwords do not match");
	include ("mail_edit.php");
	exit();
}

if (!$mail->put_mail_details($email,$pop,$pass,$alias,$trash->expiration_date_db)) {
	$error_edit=$err->errstr();
            $addok=0;
		include ("mail_edit.php");

} else {
            $ok=sprintf(_("The email address <b>%s</b> has been successfully changed"),$email)."<br />";
            $addok=1;
            $t=explode("@",$email);
            $email=$t[0];
	    $error=$ok;
	    include("mail_list.php");
	    exit();
}
?>
