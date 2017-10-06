<?php
/*
 adm_doadd.php
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2002-2017 by the AlternC Development Team.
 https://alternc.com/
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
 Purpose of file: validate and create a new member
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
	$msg->raise("ERROR", "admin", _("This page is restricted to authorized staff"));
	echo $msg->msg_html_all();
	exit;
}
$fields = array (
	"login"    		=> array ("post", "string", ""),
	"pass"    		=> array ("post", "string", ""),
	"passconf"    		=> array ("post", "string", ""),
	"canpass"    		=> array ("post", "integer", ""),
	"db_server_id"          => array ("post", "integer", ""),
	"notes"    		=> array ("post", "string", ""),
	"nom"    		=> array ("post", "string", ""),
	"prenom"    		=> array ("post", "string", ""),
	"nmail"    		=> array ("post", "string", ""),
	"type"    		=> array ("post", "string", ""),
	"create_dom_list"    	=> array ("post", "string", ""),
	"create_dom"    	=> array ("post", "integer", 0),
	"submit"    		=> array ("post", "string", ""),
);
getFields($fields);

if ($create_dom && !empty($create_dom_list) ) {
  $dom_to_create = $create_dom_list;
} else {
  $dom_to_create = false;
}

if ($pass != $passconf) {
	$msg->raise("ERROR", "admin", _("Passwords do not match"));
	include("adm_add.php");
	exit();
}

// Attemp to create, exit if fail
if (!($u=$admin->add_mem($login, $pass, $nom, $prenom, $nmail, $canpass, $type, 0, $notes, 0, $dom_to_create, $db_server_id))) {
	include ("adm_add.php");
	exit;
}

$msg->raise("INFO", "admin", _("The new member has been successfully created"));

include("adm_list.php");
exit;

?>
