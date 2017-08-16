<?php
/*
 $Id: hta_doadd.php,v 1.1.1.1 2003/03/26 17:41:29 root Exp $
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
 Original Author of file: Franck Missoum
 Purpose of file: Protect a folder
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");


$fields = array (
	"dir"     => array ("post", "string", ""),
);
getFields($fields);

if(empty($dir)) {
	$msg->raise("Error", "hta", _("No directory specified"));
	include("hta_list.php");
} else if(!$hta->CreateDir($dir)) {
	$is_include=true;
	include("hta_add.php");
} else {
	$msg->raise("Ok", "hta", _("Folder %s is protected"), $dir);  // à traduire
	include("hta_list.php");
}
?>
