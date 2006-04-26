<?php
/*
 $Id: mail_list.php,v 1.8 2005/04/01 16:05:26 benjamin Exp $
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
 Original Author of file: Benjamin Sonntag, Franck Missoum
 Purpose of file: Show the mail account list on domain $dom
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if(!$domain){
exit();
}
include("head.php");

if(!$res=$mail->enum_doms_mails($domain,1)) {
  $error=$err->errstr();

?>
</head>
<body>
<h3><?php printf(_("Mailbox list of the domain %s"),"http://$domain"); ?> : </h3>
<?php 
if ($error) {
  echo "<p class=\"error\">$error</p>";
}
echo "<p><a href=\"mail_add.php?domain=$domain\">".sprintf(_("Add a mailbox on <b>%s</b>"),$domain)."</a><br /";
echo "   <a href=\"mail_add.php?many=1&amp;domain=$domain\">".sprintf(_("Add many mailboxes on <b>%s</b>"),$domain)."</a></p>";
?>

</body>
</html>
<?php
}
else {

?>
</head>
<body>
<h3><?php printf(_("Mailbox list of the domain %s"),"http://$domain"); ?> : </h3>
<?php
if ($error) {
  echo "<p class=\"error\">$error</p>";
}

echo "<p><a href=\"mail_add.php?domain=$domain\">".sprintf(_("Add a mailbox on <b>%s</b>"),$domain)."</a><br /";
echo "   <a href=\"mail_add.php?many=1&amp;domain=$domain\">".sprintf(_("Add many mailboxes on <b>%s</b>"),$domain)."</a></p>";
?>

<form method="post" action="mail_dodel.php" id="main">

<table cellspacing="0" cellpadding="4">

<tr><th><input type="hidden" name="domain" value="<?php echo $domain ?>"/>
<?php __("Delete"); ?></th><th><?php __("Email address"); ?></th><th><?php __("Action"); ?></th><th><?php __("Size"); ?></th></tr>
<?php
$col=1;
for($i=0;$i<$res["count"];$i++) {
	$col=3-$col;
	$val=$res[$i];
	echo "<tr class=\"lst$col\">";
	echo "<td align=\"center\"><input class=\"inc\" type=\"checkbox\" id=\"del_$i\" name=\"d[]\" value=\"".$val["mail"]."\" /></td>
	<td><label for=\"del_$i\">".$val["mail"]."</label></td>
	<td><a href=\"mail_edit.php?email=".urlencode($val["mail"])."&amp;domain=".urlencode($domain)."\">"._("Edit")."</a></td>";
	if ($val["pop"]) {
		echo "<td>".format_size($val["size"])."</td>";
	} else {
		echo "<td>&nbsp;</td>";
	}
	echo "</tr>";

}
?>
<tr><td colspan="5"><input type="submit" class="inb" name="submit" value="<?php __("Delete the selected mailboxes"); ?>" /></td></tr>
</table>
</form>

<?php
}
?>
</body>
</html>
