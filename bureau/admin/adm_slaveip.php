<?php
/*
 $Id: adm_slaveip.php,v 1.2 2004/06/02 13:03:13 anonymous Exp $
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
 Purpose of file: Manage list of allowed ip for zone transfers
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
	__("This page is restricted to authorized staff");
	exit();
}

$fields = array (
	"delip"   => array ("request", "string", ""),
	"newip"    => array ("request", "string", ""),
	"newclass" => array ("request", "string", "32"),
);
getFields($fields);

if ($delip) {
	// Delete an ip address/class
	if ($dom->del_slave_ip($delip)) {
		$error=_("The requested ip address has been deleted. It will be denied in one hour.");
	}
}
if ($newip) {
	// Add an ip address/class
	if ($dom->add_slave_ip($newip,$newclass)) {
		$error=_("The requested ip address has been added to the list. It will be allowed in one hour.");
		unset($newip); unset($newclass);
	}
}

include_once("head.php");

?>
<h3><?php __("Manage allowed ip for slave zone transfers"); ?></h3>
<hr id="topbar" />
<br />
<?php
	if (isset($error) && $error) {
	  echo "<p class=\"error\">$error</p>";
	}

$c=$dom->enum_slave_ip();

if (is_array($c)) {

?>
<p>
<?php __("Here is the list of the allowed ip or ip class for slave dns zone transfer requests (AXFR). You must add the ip address of all the slave DNS you have so that those slaves will be allowed to transfer the zone files. There is also some defaults ip from DNS checks made by some third-party technical offices such as afnic (for .fr domains)"); ?>
</p>

<table border="0" cellpadding="4" cellspacing="0" class='tlist'>
<tr><th><?php __("Action"); ?></th><th><?php __("IP Address"); ?></th></tr>
<?php
$col=1;
for($i=0;$i<count($c);$i++) {
 $col=3-$col;
?>

<tr class="lst<?php echo $col; ?>">
   <td class="center"><div class="ina"><a href="adm_slaveip.php?delip=<?php echo urlencode($c[$i]['ip']); ?>"><img src="images/delete.png" alt="<?php __("Delete"); ?>" /><?php __("Delete"); ?></a></div></td>
<td><?php echo $c[$i]["ip"]."/".$c[$i]["class"]; ?></td>
</tr>
<?php
}
?>
</table>
    <?php } ?>
<p><?php __("If you want to allow an ip address or class to connect to your dns server, enter it here. Choose 32 as a prefix for single ip address."); ?></p>
<form method="post" action="adm_slaveip.php" name="main" id="main">
<table class="tedit">
<tr><th><label for="newip"><?php __("IP Address"); ?></label></th><th><label for="newclass"><?php __("Prefix"); ?></label></th></tr>
<tr>
	<td style="text-align: right"><input type="text" class="int" value="<?php ehe( (isset($newip)?$newip:'') ); ?>" id="newip" name="newip" maxlength="15" size="20" style="text-align:right" /> / </td>
	<td><input type="text" class="int" value="<?php ehe( (isset($newclass)?$newclass:'') ); ?>" id="newclass" name="newclass" maxlength="2" size="3" /></td>
</tr>
<tr><td colspan="2">
	<input type="submit" value="<?php __("Add this ip to the slave list"); ?>" class="inb" />
</td></tr>
</table>
</form>
<script type="text/javascript">
document.forms['main'].newip.focus();
</script>

<?php include_once("foot.php"); ?>
