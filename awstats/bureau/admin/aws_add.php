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
 Purpose of file: Create a new awstat statistic set.
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$fields = array (
	"id" => array ("request", "integer", 0),
);
getFields($fields);

if (!$id && !$quota->cancreate("aws")) {
	$error=_("You cannot add any new statistics, your quota is over.");
}

include_once("head.php");
?>
<h3><?php if (!$id) { __("New Statistics"); } else { __("Edit Statistics"); } ?></h3>
<hr id="topbar"/>
<br />
<?php __("Awstats will analyze every logs availables for this domain (since its creation).");?>
<br/>
<br/>
<?php
	if (isset($error) && $error) {
		echo "<p class=\"error\">$error</p></body></html>";
	}
?>
<form method="post" action="<?php if (!$id) echo "aws_doadd.php"; else echo "aws_doedit.php"; ?>" id="main" name="main">
<table class="tedit">
<tr><th><input type="hidden" name="id" value="<?php echo $id ?>" />
        <label for="hostname"><?php __("Domain name"); ?></label></th><td>
<?php if (!$id) { ?>
	<select class="inl" name="hostname" id="hostname"><?php $aws->select_host_list($hostname); ?></select>
<?php } else { ?>
	<code><?php echo $hostname; ?></code>
<?php } ?>
</td></tr>
<tr><th><?php __("Hostaliases"); ?></th><td>
<?php  // TODO : put them on 2 columns (at least)
$hl=$aws->host_list();
reset($hl);
$hatab=$aws->get_hostaliases($id);
while (list($key,$val)=each($hl)) {
  $ho=$val["hostname"];
  $ty=$val["desc"];
  echo "<input type=\"checkbox\" name=\"hostaliases[]\" id=\"ha_$ho\" value=\"$ho\"";
  if (in_array($ho,@explode(" ",$hatab[0]))) echo " checked=\"checked\"";
  echo " /><label for=\"ha_$ho\">$ho ($ty)</label><br />\n";
}
?>
</td></tr>
<tr><th><?php __("Allowed Users"); ?></th><td>
<?php 
// List the users (and check allowed ones) : 
$r=$aws->list_allowed_login($id);

if (is_array($r)) {
?>
<?php
foreach($r as $v) {
	echo "<input type=\"checkbox\" name=\"awsusers[]\" class=\"int\" id=\"u_".htmlentities($v["login"])."\" value=\"".htmlentities($v["login"])."\" ";
	if ($v["selected"]) echo " checked=\"checked\"";
	echo "><label for=\"u_".htmlentities($v["login"])."\">".$v["login"]."</label><br />\n";
}
?>
<?php
} else {
	__("No users currently defined, you must create login with the 'Manage allowed users' accounts' menu.");
}

?></td></tr>
<tr class="trbtn"><td colspan="2">
  <input type="submit" class="inb" name="submit" value="<?php if (!$id)  __("Create those statistics"); else __("Edit those statistics"); ?>" />
  <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='aws_list.php'"/>

</td></tr>
</table>
</form>
<?php include_once("foot.php"); ?>
