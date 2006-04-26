<?php
/*
 $Id: lst_affallsub.php,v 1.1.1.1 2003/03/26 17:41:29 root Exp $
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
 Original Author of file: Louis Sylvain
 Purpose of file: show and delete all subscribers in the mailing-list 
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

include("head.php");

$r=$sympa->get_ml($id);

$u=$sympa->get_ml_all_users($id);

?>
</head>
	<body>

	<div align="center"><h3><?php printf(_("Mailing list %s"),$r["list"]); ?></h3></div>

	<hr>
	
	<!-- Tableau affichant la liste des mails inscrits -->
	<table cellspacing="0" cellpadding="4" border="0" cols="4">
	<tr>
	<form method="post" action="lst_delsub.php?id=<?php echo $id ?>&offset=<?php echo $offset ?>">
	<th width="35"><?php __("Delete"); ?></th><th><?php __("Email address"); ?></th><th><?php __("Name"); ?></th><th>&nbsp;</th></tr>
	<?php
		$col=1;
		for($i=0;$i<count($u["mail"]);$i++) {
			$c=$u["mail"][$i];
			$d=$u["nom"][$i];
			$col=3-$col;
			echo "<tr class=\"lst$col\"><td align=\"center\"><input type=\"checkbox\" class=\"inc\" name=\"del_".$i."\" 	value=\"".$c."\"></td>";
			echo "<td>".$c."</td>";
			echo "<td>".$d."</td>";
			echo "<td align=\"center\"><a href='lst_editsub.php?id=".$id."&mail=".$c."&name=".$d."&offset=".$offset."'>"._("Edit")."</a></td></tr>";
		} // fin for
	?>
	<tr valign="top">
	<td align="left" colspan="4">
		<input type="submit" class="inb" name="submit" value="<?php __("Delete the checked subscribers"); ?>">
		</form></td>
	</tr>
	</table>

	<br>
	<hr>
	<a href="lst_subscribers.php?id=<?php echo $id; ?>"><?php __("Back to the subscription page"); ?></a>
</body>
</html>