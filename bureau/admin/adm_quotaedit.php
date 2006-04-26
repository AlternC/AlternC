<?php
/*
 $Id: adm_quotaedit.php,v 1.4 2004/10/24 20:09:21 anonymous Exp $
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
 Purpose of file: Show the form to edit the user's quota
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
	__("This page is restricted to authorized staff");
	exit();
}

if (!$us=$admin->get($uid)) {
	$error=$err->errstr();
}

$mem->su($uid);
if (!$r=$quota->getquota()) {
	$error=$err->errstr();
}
$mem->unsu();
include("head.php");
?>
</head>
<body>
<h3><?php __("Editing the quotas of a member"); ?></h3>
<?php
	if ($error) {
	  echo "<p class=\"error\">$error</p>";
	  exit();
	}
?>
<form method="post" action="adm_quotadoedit.php">
<table border="1" cellspacing="0" cellpadding="6">
<tr><th><input type="hidden" name="uid" value="<?php echo $uid ?>" />
<?php __("Username"); ?></th><td colspan="3"><code><big><?php echo $us["login"]; ?></big></code>&nbsp;</td></tr>
<tr><th><?php __("Quota"); ?></th><th><?php __("Total"); ?></th><th><?php __("Used"); ?></th></tr>
<?php
$ql=$quota->qlist();
reset($ql);
while (list($key,$val)=each($ql)) {
	echo "<tr>";
	echo "<td>";
	if ($r[$key]["t"]==$r[$key]["u"] && $r[$key]["u"]) echo "<font color=red>";
	echo _("quota_".$key)."</td>";
	if ($r[$key]["t"]==$r[$key]["u"] && $r[$key]["u"]) echo "</font>";
	echo "<td align=\"center\"><input type=\"text\" class=\"int\" style=\"text-align: right\" size=\"10\" maxlength=\"10\" value=\"".$r[$key]["t"]."\" name=\"q_".$key."\" id=\"q_".$key."\" /></td>";
	echo "<td align=\"right\"><code><label for=\"q_$key\">".$r[$key]["u"]."</label></code>&nbsp;</td>";
	echo "</tr>";
}
?>
<tr><td colspan="4" align="center"><input class="inb" type="submit" name="submit" value="<?php __("Edit the quotas"); ?>" />
</td></tr>
</table>
</form>
</body>
</html>
