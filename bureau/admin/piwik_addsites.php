<?php
/*
 $Id: piwik_addaccount.php,v 1.5 2006/01/12 01:10:48 anarcat Exp $
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
 Purpose of file: Ask the required values to add a ftp account
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"site_name" 		=> array ("post", "string", ""),
	"site_urls" 		=> array ("post", "string", ""),
);
getFields($fields);

if(empty($site_name)) $site_name=$site_urls;

if (empty($site_name)) {
  $error=("Error : missing arguments.");
} elseif (! $piwik->site_add($site_name, $site_urls) ) {
  $error=_("Error during adding website.<br/>".$err->errstr());
} else {
  $error=_("Successfully add website");
}
include_once("piwik_sitelist.php");

?>
