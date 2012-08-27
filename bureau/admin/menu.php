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

?>
<h3>Menu <?php echo $mem->user["login"]; ?></h3>

<div class="menu-box">
    <div class="menu-title"><img src="images/home.png" alt="<?php  __("Home / Information"); ?>" />&nbsp;<a href="main.php"><?php  __("Home / Information"); ?></a></div>
</div>
<?php
// Force rebuilding quota, in case of add or edit of the quota and cache not up-to-date
$quota->getquota("",true); // rebuild quota

$MENUPATH=ALTERNC_PANEL."/admin/";
$file=file("/etc/alternc/menulist.txt", FILE_SKIP_EMPTY_LINES);
foreach($file as $v) {
  $v=trim($v);
  if ( file_exists($MENUPATH.$v)) include($MENUPATH.$v);
}
?>
<p class="center"><a href="about.php" target="_blank"><img src="logo2.png" border="0" alt="AlternC" title="<?php __("About"); ?>"/></a>
<br />
<?php 
echo "$L_VERSION";
?>
</p>
