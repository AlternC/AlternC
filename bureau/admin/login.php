<?php
/*
 $Id: login.php,v 1.2 2003/06/10 06:42:25 root Exp $
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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/

// For people who want to authenticate with HTTP AUTH
if (isset($_GET['http_auth'])) $http_auth=strval($_GET['http_auth']);
if ($http_auth) {
    if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
        header('WWW-Authenticate: Basic realm="Test Authentication System"');
        header('HTTP/1.0 401 Unauthorized');
    } else {
        // Gruiiik
        $_REQUEST["username"]=$_SERVER['PHP_AUTH_USER'];
        $_REQUEST["password"]=$_SERVER['PHP_AUTH_PW'];
    }
}

require_once("../class/config.php");

if (!$mem->checkid())
{
	$error = $err->errstr();
	include("index.php");
	exit();
}

include("main.php");
exit;

?>
