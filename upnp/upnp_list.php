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
 Purpose of file: Show the UPnP port forwarding list
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

if (!$admin->enabled) {
	__("This page is restricted to authorized staff");
	exit();
}

$r=$upnp->get_forward_list();

$aaction=array("CREATE" => _("Creation in progress"),
	       "DELETE" => _("Deletion in progress"),
	       "DELETING" => _("Deletion in progress"),
	       "DISABLE" => _("Will be disabled soon"),
	       "ENABLE" => _("Will be enabled soon"),
	       "OK" => _("OK"),
	       );

?>
<h3><?php __("UPnP port forwarding list"); ?></h3>
<hr id="topbar"/>
<br />
<?php
  if (isset($error) && $error ) {
    echo "<p class=\"error\">$error</p>";
  }
?>
<p>
<?php __("Here is the list of the requested port forward for AlternC's services, and their status. You can enable or disable some of them."); ?> 

<table class="tlist">
<tr>
<th></th>
<th><?php __("Name"); ?></th>
<th><?php __("Class"); ?></th>
<th><?php __("Protocol/Port") ?></th>
<th><?php __("Mandatory") ?></th>
<th><?php __("Enabled") ?></th>
<th><?php __("Last Check"); ?></th>
<th><?php __("Last Update"); ?></th>
<th><?php __("Status"); ?></th>
</tr>
<?php
reset($r);

$col=1;
while (list($key,$val)=each($r))
	{
	$col=3-$col;
?>
	<tr class="lst<?php echo $col; ?>">

<?php
 if ($val["mandatory"]) { ?>
		   <td>&nbsp;</td>
<?php } else { ?>
 <td><?php if ($val["enabled"]) { ?><a href="upnp_change.php?action=disable&id=<?php echo $val["id"]; ?>"><?php __("Disable"); ?></a><?php } else { ?><a href="upnp_change.php?action=enable&id=<?php echo $val["id"]; ?>"><?php __("Enable"); ?></a><?php } ?></td>
<?php } ?>
		<td><?php echo $val["class"] ?></td>
		<td><?php echo $val["protocol"]."/".$val["port"] ?></td>
                <td><?php if ($val["mandatory"]) __("Yes"); else __("No"); ?></td>
                <td><?php if ($val["enabled"]) __("Yes"); else __("No"); ?></td>
		<td><?php echo $val["lastcheck"] ?></td>
		<td><?php echo $val["lastupdate"] ?></td>
	        <td><?php echo $aaction[$val["status"]]; ?><br /><?php echo $val["result"]; ?></td>
	</tr>
<?php
  } // for loop
?>
</table>
</form>
<?php include_once("foot.php"); ?>

