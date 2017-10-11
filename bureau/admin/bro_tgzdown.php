<?php
/*
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
*/

/**
 * Returns the current folder's in compressed format,
 * used from the File Browser
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");

$fields = array (
	"dir"    => array ("get", "string", "/"),
);
getFields($fields);


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
