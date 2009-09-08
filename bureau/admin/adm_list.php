<?php
/*
 $Id: adm_list.php,v 1.11 2006/01/24 05:03:30 joe Exp $
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2006 Le réseau Koumbit Inc.
 http://koumbit.org/
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
 Purpose of file: Show the member list
 TODO : Add a Next / Previous system in case of big lists...
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

if (!$admin->enabled) {
	__("This page is restricted to authorized staff");
	exit();
}

$fields = array (
	"show"    => array ("request", "string", ""),
	"creator_id" => array("request", "integer", 2000),
);
getFields($fields);

if ($show && $cuid != 2000)
{
	__("This page is restricted to authorized staff");
	exit();
}

$r=$admin->get_list($show == 'all' ? 1 : 0, $creator_id);

?>
<h3><?php __("Member list"); ?></h3>
<?php
	if ($error) {
	  echo "<p class=\"error\">$error</p>";
	}
?>
<p>
<?php __("Here is the list of hosted members"); ?>
&nbsp;
<?php if($_REQUEST['show'] != 'all') {
  echo '<br /><a href="adm_list.php?show=all">' . _('List all the accounts') . '</a>';
  if ($cuid == 2000) {
    $list_creators = $admin->get_creator_list();
    $infos_creators = array();

    foreach ($list_creators as $key => $val) {
      $infos_creators[] = '<a href="adm_list.php?show_all&creator=' . $val['uid'] . '">' . $val['login'] . '</a>';
    }

    if (count($infos_creators)) {
      echo ' (' . implode(', ', $infos_creators) . ')';
    }
  }
} else {
  echo '<br /><a href="adm_list.php">' . _('List only my accounts') . '</a>';
} ?>
  <br /><a href="adm_add.php"><?php __("Create a new member"); ?></a>
  <br /><a href="<?php echo $_SERVER["SCRIPT_NAME"]; ?>"><?php __("Update this page"); ?></a>
</p>
<?php
if (!is_array($r)) {
  echo "<p class=\"error\">"._("No account defined for now")."</p>";
} else {
?>

<form method="post" action="adm_dodel.php">
<?php

// Depending on the admin's choice, let's show a short list or a long list.

if ($mem->user["admlist"]==0) { // Normal (large) mode
?>
<table cellspacing="0" cellpadding="4">
<tr>
<th colspan="5">&nbsp;</th>
<th><?php __("Username"); ?></th>
<th><?php echo _("Surname")." "._("First Name")."<br />("._("Email address").")"; ?></th>
<th><?php __("Created by") ?></th>
<th><?php __("Account type") ?></th>
<th><?php __("Last login"); ?></th>
<th><?php __("Last fail"); ?></th>
<th><?php __("Last ip"); ?></th>
<th><?php __('Expiry') ?></th>
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
		  if($admin->checkcreator($val['uid'])) {
 if ($val["su"]) { ?>
			<td>&nbsp;</td>
<?php } else { ?>
 <td align="center"><input type="checkbox" class="inc" name="d[]" value="<?php echo $val["uid"]; ?>" /></td>
<?php } ?>
		<td align="center"><a href="adm_edit.php?uid=<?php echo $val["uid"] ?>"><?php __("Edit"); ?></a></td>
		<td align="center"><a href="adm_quotaedit.php?uid=<?php echo $val["uid"] ?>"><?php __("Quotas"); ?></a></td>
		<td align="center"><a href="adm_deactivate.php?uid=<?php echo $val["uid"] ?>"><?php __("Deactivate"); ?></a></td>
		<td align="center"><?php
		if (!$val["enabled"])
			echo "<img src=\"icon/encrypted.png\" width=\"16\" height=\"16\" alt=\""._("Locked Account")."\" />";
		else {
		  if($admin->checkcreator($val['uid'])) {
		?>
			<a href="adm_login.php?id=<?php echo $val["uid"];?>" target="_parent"><?php __("Connect as"); ?></a>
		<?php } } ?>
		</td>
		    <?php
		    } else { 
		      echo "<td colspan=\"5\"></td>"; 
		    }
 ?>
		<td <?php if ($val["su"]) echo "style=\"color: red\""; ?>><?php echo $val["login"] ?></td>
		<td><a href="mailto:<?php echo $val["mail"]; ?>"><?php echo $val["nom"]." ".$val["prenom"] ?></a>&nbsp;</td>
		<td><?php echo $val["parentlogin"] ?></td>
		<td><?php echo $val["type"] ?></td>
		<td><?php echo $val["lastlogin"] ?></td>
		<td><?php echo $val["lastfail"] ?></td>
                <td><?php echo $val["lastip"] ?></td>
		<td><div class="<?php echo 'exp' . $admin->renew_get_status($val['uid']) ?>"><?php echo $admin->renew_get_expiry($val['uid']) ?></div></td>
	</tr>
<?php
	}

} // Normal Mode

if ($mem->user["admlist"]==1) { // Short mode TODO : make 3 columns instead of 2  + XHTML compliance instead of 1px img trick ;)
?>


<table cellspacing="0" cellpadding="0">
<tr>
	<th colspan="2">&nbsp;</th><th><?php __("Username"); ?></th>
	<th colspan="2">&nbsp;</th><th><?php __("Username"); ?></th>
	<th colspan="2">&nbsp;</th><th><?php __("Username"); ?></th>
</tr>
<?php
reset($r);
$col=1;

$rz=ceil(count($r)/3);

for($z=0;$z<$rz;$z++)
	{
	$col=3-$col;
$val=$r[$z];
?>
	<tr class="lst<?php echo $col; ?>">
<?php if ($val["su"]) { ?>
			<td>&nbsp;</td>
<?php } else { ?>
 <td align="center"><input type="checkbox" class="inc" name="d[]" value="<?php echo $val["uid"]; ?>" /></td>
<?php } ?>
		<td align="center">
		<a href="adm_edit.php?uid=<?php echo $val["uid"] ?>"><?php __("E"); ?></a>
		<a href="adm_quotaedit.php?uid=<?php echo $val["uid"] ?>"><?php __("Q"); ?></a>
		<?php
		if (!$val["enabled"])
			echo "<img src=\"icon/encrypted.png\" width=\"16\" height=\"16\" alt=\""._("Locked Account")."\">";
		else {
		?>
			<a href="adm_login.php?id=<?php echo $val["uid"];?>" target="_parent"><?php __("C"); ?></a>
		<?php } ?>
		</td>
		<td style="padding-right: 2px; border-right: 1px solid; <?php if ($val["su"]) echo "color: red"; ?>"><?php echo $val["login"] ?></td>
<?php
$val=$r[$z+$rz];
if (is_array($val)) {
?>
<?php if ($val["su"]) { ?>
			<td>&nbsp;</td>
<?php } else { ?>
 <td align="center"><input type="checkbox" class="inc" name="d[]" value="<?php echo $val["uid"]; ?>"></td>
<?php } ?>
		<td align="center">
		<a href="adm_edit.php?uid=<?php echo $val["uid"] ?>"><?php __("E"); ?></a>
		<a href="adm_quotaedit.php?uid=<?php echo $val["uid"] ?>"><?php __("Q"); ?></a>
		<?php
		if (!$val["enabled"])
			echo "<img src=\"icon/encrypted.png\" width=\"16\" height=\"16\" alt=\""._("Locked Account")."\">";
		else {
		  if($admin->checkcreator($val['uid'])) {
		?>
			<a href="adm_login.php?id=<?php echo $val["uid"];?>" target="_parent"><?php __("C"); ?></a>
		<?php } } ?>
		</td>
		<td style="padding-right: 2px; border-right: 1px solid; <?php if ($val["su"]) echo "color: red"; ?>"><?php echo $val["login"] ?></td>
<?php

} else echo "<td style=\"padding-right: 2px; border-right: 1px solid;\" colspan=\"3\"></td></tr>";

$val=$r[$z+2*$rz];
if (is_array($val)) {
?>
<?php if ($val["su"]) { ?>
			<td>&nbsp;</td>
<?php } else { ?>
 <td align="center"><input type="checkbox" class="inc" name="d[]" value="<?php echo $val["uid"]; ?>"></td>
<?php } ?>
		<td align="center">
		<a href="adm_edit.php?uid=<?php echo $val["uid"] ?>"><?php __("E"); ?></a>
		<a href="adm_quotaedit.php?uid=<?php echo $val["uid"] ?>"><?php __("Q"); ?></a>
		<?php
		if (!$val["enabled"])
			echo "<img src=\"icon/encrypted.png\" width=\"16\" height=\"16\" alt=\""._("Locked Account")."\">";
		else {
		?>
			<a href="adm_login.php?id=<?php echo $val["uid"];?>" target="_parent"><?php __("C"); ?></a>
		<?php } ?>
		</td>
		<td style="padding-right: 2px; border-right: 1px solid; <?php if ($val["su"]) echo "color: red"; ?>"><?php echo $val["login"] ?></td>
	</tr>
<?php
	} else echo "<td style=\"padding-right: 2px; border-right: 1px solid;\" colspan=\"3\"></td></tr>";
} // for loop
} // Short Mode


?>
<tr><td colspan="9"><input type="submit" class="inb" name="submit" value="<?php __("Delete checked accounts"); ?>" /></td></tr>
</table>
</form>
<?php
   printf("<p>"._("%s accounts")."</p>",count($r));
 } 
?>
<?php include_once("foot.php"); ?>
