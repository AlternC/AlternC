<?php
/*
 $Id: sta2_doedit_raw.php,v 1.1 2004/05/24 17:02:28 anonymous Exp $
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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$id) {
	$error=_("No Statistics selected!");
} else {
	$r=$sta2->put_stats_details_raw($id,$dir);
	if (!$r) {
		$error=$err->errstr();
		include("sta2_edit_raw.php");
		exit();
	} else {
		$error=_("The Statistics has been successfully changed");
		include("sta2_list.php");
		exit();
	}
}
?>
