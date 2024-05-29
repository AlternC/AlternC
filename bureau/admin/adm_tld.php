<?php
/*
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

/**
 * Manages Allowed TLD's to be installed as domain names on the server
 * soon deprecated due to all those new TLDs 
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/  
 */

require_once("../class/config.php");

if (!$admin->enabled) {
	$msg->raise("ERROR", "admin", __("This page is restricted to authorized staff", "alternc", true));
	echo $msg->msg_html_all();
	exit();
}

$fields = array (
	"sel"    		=> array ("post", "array", ""),
);
getFields($fields);


if (is_array($sel)) {
	for($i=0;$i<count($sel);$i++) {
		if (!$admin->deltld($sel[$i])) {
			$msg->raise("ERROR", "admin", __("Some TLD cannot be deleted...", "alternc", true)." : ".$sel[$i]);
		}
	}
	if (!$msg->has_msgs("ERROR"))
		$msg->raise("INFO", "admin", __("The requested TLD has been deleted", "alternc", true));
}

include_once("head.php");

?>
<h3><?php __("Manage allowed domains (TLD)"); ?></h3>
<hr id="topbar" />
<br />
<?php
echo $msg->msg_html_all();

$c=$admin->listtld();

?>
<p>
<?php __("Here is the list of the TLD allowed on this server. Each TLD can be allowed or denied after some checks (whois, ns, domain exists...)"); ?>
</p>
<p><span class="ina"><a href="adm_tldadd.php"><?php __("Add a new TLD"); ?></a></span></p>
<form method="post" action="adm_tld.php" name="main" id="main">
  <?php csrf_get(); ?>
<table class="tlist">
<tr><th colspan="2"> </th><th><?php __("TLD"); ?></th><th><?php __("Allowed Mode"); ?></th></tr>
<?php
for($i=0;$i<count($c);$i++) {
?>

<tr class="lst">
<td><input id="sel<?php echo $i; ?>" type="checkbox" name="sel[]" class="inc" value="<?php ehe($c[$i]["tld"]); ?>" /></td>
   <td><div class="ina edit"><a href="adm_tldedit.php?tld=<?php eue($c[$i]["tld"]); ?>"><?php __("Edit"); ?></a></div></td>
    <td><label for="sel<?php echo $i; ?>"><?php ehe($c[$i]["tld"]); ?></label></td>
<td><?php __($admin->tldmode[$c[$i]["mode"]]); ?></td></tr>

<?php
}
?>
<tr class="trbtn"><td colspan="3"><input type="submit" class="inb" value="<?php __("Delete the checked TLD"); ?>" /></td></tr>
</table>
</form>

<p><span class="ina"><a href="adm_tldadd.php"><?php __("Add a new TLD"); ?></a></span></p>
<?php include_once("foot.php"); ?>
