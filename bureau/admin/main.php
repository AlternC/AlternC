<?php
/*
 $Id: main.php,v 1.3 2004/05/19 14:23:06 benjamin Exp $
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
require_once("../class/config.php");

include("head.php");
?>
</head>
<body>
<?php
// Show last login information : 
__("Last Login: ");

echo format_date('the %3$d-%2$d-%1$d at %4$d:%5$d',$mem->user["lastlogin"]);
printf("&nbsp;"._('from: <code> %1$s </code>')."<br />",$mem->user["lastip"]);

if ($mem->user["lastfail"]) {
	printf(_("%1\$d login failed since last login")."<br />",$mem->user["lastfail"]);
}

$mem->resetlast();

/*
<h3>
Le bureau AlternC de ce serveur est en travaux actuellement.<br />
Il se peut que certaines parties du bureau soient inaccessibles ponctuellement.<br />
Si une partie du bureau ne fonctionne pas pendant longtemps, n'hésitez pas, contactez le 
mainteneur par mail : <a href="mailto:root@heberge.info">root@heberge.info</a>
</h3>
*/
?>
</body>
</html>
