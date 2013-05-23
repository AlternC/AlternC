<?php
/*
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2006 Le réseau Koumbit Inc.
 http://koumbit.org/
 Copyright (C) 2002 by the AlternC Development Team.
 http://alternc.org/
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
	"creator" => array("request", "integer", 0),
	"short"   => array("request", "integer", -1),
);
getFields($fields);


if ($short!=-1) {
  $mem->adminpref($short);
  $mem->user["admlist"]=$short;
 }

$subadmin=variable_get("subadmin_restriction", 0);

// If we ask for all account but we aren't "admin" and
// subadmin var is not 1
if ($show=="all" && !$subadmin==1 && $cuid != 2000) {
	__("This page is restricted to authorized staff");
	exit();
}

$r=$admin->get_list($show == 'all' ? 1 : 0, $creator);

?>
<h3><?php __("AlternC account list"); ?></h3>
<hr id="topbar"/>
<br />
<?php
	if (isset($error) && !empty($error) ) {
	  echo "<p class=\"error\">$error</p>";
	}
?>
<p>
<?php __("Here is the list of hosted AlternC accounts"); ?> (<?php printf(_("%s accounts"),count($r)); ?>)
</p>
<?php

if ($subadmin==1 || $cuid==2000) {
if($show != 'all') {
  echo '<p><span class="ina"><a href="adm_list.php?show=all">' . _('List all AlternC accounts') . '</a></span>';
  if ($subadmin==1 || $cuid==2000) {
    $list_creators = $admin->get_creator_list();
    $infos_creators = array();

    foreach ($list_creators as $key => $val) {
      $infos_creators[] = '<a href="adm_list.php?creator=' . $val['uid'] . '">' . $val['login'] . '</a>';
    }

    if (count($infos_creators)) {
      echo ' ('._("Or only the accounts of:")." ". implode(', ', $infos_creators) . ')';
    }
    echo "</p>";
  }
} else {
  echo '<p><span class="ina"><a href="adm_list.php">' . _('List only my accounts') . '</a></span></p>';
}
}// END ($subadmin==1 || $cuid==2000)
?>
  <p><span class="ina"><a href="adm_add.php"><?php __("Create a new AlternC account"); ?></a></span></p>

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
<p>
<span class="ina" style="float: right;"><a href="adm_list.php?short=1"><?php __("Minimal view"); ?></a></span> &nbsp;
<?php  if (count($r)>5) { ?>
<input type="submit" class="inb" name="submit" value="<?php __("Delete checked accounts"); ?>" />
<?php } ?>
</p>
<table class="tlist" style="clear: both;">
<tr>
<th></th>
<th><?php __("Account"); ?></th>
<th><?php __("Manager"); ?></th>
<th><?php __("Created by") ?></th>
<th><?php __("Created on") ?></th>
<th><?php __("Quotas") ?></th>
<th><?php __("Last login"); ?></th>
<th><?php __("Last ip"); ?></th>
<th><?php __("Fails"); ?></th>
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
 if ($val["su"]) { ?>
		   <td id="user_<?php echo $val["uid"]; ?>">&nbsp;</td>
<?php } else { ?>
 <td><input type="checkbox" class="inc" name="d[]" id="user_<?php echo $val["uid"]; ?>" value="<?php echo $val["uid"]; ?>" /></td>
<?php } ?>
		<td <?php if ($val["su"]) echo "style=\"color: red\""; ?>><label for="user_<?php echo $val["uid"]; ?>"><b><?php echo $val["login"] ?></b></label></td>
		<td><a href="mailto:<?php echo $val["mail"]; ?>"><?php echo $val["nom"]." ".$val["prenom"] ?></a>&nbsp;</td>
		<td><?php echo $val["parentlogin"] ?></td>
		<td><?php echo format_date(_('%3$d-%2$d-%1$d'),$val["created"]); ?></td>
		<td><?php echo $val["type"] ?></td>
		<td><?php echo $val["lastlogin"] ?></td>
                <td><?php echo $val["lastip"] ?></td>
		<td><?php echo $val["lastfail"] ?></td>
		<td><div class="<?php echo 'exp' . $admin->renew_get_status($val['uid']) ?>"><?php echo $admin->renew_get_expiry($val['uid']) ?></div></td>
	</tr>

<tr class="lst<?php echo $col; ?>" >
<td/><td ><i><?php echo _("DB:").' '.$val['db_server_name']?></i></td>
<td colspan="8" >
<div name="admlistbtn">
<span class="ina<?php if ($col==2) echo "v"; ?> lst<?php echo $col; ?>">
  <a href="adm_login.php?id=<?php echo $val["uid"];?>"><?php __("Connect as"); ?></a>
</span>&nbsp;
	&nbsp;
<span class="ina<?php if ($col==2) echo "v"; ?> lst<?php echo $col; ?>" >
  <a href="adm_edit.php?uid=<?php echo $val["uid"] ?>"><?php __("Edit"); ?></a>
</span>&nbsp;
<span class="ina<?php if ($col==2) echo "v"; ?> lst<?php echo $col; ?>" >
  <a href="adm_quotaedit.php?uid=<?php echo $val["uid"] ?>"><?php __("Quotas"); ?></a>
</span>&nbsp;
	<?php if (!$val["su"]) { ?>
<span class="ina<?php if ($col==2) echo "v"; ?> lst<?php echo $col; ?>" >
  <a href="adm_deactivate.php?uid=<?php echo $val["uid"] ?>"><?php __("Disable"); ?></a>
</span>&nbsp;
	  <?php } ?>
</div>
</td>
</tr>
<?php
	}

} // NORMAL MODE

if ($mem->user["admlist"]==1) { // SHORT MODE
?>

  [&nbsp;<?php __("C"); ?>&nbsp;] <?php __("Connect as"); ?> &nbsp; &nbsp; 
  [&nbsp;<?php __("E"); ?>&nbsp;] <?php __("Edit"); ?> &nbsp; &nbsp; 
  [&nbsp;<?php __("Q"); ?>&nbsp;] <?php __("Quotas"); ?> &nbsp; &nbsp; 

<p>
<span class="ina" style="float:right;"><a href="adm_list.php?short=0"><?php __("Complete view"); ?></a></span> &nbsp;
<?php  if (count($r)>50) { ?>
<input type="submit" class="inb" name="submit" value="<?php __("Delete checked accounts"); ?>" />
<?php } ?>
</p>
<table class="tlist" style="clear:both;">
<tr>
   <th colspan="2"> </th><th><?php __("Account"); ?></th>
   <th colspan="2"> </th><th><?php __("Account"); ?></th>
   <th colspan="2"> </th><th><?php __("Account"); ?></th>
</tr>
<?php
reset($r);

$rz=ceil(count($r)/3);

for($z=0;$z<$rz;$z++){
  $val=$r[$z];
?>
	<tr class="lst">
<?php if ($val["su"]) { ?>
			<td>&nbsp;</td>
<?php } else { ?>
 <td align="center"><input type="checkbox" class="inc" name="d[]" value="<?php echo $val["uid"]; ?>" id="id_c_<?php echo $val["uid"]; ?>" /></td>
<?php } ?>
		<td align="center">
		   <a href="adm_login.php?id=<?php echo $val["uid"];?>" title="<?php __("Connect as"); ?>">[&nbsp;<?php __("C"); ?>&nbsp;]</a>
		   <a href="adm_edit.php?uid=<?php echo $val["uid"] ?>" title="<?php __("Edit"); ?>">[&nbsp;<?php __("E"); ?>&nbsp;]</a>
<?php		  if($admin->checkcreator($val['uid'])) { ?>
		   <a href="adm_quotaedit.php?uid=<?php echo $val["uid"] ?>" title="<?php __("Quotas"); ?>">[&nbsp;<?php __("Q"); ?>&nbsp;]</a>
							  <?php } ?>
		</td>
		<td style="padding-right: 2px; border-right: 1px solid black; <?php if ($val["su"]) echo "color: red"; ?>"><b><label for="id_c_<?php echo $val["uid"]; ?>"><?php echo $val["login"] ?></label></b></td>
<?php
$val=$r[$z+$rz];
if (is_array($val)) {
?>
<?php if ($val["su"]) { ?>
			<td>&nbsp;</td>
<?php } else { ?>
 <td align="center"><input type="checkbox" class="inc" name="d[]" value="<?php echo $val["uid"]; ?>" id="id_c_<?php echo $val["uid"]; ?>" /></td>
<?php } ?>
		<td align="center">
		   <a href="adm_login.php?id=<?php echo $val["uid"];?>" title="<?php __("Connect as"); ?>">[&nbsp;<?php __("C"); ?>&nbsp;]</a>
		   <a href="adm_edit.php?uid=<?php echo $val["uid"] ?>" title="<?php __("Edit"); ?>">[&nbsp;<?php __("E"); ?>&nbsp;]</a>
<?php		  if($admin->checkcreator($val['uid'])) { ?>
		   <a href="adm_quotaedit.php?uid=<?php echo $val["uid"] ?>" title="<?php __("Quotas"); ?>">[&nbsp;<?php __("Q"); ?>&nbsp;]</a>
							  <?php } ?>
		</td>
		<td style="padding-right: 2px; border-right: 1px solid black; <?php if ($val["su"]) echo "color: red"; ?>"><b><label for="id_c_<?php echo $val["uid"]; ?>"><?php echo $val["login"] ?></label></b></td>
<?php

} else echo "<td style=\"padding-right: 2px; border-right: 1px solid;\" colspan=\"3\"></td></tr>";

$val=null;
if (isset($r[$z+2*$rz])) {
  $val=$r[$z+2*$rz];
}
 
if (is_array($val)) {
?>
<?php if ($val["su"]) { ?>
			<td id="id_c_<?php echo $val["uid"]; ?>">&nbsp;</td>
<?php } else { ?>
 <td align="center"><input type="checkbox" class="inc" name="d[]" value="<?php echo $val["uid"]; ?>" id="id_c_<?php echo $val["uid"]; ?>" /></td>
<?php } ?>
		<td align="center">
		   <a href="adm_login.php?id=<?php echo $val["uid"];?>" title="<?php __("Connect as"); ?>">[&nbsp;<?php __("C"); ?>&nbsp;]</a>
		   <a href="adm_edit.php?uid=<?php echo $val["uid"] ?>" title="<?php __("Edit"); ?>">[&nbsp;<?php __("E"); ?>&nbsp;]</a>
<?php		  if($admin->checkcreator($val['uid'])) { ?>
		   <a href="adm_quotaedit.php?uid=<?php echo $val["uid"] ?>" title="<?php __("Quotas"); ?>">[&nbsp;<?php __("Q"); ?>&nbsp;]</a>
							  <?php } ?>
		</td>
		<td style="padding-right: 2px; border-right: 1px solid black; <?php if ($val["su"]) echo "color: red"; ?>"><b><label for="id_c_<?php echo $val["uid"]; ?>"><?php echo $val["login"] ?></label></b></td>
	</tr>
<?php
	} else echo "<td style=\"padding-right: 2px; border-right: 1px solid;\" colspan=\"3\"></td></tr>";
} // for loop
} // Short Mode


?>
</table>
<p><input type="submit" class="inb" name="submit" value="<?php __("Delete checked accounts"); ?>" /></p>
</form>
<?php
 }
?>
<?php include_once("foot.php"); ?>
