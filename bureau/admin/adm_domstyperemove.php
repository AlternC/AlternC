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
 * Change a domain type on the server
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");
$uid = $mem->user['uid'];

if (!$admin->enabled || $uid != 2000) {
    $msg->raise("ERROR", "admin", _("This page is restricted to authorized staff"));
    echo $msg->msg_html_all();
    exit();
}

$fields = array (
	"name"    => array ("request", "string", "")
);
getFields($fields);

if ( empty($name) || !$dom->domains_type_del($name) ) {
    $msg->raise("ERROR", "admin", _("Domain type can't be deleted"));
} else {
    $msg->raise("INFO", "admin", _("Domain type is deleted"));
}
include("adm_domstype.php");
?>
