<?php
/*
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2000-2012 by the AlternC Development Team.
 https://alternc.org/
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
*/

/* ############################# */
/* ######### SUPER-ADMIN ########## */
/* ############################# */

if ($mem->checkRight()) { ?>
<div class="menu-box">
<div class="menu-title">
  <a href="javascript:menu_toggle('menu-adm');">
    <img src="images/admin.png" alt="Administration" />&nbsp;<span style="color: red;"><?php __("Administration"); ?></span>
    <img src="images/row-down.png" alt="" style="float:right;"/></a>
</div>
<div class="menu-content" id ="menu-adm">
<ul>
<li><a href="adm_list.php"><span style="color: red;"><?php __("Manage the Alternc accounts"); ?></span></a></li>
<li><a href="quotas_users.php?mode=4"><span style="color: red;"><?php __("User Quotas"); ?></span></a></li>
<?php if ($cuid == 2000) { 
  $llzstr="Switch debug ".($debug_alternc->status?"Off":"On"); ?>
  <li><a href="adm_panel.php"><span style="color: red;"><?php __("Admin Control Panel"); ?></span></a></li>
  <li><a href="/alternc-sql/?server=2"><span style="color: red;"><?php __("General PhpMyAdmin"); ?></span></a></li>
  <li><a href="alternc_debugme.php?enable=<?php echo $debug_alternc->status?"0":"1"; ?>"><span style="color: red;"><?php __("$llzstr"); ?></span></a></li>
<?php } ?>
</ul>
</div>
</div>
<?php } ?>
