<?php
/*
 $Id: dom_editdns.php,v 1.3 2003/06/10 11:18:27 root Exp $
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
 Purpose of file: Edit the dns parameters of a domain
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$dom->lock();
if ($dns!="1") {
	// On fixe mx :
	if ($email=="1") {
		$mx=$L_MX;
	} else {
		$mx="";
	}
}

if (!$dom->edit_domain($domain,$dns,$mx)) {
	$error=$err->errstr();
	include("dom_edit.php");
	$dom->unlock();
	exit();
}
$dom->unlock();

include("head.php");
?>
</head>
<body>
<h3><?php printf(_("Editing domain %s"),$domain); ?></h3>
<p>
<?php printf(_("The domain %s has been changed. The modifications will take effect in 5 minutes."),$domain); ?><br />
<a href="login.php" target="_top"><?php __("Click here to continue"); ?></a>
</p>
</body>
</html>
