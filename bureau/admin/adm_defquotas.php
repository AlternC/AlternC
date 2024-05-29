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
 * Administrator page to manage default quotas for users
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
  "synchronise"        => array ("get", "integer", "0"),
);
getFields($fields);

include_once ("head.php");

?>
<h3><?php __("Change the default quotas"); ?></h3>
<hr id="topbar"/>
<br />
<?php
if ($synchronise==true) {
  $quota->synchronise_user_profile();
  echo "<p class=\"alert alert-info\">";__("User's quotas synchronised");echo "</p>";
}

$quota->create_missing_quota_profile();

echo $msg->msg_html_all();
?>
<form method="post" action="adm_dodefquotas.php">
  <?php csrf_get(); ?>
<p>
<input type="hidden" name="action" value="add" />
<input type="text" name="type" class="int" />
<input type="submit" class="inb" value="<?php __("Add account type"); ?>" />
</p>
</form>

<?php
?>
<form method="post" action="adm_dodefquotas.php">
  <?php csrf_get(); ?>
<table border="0" cellpadding="4" cellspacing="0">
<tr class="lst">
<td>
<input type="hidden" name="action" value="delete" />
<select name="type" id="type" class="inl">
<?php
foreach($quota->listtype() as $type) {
  if ($type=="default") continue;
  echo "<option value=\"$type\">$type</option>\n";
}
?></select>
</td><td><input type="submit" class="inb" value="<?php __("Delete account type"); ?>" /></td>
</tr>
</table>
</form>
<p>
<?php __("Here is the list of the quotas on the server for the new accounts. If you want to change them, enter new values"); ?>
</p>
<span class="inb"><a href="adm_defquotas.php?synchronise=1"><?php __("Synchronise user's quota (only to upper value)"); ?></a></span>  

<form method="post" action="adm_dodefquotas.php">
  <?php csrf_get(); ?>
<div>
<input type="hidden" name="action" value="modify" />
<?php
$qarray=$quota->qlist();
$qlist=$quota->getdefaults();
reset($qlist);
foreach($qlist as $type => $q) {
?>
<div class="info-toggle">
<h4 class="toggle-next"><?php echo __("Accounts of type", "alternc", true). " \"$type\"" ?>▼</h4>
<div class="info-hide" id="div-quot-<?php echo md5($type);?>">
<table border="0" cellpadding="4" cellspacing="0" class='tlist'>
<tr><th><?php __("Quotas") ?></th><th><?php __("Default Value"); ?></th></tr>
<?php
foreach($q as $name => $value) {
	if (!isset($qarray[$name])) continue;
	$key = $type . ":" . $name;
?>

<tr class="lst">
<td><label for="<?php echo $key; ?>"><?php echo $qarray[$name] ; ?></label></td>
<td><input type="text" class="int" size="16" maxlength="16" name="<?php ehe($key); ?>" id="<?php ehe($key); ?>" value="<?php ehe($value); ?>" /></td></tr>

<?php
  } //foreach 
?>
</table>
<br/>
</div>
<script type="text/javascript">
  $("#div-quot-<?php echo md5($type);?>").toggle();
</script>
</div>
<?php
}
?>
<br/>
<input type="submit" class="inb ok" value="<?php __("Edit the default quotas"); ?>" />
</div>
</form>
<script type="text/javascript">
$(function(){
  $(".toggle-next").on("click",function(){
    var next = $(this).next();
    next.toggle();
  })
}); 
</script>
<?php include_once("foot.php"); ?>
