<?php
/*
 $Id: lst_subscribers.php,v 1.1.1.1 2003/03/26 17:41:29 root Exp $
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
 Purpose of file: Add or delete subscribers in the mailing-list
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

include("head.php");
$r=$sympa->get_ml($id);

$total=$sympa->get_ml_users($id,0);
?>
	</head>
	<body>

	<div align="center"><h3><?php printf(_("Mailing list %s"),$r["list"]); ?></h3></div>
	<?php

	// Affichage des liens pour vpir ou récupérer la liste des inscrits si il y en a
	if ($total==0) {
		$error=$err->errstr();
		echo "<font color=\"#FF0000\">".$error."</font>";
	} else {
	?>

	<p><?php printf(_("There is %s subscriber(s) in your list"),$total["count"]); ?></p>
	<p><a href="lst_affsub.php?id=<?php echo $id ?>&offset=0"><?php __("View, edit and delete subscribers"); ?></a></p>
	<p><a href="lst_downsub.php?id=<?php echo $id ?>"><?php __("Download the subscribers list"); ?></a></p>
	<?php } // fin if
	?>
	<hr>

	<!-- champ de saisie permettant de saisir un email -->
	<form method="post" action="lst_addsub.php?id=<?php echo $id ?>">
	<P><b><?php __("Add a subscriber to the list"); ?></b></P>
	<br>
	<table cellspacing="0" cellpadding="4">
	<tr>
		<th align="left"><?php __("Email address"); ?></th>
		<td><input type="text" class="int" name="user" value="" size="20"></td>
	</tr>
	<tr>
		<th align="left"><?php __("User's name"); ?></th>
		<td><input type="text" class="int" name="nom_user" value="" size="20"></td>
	</tr>
	<tr>
		<td colspan="2" align="center"><input type="submit" class="inb" name="submit" value="<?php __("Subscribe this email to the list"); ?>"></td>
	</tr>
	</table>
	</form>

	<hr>

	<!-- Textearea permettant de saisir directement plusieurs emails -->
	<form method="post" action="lst_addsub1.php?id=<?php echo $id ?>">
	<P><b><?php __("Subscribe many users to the list:"); ?></b></P>
	<table cellspacing="0" cellpadding="4">
	<tr>
		<th valign="top"><?php __("Enter the user email list, one per line"); ?></th>
	</tr>
	<tr>
		<td align="center"><textarea cols="30" rows="15" class="int" name="inscrire"></textarea></td>
	</tr>
	<tr>
		<td align="center"><input type="submit" class="inb" name="submit" value="<?php __("Subscribe those users to the list"); ?>"></td>
	</tr>
	</table>
	</form>
	<hr>
</body>
</html>