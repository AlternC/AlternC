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
if (!$admin->enabled) {
    $msg->raise("ERROR", "admin", _("This page is restricted to authorized staff"));
    echo $msg->msg_html_all();
    exit();
}

$fields = array (
	"name"    		=> array ("post", "string", ""),
	"description"    	=> array ("post", "string", ""),
	"target"    		=> array ("post", "string", ""),
	"entry"    		=> array ("post", "string", ""),
	"compatibility"    	=> array ("post", "string", ""),
	"enable"    		=> array ("post", "string", ""),
	"only_dns"    		=> array ("post", "string", ""),
	"need_dns"    		=> array ("post", "string", ""),
	"advanced"    		=> array ("post", "string", ""),
	"create_tmpdir"    		=> array ("post", "string", ""),
	"create_targetdir"    		=> array ("post", "string", ""),
);
getFields($fields);

if (! $dom->domains_type_update($name, $description, $target, $entry, $compatibility, $enable, $only_dns, $need_dns, $advanced,$create_tmpdir,$create_targetdir) ) {
    include("adm_domstypedoedit.php");
} else {
    $msg->raise("INFO", "admin", _("Domain type is updated"));
    include("adm_domstype.php");
}
?>


