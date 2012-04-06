<?php
/*
 $Id: menu_ftp.php,v 1.2 2003/06/10 06:42:25 root Exp $
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

/* ############# FTP ############# */

$q = $quota->getquota("ftp");

if ($q["t"] > 0) { 

?>
<div class="menu-box">
<div class="menu-title">
<a href="javascript:menu_toggle('menu-ftp');">
<img src="images/ftp.png" alt="<?php __("FTP accounts"); ?>" />&nbsp;<?php __("FTP accounts"); ?> <?php if (!$quota->cancreate("ftp")) { echo '<span class="full">'; } ?>(<?= $q["u"]; ?>/<?= $q["t"]; ?>)<?php if (!$quota->cancreate("ftp")) { echo '</span>'; } ?>
<img src="images/row-down.png" alt="" style="float:right;"/></a>
</div>
<div class="menu-content" id="menu-ftp">
<ul>
<?php if ($quota->cancreate("ftp")) { ?>
     <li><a href="ftp_add.php"><img src="images/new.png" alt="<?php __("Create a new ftp account"); ?>" />&nbsp;<?php __("Create a new ftp account"); ?></a></li>
<?php } ?>
<li><a href="ftp_list.php"><?php __("FTP accounts list"); ?></a></li>
</ul>
</div>
</div>
<?php } ?>
