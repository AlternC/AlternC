<?php
/*
 $Id: bro_tgzdown.php,v 1.2 2004/09/06 18:14:36 anonymous Exp $
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
 Original Author of file: Sonntag Benjamin
 Purpose of file: Return the current folder in a compressed file
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$p=$bro->GetPrefs();
switch ($p["downfmt"]) {
	case 0:
		$bro->DownloadTGZ($dir);
		break;
	case 1:
		$bro->DownloadTBZ($dir);
		break;
	case 2:
		$bro->DownloadZIP($dir);
		break;
	case 3:
		$bro->DownloadZ($dir);
		break;
}
?>
