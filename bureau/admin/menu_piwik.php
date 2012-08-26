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

/* ############# PIWIK ############# */
$q = $quota->getquota("piwik");
if (!empty($piwik->piwik_server_uri) && ($q["t"] > 0 || $r["u"] > 0)) {
?>
<div class="menu-box">
<div class="menu-title"><img src="images/stat.png" alt="<?php __("Piwik statistics"); ?>" />&nbsp;<a href="piwik_userlist.php"><?php __("Piwik statistics"); ?> (<?php echo $q["u"]; ?>/<?php echo $q["t"]; ?>)</a></div>
</div>
<?php } ?>
