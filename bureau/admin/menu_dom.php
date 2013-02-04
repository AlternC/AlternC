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

		/* ############# DOMAINES ############# */
$q = $quota->getquota("dom");

if ($q["t"] > 0 || $q['u'] > 0)
{

?>
<div class="menu-box">
<a href="javascript:menu_toggle('menu-dom');">
  <div class="menu-title" id="test">
    <img src="images/dom.png" alt="<?php __("Domains"); ?>" />&nbsp;<?php __("Domains"); ?> (<?php echo $q["u"]; ?>/<?php echo $q["t"]; ?>)
    <img src="images/menu_moins.png" alt="" style="float:right;" id="menu-dom-img"/>
  </div>
</a>
<div class="menu-content" id="menu-dom">
<ul>
<?php if ($quota->cancreate("dom")) { ?>
  <li><a href="dom_add.php"><img src="images/new.png" alt="<?php __("Add a domain"); ?>" />&nbsp;<?php __("Add a domain"); ?></a></li>
<?php }

/* Enumeration des domaines : */
$domlist = $dom->enum_domains();
reset($domlist);
while (list($key, $val) = each($domlist)) { ?>
  <li><a href="dom_edit.php?domain=<?php echo urlencode($val) ?>" title="<?php echo htmlentities($val); ?>"><?php echo $val; ?></a></li>
<?php } ?>
</ul>
</div>
</div>
<?php } ?>
