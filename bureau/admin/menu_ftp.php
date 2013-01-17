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

/* ############# FTP ############# */

$q = $quota->getquota("ftp");

if ($q["t"] > 0 || $q['u'] > 0) { 

?>
<div class="menu-box">
<div class="menu-title">
<a href="javascript:menu_toggle('menu-ftp');">
<img src="images/ftp.png" alt="<?php __("FTP accounts"); ?>" />&nbsp;<?php __("FTP accounts"); ?> <?php if (!$quota->cancreate("ftp")) { echo '<span class="full">'; } ?>(<?php echo $q["u"]; ?>/<?php echo $q["t"]; ?>)<?php if (!$quota->cancreate("ftp")) { echo '</span>'; } ?>
<img src="images/row-down.png" alt="" style="float:right;"/></a>
</div>
<div class="menu-content" id="menu-ftp">
<ul>
<?php if ($quota->cancreate("ftp")) { ?>
     <li><a href="ftp_edit.php?create=1"><img src="images/new.png" alt="<?php __("Create a new ftp account"); ?>" />&nbsp;<?php __("Create a new ftp account"); ?></a></li>
<?php } ?>
<li><a href="ftp_list.php"><?php __("FTP accounts list"); ?></a></li>
</ul>
</div>
</div>
<?php } ?>
