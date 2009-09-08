<?php
/*
 $Id: quota_show.php,v 1.3 2003/08/13 23:52:24 root Exp $
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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

?>
<h3><?php __("Account's quotas"); ?> : </h3>
<?php
$q=$quota->getquota();
if (!is_array($q)) {
	echo "<p class=\"error\">"._("No quotas for this account, or quotas currently unavailable!")."</p>";
} else {
	echo "<table cellspacing=\"0\" cellpadding=\"4\"><tr><th>"._("Quota")."</th><th>"._("Used")."</th><th>"._("Total")."</th></tr>";
	$qlist=$quota->qlist();
	reset($qlist);
	$col=1;
 	while (list($key,$val)=each($qlist)) {
		if (!$q[$key]["t"]) continue;
 		$col=3-$col;
 		echo "<tr class=\"lst$col\">";
 		echo "<td>";
		if ($q[$key]["u"] >= $q[$key]["t"]) echo "<font class=\"over\">";
 		echo _($val);
		if ($q[$key]["u"] >= $q[$key]["t"]) echo "</font>";

		if ($key == 'bw_web') {              
			echo "&nbsp;</td><td align=\"center\">". format_size($q[$key]["u"]) ."&nbsp;</td><td align=\"center\">". format_size($q[$key]["t"]) ."&nbsp;</td>";
		} elseif ($key == 'web') {
			echo "&nbsp;</td><td align=\"center\">". format_size($q[$key]["u"] * 1024) . "&nbsp;</td><td align=\"center\">". format_size($q[$key]["t"] * 1024) ."&nbsp;</td>";
		} else {
			echo "&nbsp;</td><td align=\"center\">".$q[$key]["u"]."&nbsp;</td><td align=\"center\">".$q[$key]["t"]."&nbsp;</td>";
		}

 		echo "</tr>";
 	}
	echo "</table>";
}
?>
<?php include_once("foot.php"); ?>