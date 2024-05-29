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
 * Manage allowed TLDs on the server
 * soon deprecated
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
	"tld"    		=> array ("post", "string", ""),
	"mode"    		=> array ("post", "integer", ""),
);
getFields($fields);


include_once ("head.php");

?>
<h3><?php __("Manage allowed domains (TLD)"); ?></h3>
<hr id="topbar"/>
<br />
<?php
echo $msg->msg_html_all();
?>
<h3><?php __("Add a new TLD"); ?></h3>
<p>
<?php __("Enter the new TLD (without the first dot) and choose what check should be done."); ?><br />
<small><?php __("Warning : only some final tld are known in the whois function of AlternC, please check m_dom.php accordingly."); ?></small>
</p>

<form method="post" action="adm_tlddoadd.php" name="main" id="main">
  <?php csrf_get(); ?>
<table class="tedit">
<tr><th><label for="tld"><?php __("TLD"); ?></label></th><td><input type="text" id="tld" name="tld" class="int" value="<?php ehe( (isset($tld)?$tld:'') ); ?>" size="20" maxlength="64" /></td></tr>
<tr><th><label for="mode"><?php __("Allowed Mode"); ?></label></th><td><select name="mode" id="mode" class="inl">
	<?php $admin->selecttldmode($mode); ?>
</select></td></tr>
<tr class="trbtn"><td colspan="2">
 <input type="submit" class="inb" value="<?php __("Add a new TLD"); ?>" />
  <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='adm_tld.php'"/>
</td></tr>
</table>
</form>
<script type="text/javascript">
document.forms['main'].tld.focus();
</script>


<?php include_once("foot.php"); ?>
