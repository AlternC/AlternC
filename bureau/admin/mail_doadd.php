<?php
/*
 $Id: mail_doadd.php,v 1.3 2006/01/12 01:10:48 anarcat Exp $
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
 Purpose of file: Create a new mail account
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if ($pass != $passconf) {
	$error = _("Passwords do not match");
	include("mail_add.php");
	exit();
}

if (!$mail->add_mail($domain,$email,$pop,$pass,$alias)) {
	$error=$err->errstr();
        $addok=0;
	include ("mail_add.php");
} else {
	$addok=1;
	$error=sprintf (_("The email address <b>%s</b> has been successfully created"),"$email@$domain");
	if ($many) {
		unset($email,$pass,$alias);
		include("mail_add.php");
 	} else {
		include("mail_list.php");
	}
	exit();
}

?>