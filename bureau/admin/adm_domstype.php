<?php
/*
 $Id: adm_tld.php,v 1.4 2004/11/29 17:27:04 anonymous Exp $
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
 Purpose of file: Manage allowed TLD on the server
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
	__("This page is restricted to authorized staff");
	exit();
}

include_once("head.php");

?>
<h3><?php __("Manage domains type"); ?></h3>
<hr id="topbar" />
<br />
<?php
	if ($error) {
	  echo "<p class=\"error\">$error</p>";
	}

?>
<p>
<?php __("Here is the list of the domains type."); ?>
</p>
<p><span class="ina"><a href="adm_domstypeadd.php"><?php __("Add a new domains type"); ?></a></span></p>
<table class="tlist">
<tr>
    <th><?php __("Name");?></th>
    <th><?php __("Description");?></th>
    <th><?php __("Target");?></th>
    <th><?php __("Entry");?></th>
    <th><?php __("Compatibility");?></th>
    <th><?php __("Edit");?></th>
</tr>
<?php 
$pair=0;
foreach($dom->domains_type_lst() as $d) { 
++$pair;
?>
<tr class="lst<?php echo $pair%2+1 ?>">
    <td><?php echo $d['name'];?></td>
    <td><?php echo $d['description'];?></td>
    <td><?php echo $d['target'];?></td>
    <td><?php echo $d['entry'];?></td>
    <td><?php echo $d['compatibility'];?></td>
    <td><div class="ina"><a href="adm_domstypeedit.php?name=<?php echo urlencode($d['name']); ?>"><img style="padding-bottom: 5px" src="images/edit.png" alt="<?php __("Edit"); ?>" /><?php __("Edit"); ?></a></div></td>

    
</tr>
<?php } // end foreach ?>

<?php include_once("foot.php"); ?>
