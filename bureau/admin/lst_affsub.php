<?php
/*
 $Id: lst_affsub.php,v 1.1.1.1 2003/03/26 17:41:29 root Exp $
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
 Purpose of file: show and delete subscribers in the mailing-list 
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

include("head.php");

$r=$sympa->get_ml($id);

$u=$sympa->get_ml_users($id,$offset);

$count=$u["affiche"]; // nombre de mail à afficher
?>
</head>
	<body>

	<div align="center"><h3><?php printf(_("Mailing list %s"),$r["list"]); ?></h3></div>

	<hr>
	<?php
		// test pour que l'affichage du nombre de mail, ne dépasse le total des inscrits
		if ($offset+$count>$u["count"])
			$top=$u["count"];
		else
			$top=$offset+$count;

		// test pour afficher un lien pour afficher tous les inscrits s'il sont supérieurs au nombre affiché
		if ($u["count"]>$count)
			$lien="<a href='lst_affallsub.php?id=".$id."'>"._("Show all subscribed emails")."</a>";
		else
			$lien="";

		// affichage de la position où l'on se trouve dans le tableau
		printf(_("From %s to %s sur %s"),($offset+1),$top,$u["count"]);
		echo "&nbsp;&nbsp;".$lien;
	?>
	<br>
	<!-- Affichage des boutons de navigation dans un tableau (en haut de page)-->
	<table cellspacing="0" cellpadding="4" border="0" cols="4" width="140">
	<tr valign="top">
	<td align="center" width="35">
		<?php
		if ($offset>0) { // Bouton Début si il y a besoin
		?>
<form method="post" action="lst_affsub.php?id=<?php echo $id ?>&offset=0">
<input type="submit" class="inb" name="precedent" value="<< ">
</form>
		<?php
		} else {
			echo "&nbsp;";
		}
		?>
	</td>
	<td width="35" align="center">
		<?php
		if ($offset>0) { // Bouton précedent si il y a besoin
		?>
<form method="post" action="lst_affsub.php?id=<?php echo $id ?>&offset=<?php echo $offset-$count; ?>">
<input type="submit" class="inb" name="precedent" value=" < ">
</form>
		<?php
		} else {
			echo "&nbsp;";
		}
		?>
	</td><td width="35" align="center">
		<?php
		if ($offset+$count<$u["count"]) { // Bouton suivant si il y a besoin
		?>
			<form method="post" action="lst_affsub.php?id=<?php echo $id ?>&offset=<?php echo $offset+$count; ?>">
			<input type="submit" class="inb" name="precedent" value=" > ">
			</form>
		<?php
		} else {
			echo "&nbsp;";
		}
		?>
	</td>
	<td align="center" width="30">
	<?php
		if ($offset+$count<$u["count"]) { // Bouton Fin si il y a besoin
			// Calcul de $fin argh, la boucle for !
			for ($i=0;$i<$u["count"];$i+=$count){
				$fin=$i;
			}
			?>
			<form method="post" action="lst_affsub.php?id=<?php echo $id ?>&offset=<?php echo $fin; ?>">
			<input type="submit" class="inb" name="precedent" value=" >>">
			</form>
			<?php
		} else {
			echo "&nbsp;";
		}
			?>
	</td></tr>
	</table>

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

	<!-- Affichage des boutons de navigation dans un tableau (en bas de page)-->
	<table cellspacing="0" cellpadding="4" border="0" cols="4" width="140">
	<td valign="top">

		<?php
		if ($offset>0) { // Bouton Début si il y a besoin
		?>
<form method="post" action="lst_affsub.php?id=<?php echo $id ?>&offset=0">
<input type="submit" class="inb" name="precedent" value="<< ">
</form>
		<?php
		} else {
			echo "&nbsp;";
		}
		?>
	</td>
	<td width="35" align="center">
		<?php
		if ($offset>0) { // Bouton précedent si il y a besoin
		?>
<form method="post" action="lst_affsub.php?id=<?php echo $id ?>&offset=<?php echo $offset-$count; ?>">
<input type="submit" class="inb" name="precedent" value=" < ">
</form>
		<?php
		} else {
			echo "&nbsp;";
		}
		?>
	</td><td width="35" align="center">
		<?php
		if ($offset+$count<$u["count"]) { // Bouton suivant si il y a besoin
		?>
			<form method="post" action="lst_affsub.php?id=<?php echo $id ?>&offset=<?php echo $offset+$count; ?>">
			<input type="submit" class="inb" name="precedent" value=" > ">
			</form>
		<?php
		} else {
			echo "&nbsp;";
		}
		?>
	</td>
	<td align="center" width="30">
	<?php
		if ($offset+$count<$u["count"]) { // Bouton Fin si il y a besoin
			// Calcul de $fin argh, la boucle for ! on teste ca pour voir...
			$fin=($count*intval($u["count"]/$count))+1;
			?>
			<form method="post" action="lst_affsub.php?id=<?php echo $id ?>&offset=<?php echo $fin; ?>">
			<input type="submit" class="inb" name="precedent" value=" >>">
			</form>
			<?php
		} else {
			echo "&nbsp;";
		}
			?>
	</td></tr>
	</table>
	<br>
	<!-- Liens pour passer d'une table à l'autre en se basant sur le nom -->
	<?php
	// Condition pour savoir s'il y a plus d'une page à afficher
	$u=$sympa->get_ml_all_users($id);
	if ($u["count"]>$count) {
		echo "<p>"._("To access directly a page from a mail address:")."<br>";
		for ($i=0;$i<$u["count"];$i+=$count){
			if ($i+$count<$u["count"]) {
				// $requete="SELECT mail FROM subscribers LIMIT $j,1";
				$j=$i+$count-1;
				echo _("From ")."<a href='lst_affsub.php?id=".$id."&offset=".$i."'>".$u["mail"][$i]."</a> "._("to")." <a href='lst_affsub.php?id=".$id."&offset=".$i."'>".$u["mail"][$j]."</a><br>";
			}
			else { // dernier liens à la fin
				// $j=$total-1;
				// $requete="SELECT mail FROM subscribers LIMIT $j,1";
				$j=$u["count"]-1;
				echo _("From ")."<a href='lst_affsub.php?id=".$id."&offset=".$i."'>".$u["mail"][$i]."</a> "._("to")." <a href='lst_affsub.php?id=".$id."&offset=".$i."'>".$u["mail"][$j]."</a><br>";
			}
		} // fin for
	} // fin if
?>
	<hr>
	<a href="lst_subscribers.php?id=<?php echo $id; ?>"><?php __("Back to the subscription page"); ?></a>
</body>
</html>