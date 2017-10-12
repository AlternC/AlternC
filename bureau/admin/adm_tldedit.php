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
 * Manage allow TLDs domains to be installed here
 * soon deprecated
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/  
 */

require_once("../class/config.php");

if (!$admin->enabled) {
	$msg->raise("ERROR", "admin", _("This page is restricted to authorized staff"));
	echo $msg->msg_html_all();
        exit();
}

$fields = array (
	"tld"    => array ("request", "string", ""),
);
getFields($fields);

$mode=$admin->gettld($tld);
if ($mode===false) {
	include("adm_tld.php");
	exit();
}

include_once("head.php");

?>
<h3><?php __("Manage allowed domains (TLD)"); ?></h3>
<hr id="topbar"/>
<br />
<?php
echo $msg->msg_html_all();
?>
<h3><?php __("Edit a TLD"); ?></h3>

<form method="post" action="adm_tlddoedit.php">
  <?php csrf_get(); ?>
<table id="main" class="tedit">
<tr><th><label for="tld"><?php __("TLD"); ?></label></th><td><code><?php echo $tld; ?></code><input type="hidden" name="tld" id="tld" value="<?php ehe($tld); ?>" /></td></tr>
<tr><th><label for="mode"><?php __("Allowed Mode"); ?></label></th><td><select name="mode" class="inl" id="mode">
        <?php $admin->selecttldmode($mode); ?>
</select></td></tr>
<tr class="trbtn"><td colspan="2">
  <input type="submit" class="inb" value="<?php __("Edit this TLD"); ?>" />
  <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='adm_tld.php'"/>

</td></tr>
</table>
</form>
<?php include_once("foot.php"); ?>
