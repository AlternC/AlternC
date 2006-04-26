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
$q=$quota->getquota("dom");
if ($q["t"]>0) { 
?>
<tr><td nowrap="nowrap">
<?php __("Domains"); ?><br />
	<?php if ($quota->cancreate("dom")) { ?>
	- <a href="dom_add.php"><?php __("Add a domain"); ?></a><br />
	<?php }
	/* Enumeration des domaines : */
	$domain=$dom->enum_domains();
	reset($domain);
	while (list($key,$val)=each($domain)) {
	?>
	- <a href="dom_edit.php?domain=<?php echo urlencode($val) ?>"><?php echo $val ?></a><br />
<?php    }    ?>
</td></tr>
<?php   }   ?>
