<?php
/*
 $Id: lst_owner.php,v 1.1.1.1 2003/03/26 17:41:29 root Exp $
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
 Original Author of file: Benjamin Sonntag, Franck Missoum, Louis Sylvain
 Purpose of file: Add or delete owners and moderators in the list 
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

include("head.php");
if(!$r=$sympa->get_ml($id)) {
  $error=$err->errstr();
}
?>
</head>
<body>

<div align="center"><h3><?php printf(_("Mailing list %s"),$r["list"]); ?></h3></div>
<?php echo "<br><font color=red>$error</font><br>";?>

<hr>

<!-- Modification des propriétaires de la liste -->
<form method="post" action="lst_addown.php?id=<?php echo $id ?>">
<P><b><?php __("Add an owner to the list"); ?></b></P>
<br>
<table cellspacing="0" cellpadding="4">
<tr>
	<th><?php __("Enter the new owner's email"); ?> : </th>
	<td><input type="text" class="int" name="owner" value="" size="20"></td>
</tr><tr>
	<td colspan="2" align="center"><input type="submit" class="inb" name="submit" value="<?php __("Add this owner to the list"); ?>"></td>
</tr><tr>
	<td colspan="2"><font color="#007799"><?php __("Note: an owner is also a moderator"); ?></font></td>
</tr>
</table>
<br><br>
</form>

<hr>

<!-- Suppression d'un ou plusieurs propriétaires de la liste -->
<form method="post" action="lst_delown.php?id=<?php echo $id ?>">
<P><b><?php __("Delete one or more owners from the list"); ?></b></P>
<br>
<table cellspacing="0" cellpadding="4">
<tr><th><?php __("Delete"); ?></th><th><?php __("Email address"); ?></th></tr>
<?php

$col=1;
for($i=0;$i<$r["owner"]["count"];$i++) {
	$c=$r["owner"][$i];
	$col=3-$col;
	echo "<tr class=\"lst$col\"><td align=\"center\"><input type=\"checkbox\" class=\"inc\" name=\"del[".$i."]\" value=\"".$c."\"></td>";
	echo "<td>".$c."</td></tr>";
}
echo "<tr><td colspan=\"2\"><input type=\"hidden\" name=\"count\" value=\"".$r["owner"]["count"]."\"></td></tr>";
?>
<tr><td align="center" colspan="2"><input type="submit" class="inb" name="submit" value="<?php __("Delete the checked owners"); ?>"></td></tr>
<tr><td colspan="2"><font color="#007799"><?php __("Note: a list must have at least one owner"); ?></font></td></tr>
</table>
</form>

<hr>
</body>
</html>