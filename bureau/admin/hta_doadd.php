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
 * Protects a folder using a .htaccess / .htpasswd pair for apache2
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");

$fields = array (
	"dir"     => array ("post", "string", ""),
);
getFields($fields);

if(empty($dir)) {
	$msg->raise("ERROR", "hta", __("No directory specified", "alternc", true));
	include("hta_list.php");
} else if(!$hta->CreateDir($dir)) {
	$is_include=true;
	include("hta_add.php");
} else {
	include("hta_list.php");
}
?>
