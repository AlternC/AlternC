<?php
/*
 $Id: ftp_list.php,v 1.5 2003/06/10 13:16:11 root Exp $
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
 Purpose of file: List ftp accounts of the user.
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$noftp=false;
if (!$r=$ftp->get_list($domain)) {
	$noftp=true;
	$error=$err->errstr();
}

include("head.php");
?>
</head>
<body>
<h3><?php __("FTP accounts list"); ?></h3>
<?php
	if ($noftp) {
?>
	<p class="error"><?php echo $error ?></p>
	<a href="ftp_add.php"><?php __("Create a new ftp account") ?></a><br />
	<?php $mem->show_help("ftp_list_no"); ?>
	</body></html>
<?php
		exit();
	}

if ($error) {
?>
<p class="error"><?php echo $error ?></p>
<?php } ?>
<form method="post" action="ftp_del.php">
<table cellspacing="0" cellpadding="4">
<tr><th colspan="2">&nbsp;</th><th><?php __("Username"); ?></th><th><?php __("Folder"); ?></th></tr>
<?php
reset($r);
$col=1;
while (list($key,$val)=each($r))
	{
	$col=3-$col;
?>
	<tr class="lst<?php echo $col; ?>">
		<td align="center"><input type="checkbox" class="inc" id="del_<?php echo $val["id"]; ?>" name="del_<?php echo $val["id"]; ?>" value="<?php echo $val["id"]; ?>" /></td>
		<td><a href="ftp_edit.php?id=<?php echo $val["id"] ?>"><?php __("Edit"); ?></a></td>
		<td><label for="del_<?php echo $val["id"]; ?>"><?php echo $val["login"] ?></label></td>
		<td><code>/<?php echo $val["dir"] ?></code></td>
	</tr>
<?php
	}
?>
<tr><td colspan="5"><input type="submit" name="submit" class="inb" value="<?php __("Delete checked accounts"); ?>" /></td></tr>
</table>
</form>

<?php if ($quota->cancreate("ftp")) { ?>
<p>
<a href="ftp_add.php"><?php __("Create a new ftp account"); ?></a>
</p>
<?php  	}

$mem->show_help("ftp_list");
?>
</body>
</html>
