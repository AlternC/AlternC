<?php
/*
 $Id: quota_show_all.php,v 1.4 2005/10/06 16:18:25 anarcat Exp $
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

include("head.php");
?>
</head>
<body>
<h3><?php __("Quotas"); ?></h3>

<?php

$quota_utilise = array();
$tot = array();

if ($mem->user['uid'] == "2000")
  $user_list = $admin->get_list(1);
else{
  $user_list = $admin->get_list(0);
  $user_list[] = $mem->user;
}
$class = ($class== 'lst1' ? 'lst2' : 'lst1');
print "<table><tr class=\"$class\">";
$ql = $quota->qlist();
reset($ql);
print "<td>"._("User")."</td>";
$sequence = array();
foreach ($ql as $key => $name) {
  print "<td>$name</td>";
  $sequence[] = $key;
}
print "</tr>";
$u = array();
foreach ($user_list as $user) {
  $u[$user['uid']] = $user['login'];
}
asort($u);
foreach ($u as $uid => $login) {
  $class = ($class== 'lst1' ? 'lst2' : 'lst1');
  print "<tr class=\"$class\"><td>";
  print $login.'('.$uid.")</td>";
  $mem->su($uid);
  if (!($quots = $quota->getquota())) {
    $error = $err->errstr();
  }
  foreach($sequence as $key) {
    $q = $quots[$key];
    if ($q['u'] > $q['t']) {
      $style = ' style="color: red"';
    } else {
      $style = '';
    }
    $quota_utilise[$key] += $q['u']; 
    $tot[$key]+= $q['t']; 
    print "<td $style>".str_replace(" ", "&nbsp;", m_quota::display_val($key, $q['u']).'/'.m_quota::display_val($key, $q['t'])).'</td>';
  }
  print "</tr>";
  $mem->unsu();
}

echo "<tr>";
echo "<td $style><b>"._("Total")."</b></td>";
foreach($sequence as $key) {
  echo "<td $style><b>";
  echo $quota_utilise[$key]."/".$tot[$key];
  echo "</b></td>";
}
echo "</tr>";

print "</table>";

?>
</body>
</html>
