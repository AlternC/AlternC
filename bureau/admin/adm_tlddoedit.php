<?php
/*
 $Id: adm_tlddoedit.php,v 1.1.1.1 2003/03/26 17:41:29 root Exp $
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
 Purpose of file: Manage allowed TLD on the server
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
$fields = array (
        "tld"                 => array ("post", "string", ""),
        "mode"                => array ("post", "integer", ""),
);      
getFields($fields);


if (!$admin->enabled) {
        __("This page is restricted to authorized staff");
        exit();
}

if (!$admin->edittld($tld,$mode)) {
        $error=$err->errstr();
        include("adm_tldedit.php");
        exit();
} else {
        $error=_("The TLD has been successfully edited");
        include("adm_tld.php");
        exit();
}
?>
