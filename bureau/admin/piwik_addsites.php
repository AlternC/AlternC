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
 * Add a piwik website using piwik's API
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
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
  $msg->raise("ERROR", "piwik", _("All fields are mandatory"));
} elseif ( $piwik->site_add($site_name, $site_urls) ) {
  $msg->raise("INFO", "piwik", _("Website added Successfully"));
}
include_once("piwik_sitelist.php");

?>
