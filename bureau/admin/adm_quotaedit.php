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
 * Show the form used to update users' quotas
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");
include_once("head.php");

if (!$admin->enabled) {
	$msg->raise("ERROR", "admin", _("This page is restricted to authorized staff"));
	echo $msg->msg_html_all();
	include_once("foot.php");
	exit();
}

$fields = array (
	"uid"    => array ("request", "integer", 0),
);
getFields($fields);

$us=$admin->get($uid);

$mem->su($uid);
$r=$quota->getquota();
$mem->unsu();

?>
<h3><?php __("Editing the quotas of a member"); ?></h3>
<hr id="topbar"/>
<br />
<?php
echo $msg->msg_html_all();
?>
<form method="post" action="adm_quotadoedit.php">
  <?php csrf_get(); ?>
<table class="tedit">
<tr><th><input type="hidden" name="uid" value="<?php ehe($uid); ?>" />
<?php __("Username"); ?></th><td colspan="3"><code><big><?php ehe($us["login"]); ?></big></code>&nbsp;</td></tr>
<tr><th><?php __("Quota"); ?></th><th style="text-align: right"><?php __("Total"); ?></th><th><?php __("Used"); ?></th></tr>
<?php
$ql=$quota->qlist();
foreach($ql as $key=>$val) {
	if (!isset($r[$key])) continue;
	echo "<tr>";
	echo "<td>";
	if ($r[$key]["t"]==$r[$key]["u"] && $r[$key]["u"]) echo "<span style=\"color: red;\">";
	echo "<label for=\"q_$key\">" . $val . "</label>";
	if ($r[$key]["t"]==$r[$key]["u"] && $r[$key]["u"]) echo "</span>";
	echo "</td>";
	echo "<td align=\"center\"><input type=\"text\" class=\"int\" style=\"text-align: right\" size=\"10\" maxlength=\"20\" value=\"".$r[$key]["t"]."\" name=\"q_".$key."\" id=\"q_".$key."\" /></td>";
	echo "<td align=\"right\"><code><label for=\"q_$key\">".$r[$key]["u"]."</label></code>&nbsp;</td>";
	echo "</tr>";
}
?>
<tr class="trbtn"><td colspan="3">
  <input class="inb ok" type="submit" name="submit" value="<?php __("Edit the quotas"); ?>" />
  <input type="button" class="inb cancel" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='adm_list.php'" />

</td></tr>
</table>
</form>
<?php include_once("foot.php"); ?>
