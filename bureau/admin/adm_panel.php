<?php
/*
 $Id: adm_panel.php,v 1.9 2005/08/01 18:25:52 anarcat Exp $
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
 Purpose of file: Panneau de control de l'administrateur
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
	__("This page is restricted to authorized staff");
	exit();
}

include("head.php");
?>
</head>
<body>
<h3><?php __("Admin Control Panel"); ?></h3>
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p></body></html>";
	}
?>
<blockquote>
<table border="0" cellpadding="4" cellspacing="0">
<tr class="lst1"><td><a href="adm_tld.php"><?php __("Manage allowed domains (TLD)"); ?></a></td></tr>
<tr class="lst2"><td><a href="adm_defquotas.php"><?php __("Change the default quotas"); ?></a></td></tr>
<tr class="lst1"><td><a href="adm_doms.php"><?php __("Manage installed domains"); ?></a></td></tr>
<tr class="lst2"><td><a href="adm_slaveip.php"><?php __("Manage allowed ip for slave zone transfers"); ?></a></td></tr>
<tr class="lst1"><td><a href="adm_slaveaccount.php"><?php __("Manage allowed accounts for slave zone transfers"); ?></a></td></tr>
<tr class="lst1"><td><a href="adm_mxaccount.php"><?php __("Manage allowed accounts for secondary mx"); ?></a></td></tr>
<tr class="lst2"><td><a href="adm_variables.php"><?php __("Configure AlternC variables"); ?></a></td></tr>
<tr class="lst1"><td><a href="quota_show_all.php"><?php __("Show all quotas"); ?></a></td></tr>
<?php

// here we include any "adminmenu_*" file content
$d=opendir(".");
if ($d) { 
  $lst=1;
  while ($c=readdir($d)) {
    if (substr($c,0,10)=="adminmenu_") {
      echo "<tr class=\"lst$lst\">";
      include($c);
      echo "</tr>\n";
      $lst=3-$lst;
    }
  } 
}

closedir($d);
?>

</table>
</blockquote>
</body>
</html>


