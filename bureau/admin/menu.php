<?php
/*
 $Id: menu.php,v 1.9 2005/01/18 22:16:10 anarcat Exp $
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
<base target="right" />
</head>
<body id="menu">
<div id="alternc-logo"><a href="http://alternc.org"><!-- img src="alternc.png" width="120" height="82" border="0" alt="AlternC" / -->AlternC</a> 
<span><?php echo "$L_VERSION"; ?></span>
</div>
<h3>Menu <?php echo $mem->user["login"]; ?></h3>
<dl id="menu-list">
<?php
$MENUPATH="/var/alternc/bureau/admin/";
$tt=fopen("menulist.txt","rb");
while (!feof ($tt)) {
	$c=trim(fgets($tt,4096));
	if ($c && file_exists($MENUPATH.$c)) {
		include($MENUPATH.$c);
	}
}
fclose($tt);
?>
<dd><a href="main.php"><?php __("Messages") ?></a></dd>
</dl>

</body>
</html>
