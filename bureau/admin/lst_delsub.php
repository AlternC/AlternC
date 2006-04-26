<?php
/*
 $Id: lst_delsub.php,v 1.1.1.1 2003/03/26 17:41:29 root Exp $
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
 Original Author of file: Louis Sylvain
 Purpose of file: Delete subscribers in the mailing list
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if(!$r=$sympa->get_ml($id)) {
	$error=$err->errstr();
} 

// On parcours les POST_VARS et on repere les del_.
reset($_POST); 
while (list($key,$val)=each($_POST)) {
	if (substr($key,0,4)=="del_") {
		// Effacement des mails de la liste
		$d=$sympa->del_user($id,$val);
	}
}
$error=_("The mail(s) has been successfully unsubscribed from the list");

include("head.php");
?>
</head>
<body>
<div align="center"><h3><?php printf(_("Mailing list %s"),$r["list"]); ?></h3></div>
<?php
	if ($error) {
		echo "<font color=red>$error</font></body></html>";
	}
?>
<br><br>
<a href="lst_affsub.php?id=<?php echo $id; ?>&offset=<?php echo $offset; ?>"><?php __("Back"); ?></a>
</body>
</html>