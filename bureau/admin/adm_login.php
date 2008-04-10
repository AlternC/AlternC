<?php
/*
 $Id: adm_login.php,v 1.4 2005/04/01 17:13:10 benjamin Exp $
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
 Purpose of file: Connect a super-user to another account
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
	__("This page is restricted to authorized staff");
	exit();
}

$id = $_GET['id'];

if (!$admin->checkcreator($id)) {
  __("This page is restricted to authorized staff");
  exit();
}

if (!$r=$admin->get($id)) {
	$error=$err->errstr();
} else {

if (!$mem->setid($id)) {
        $error=$err->errstr();
	include("index.php");
	exit();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html>
<head>
<title><?php __("AlternC Desktop"); ?></title>
<link rel="stylesheet" href="styles/base.css" type="text/css" />
<link rel="stylesheet" href="styles/custom.css" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
</head>
<frameset cols="225px,*">
	<frame src="menu.php" name="left" />
	<frame src="main.php" name="right" />
<noframes>
<body>
<p>
	Votre navigateur doit supporter les cadres.<br />
	Your browser must support frames
</p>
</body>
</noframes>
</frameset>
</html>
<?php
					  exit();
}
include("head.php");
?>
</head>
<body>
<h3><?php __("Member login"); ?></h3>
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p></body></html>";
		exit();
	}
?>
</body>
</html>


