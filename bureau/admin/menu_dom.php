<?php
/*
 $Id: menu_dom.php,v 1.2 2003/06/10 06:42:25 root Exp $
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

		/* ############# DOMAINES ############# */
$q = $quota->getquota("dom");

if ($q["t"] > 0)
{

?>
<div class="menu-box">
<div class="menu-title" id="test">
  <a href="javascript:menu_toggle('menu-dom');">
    <img src="images/dom.png" alt="<?php __("Domains"); ?>" />&nbsp;<?php __("Domains"); ?> (<?php echo $q["u"]; ?>/<?php echo $q["t"]; ?>)
    <img src="images/row-down.png" alt="" style="float:right;"/></a>
</div>
<div class="menu-content" id="menu-dom">
<ul>
<?php if ($quota->cancreate("dom")) { ?>
     <li><a href="dom_add.php"><img src="images/new.png" alt="<?php __("Add a domain"); ?>" />&nbsp;<?php __("Add a domain"); ?></a></li>
<?php }

/* Enumeration des domaines : */
$domlist = $dom->enum_domains();
reset($domlist);
while (list($key, $val) = each($domlist))
{
?>
	<li><a href="dom_edit.php?domain=<?php echo urlencode($val) ?>"><?php echo $val ?></a></li>
<?php } ?>
</ul>
</div>
</div>
<?php } ?>
