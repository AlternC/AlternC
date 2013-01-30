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
<a href="javascript:menu_toggle('menu-adm');">
  <div class="menu-title">
      <img src="images/admin.png" alt="Administration" />&nbsp;<span class="adminmenu"><?php __("Administration"); ?></span>
      <img src="images/row-down.png" alt="" style="float:right;"/>
  </div>
</a>
<div class="menu-content" id ="menu-adm">
<ul>
<li><a href="adm_list.php"><span class="adminmenu"><?php __("Manage the Alternc accounts"); ?></span></a></li>
<li><a href="quotas_users.php?mode=4"><span class="adminmenu"><?php __("User Quotas"); ?></span></a></li>
<?php if ($cuid == 2000) {  ?>
  <li><a href="adm_panel.php"><span class="adminmenu"><?php __("Admin Control Panel"); ?></span></a></li>
  <li><a href="/alternc-sql/"><span class="adminmenu"><?php __("General PhpMyAdmin"); ?></span></a></li>
  <li><a href="alternc_debugme.php?enable=<?php echo $debug_alternc->status?"0":"1"; ?>"><span class="adminmenu"><?php if ($debug_alternc->status) __("Switch debug Off"); else __("Switch debug On");  ?></span></a></li>
<?php if ( empty($L_INOTIFY_UPDATE_DOMAIN) || file_exists("$L_INOTIFY_UPDATE_DOMAIN") ) { ?>
  <li><a href='javascript:alert("<?php __("Reload already in progress"); ?>");'><span class="adminmenu"><?php __("Reload in progress..."); ?></span></a></li>
<?php } else { // file L_INOTIFY_UPDATE_DOMAIN don't exist ?>
  <li><a href="/adm_update_domains.php" onClick='return confirm("<?php echo addslashes(_("Server configuration is regenerate every 5 minutes (if there is changes). Do you want to force a regeneration right now?"));?>");'><span class="adminmenu"><?php __("Force a reload"); ?></span></a></li>
<?php } // file exist L_INOTIFY_UPDATE_DOMAIN ?>
<?php } ?>
</ul>
</div>
</div>
<?php } ?>
