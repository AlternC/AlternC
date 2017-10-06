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
<hr id="topbar"/>
<br />
<?php
$q=$quota->getquota();
if (!is_array($q) || empty($q) ) {
  $msg->raise("ALERT", "quota", _("No quotas for this account, or quotas currently unavailable!"));
  include_once("main.php");
  exit();
} 

echo "<table cellspacing=\"0\" cellpadding=\"4\" class='tlist'>";
echo "<tr><th>"._("Quota")."</th><th>"._("Used")."</th><th>"._("Total")."</th><th>"._("Size on disk")."</th></tr>";
$qlist=$quota->qlist();
reset($qlist);
$totalsize = 0;
while (list($key,$val)=each($qlist)) {
  if ( !isset($q[$key]) || !$q[$key]["t"]) continue;
   echo "<tr class=\"lst\">";
   echo "<td>";
  if ($q[$key]["u"] >= $q[$key]["t"]) echo "<font class=\"over\">";
   echo _($val);
  if ($q[$key]["u"] >= $q[$key]["t"]) echo "</font>";

  if (($key == 'web')||(isset($q[$key]['type'])&&($q[$key]['type']=='size'))) {
    echo "&nbsp;</td><td>". format_size($q[$key]["u"] * 1024) . "&nbsp;</td><td>&nbsp;</td>";
  } else {
    echo "&nbsp;</td><td>".$q[$key]["u"]."&nbsp;</td><td>".$q[$key]["t"]."&nbsp;</td>";
  }

  if (isset($q[$key]['s'])) {
    $totalsize += $q[$key]["s"];
    echo "<td>". format_size($q[$key]["s"] * 1024) . "&nbsp;</td>";
  } else {
    echo "<td>-&nbsp;</td>";
  }
  echo "</tr>";
}
echo "<tr><td colspan='2'></td><td align='right'><b>"._("Total").":&nbsp;</b></td><td><b>".format_size($totalsize * 1024)." / ".format_size($q['web']["t"] * 1024)."</b></td></tr>";
echo "</table>";

include_once("foot.php"); 

?>
