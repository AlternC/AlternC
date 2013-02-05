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

include_once("head.php");

?>
<h3><?php __("Admin Control Panel"); ?></h3>
<hr id="topbar"/>
<br />
<?php
if (isset($error) && $error) {
	echo "<p class=\"error\">$error</p>";
	include_once("foot.php");
	exit;
}
?>
<ul id="adm_panel">
 <li class="lst1"><a href="adm_tld.php"><?php __("Manage allowed domains (TLD)"); ?></a></li>
 <li class="lst2"><a href="adm_passpolicy.php"><?php __("Password Policies"); ?></a></li>
 <li class="lst1"><a href="adm_doms.php"><?php __("Manage installed domains"); ?></a></li>
 <li class="lst2"><a href="adm_defquotas.php"><?php __("Change the default quotas"); ?></a></li>
 <li class="lst1"><a href="adm_authip_whitelist.php"><?php __("Manage IP whitelist"); ?></a></li>
 <li class="lst2"><a href="adm_email.php"><?php __("Send an email to all members"); ?></a></li>
</ul>


 <h3><?php __("Advanced features"); ?></h3>

<ul id="adm_panel_root">
 <li class="lst2"><a href="adm_slaveip.php"><?php __("Manage allowed ip for slave zone transfers"); ?></a></li>
 <li class="lst1"><a href="adm_slaveaccount.php"><?php __("Manage allowed accounts for slave zone transfers"); ?></a></li>
 <li class="lst2"><a href="adm_mxaccount.php"><?php __("Manage allowed accounts for secondary mx"); ?></a></li>
 <li class="lst1"><a href="adm_variables.php"><?php __("Configure AlternC variables"); ?></a></li>
 <li class="lst2"><a href="adm_doms_def_type.php"><?php __("Manage defaults domains type"); ?></a></li>
 <li class="lst1"><a href="adm_domstype.php"><?php __("Manage domains type"); ?></a></li>
 <li class="lst2"><a href="adm_dnsweberror.php"><?php __("DNS and website having errors"); ?></a></li>
 <li class="lst1"><a href="adm_menulist.php"><?php __("Manage menu"); ?></a></li>
<!--  <li class="lst2"><a href="stats_members.php"><?php __("Account creation statistics"); ?></a></li> -->

<?php

// here we include any "adminmenu_*" file content
$d=opendir(".");
if ($d) {
  $lst=2;
  while ($c=readdir($d)) {
    if (substr($c,0,10)=="adminmenu_") {
      echo "<li class=\"lst$lst\">";
      include($c);
      echo "</li>\n";
      $lst=3-$lst;
    }
  }
}

closedir($d);
?>
</ul>

<?php include_once("foot.php"); ?>
