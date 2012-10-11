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
 Original Author of file: Alan Garcia
 Purpose of file: Manage domain types on the server 
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
  <p><?php __("If you don't know what this page is about, don't touch anything, and read AlternC documentation about domain types"); ?></p>

<br />
<?php
if (isset($error) && $error) {
  echo "<p class=\"error\">$error</p>";
}

?>
<p>
<?php __("Here is the list of domain types."); ?>
</p>
<p><span class="ina"><a href="#" OnClick="alert('Todo. But if you want to play with that, you are advanced enough to do an insert in MySQL');" ><?php __("Create a domain type"); ?></a></span></p>
<table class="tlist">
<tr>
    <th colspan="2"> </th>
    <th><?php __("Name");?></th>
    <th><?php __("Description");?></th>
    <th><?php __("Target");?></th>
    <th><?php __("Entry");?></th>
    <th><?php __("Compatible with");?><br /><small><?php __("Enter comma-separated name of other types"); ?></small></th>
    <th><?php __("Enabled?");?></th>
    <th><?php __("Only DNS?");?></th>
    <th><?php __("Need to be DNS?");?></th>
    <th><?php __("Advanced?");?></th>
    <th><?php __("Create tmp directory ?");?></th>
    <th><?php __("create www directory ?");?></th>
    <th/>
</tr>
<?php 
$pair=0;
foreach($dom->domains_type_lst() as $d) { 
++$pair;
?>
<tr class="lst<?php echo $pair%2+1 ?>">
    <td><div class="ina"><a href="adm_domstypeedit.php?name=<?php echo urlencode($d['name']); ?>"><img style="padding-bottom: 5px" src="images/edit.png" alt="<?php __("Edit"); ?>" /><?php __("Edit"); ?></a></div></td>
    <td><div class="ina"><a href="adm_domstyperegenerate.php?name=<?php echo urlencode($d['name']);?>"><?php __("Regenerate");?></a></div></td> 
    <td><?php echo $d['name'];?></td>
    <td><?php echo $d['description'];?></td>
    <td><?php echo $d['target'];?></td>
    <td><?php echo $d['entry'];?></td>
    <td><?php echo $d['compatibility'];?></td>
    <td><?php echo __($d['enable']);?></td>
    <td><?php echo $d['only_dns']?__("Yes"):__("No");?></td>
    <td><?php echo $d['need_dns']?__("Yes"):__("No");?></td>
    <td><?php echo $d['advanced']?__("Yes"):__("No");?></td>
    <td><?php echo $d['create_tmpdir']?__("Yes"):__("No");?></td>
    <td><?php echo $d['create_targetdir']?__("Yes"):__("No");?></td>
</tr>
<?php } // end foreach 
?>
</table>

<?php include_once("foot.php"); ?>
