<?php
/*
 $Id: mail_dodel.php,v 1.2 2003/06/10 06:45:16 root Exp $
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
 Purpose of file: Delete a mailbox
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!is_array($d)) {
        $d[]=$d;
}


reset($d);
while (list($key,$val)=each($d)) {
	if (!$mail->del_mail($val)) {
		$error.=sprintf(_("The mailbox <b>%s</b> does not exist!")."<br />",$val);
		echo $error;
	} else {
		$error.=sprintf(_("The mailbox <b>%s</b> has been deleted!")."<br />",$val); 
		echo $error;		
	}
include("mail_list.php");
}

exit();

?>