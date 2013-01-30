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
include_once("head.php");

$fields = array (
	"domain"    => array ("request", "string", ""),
);
getFields($fields);

$noftp=false;
if (!$r=$ftp->get_list($domain)) {
	$noftp=true;
	$error=$err->errstr();
}

?>
<h3><?php __("FTP accounts list"); ?></h3>
<hr id="topbar"/>
<br />
 
<?php
if (isset($error) && $error && !$noftp) {
?>
<p class="error"><?php echo $error ?></p>
<?php } ?>

<?php if ($quota->cancreate("ftp")) { ?>
<p>
   <span class="inb"><a href="ftp_edit.php?create=1"><?php __("Create a new ftp account"); ?></a></span> 
</p>
<?php  	} ?>

<?php
	if ($noftp) {
?>
	<?php $mem->show_help("ftp_list_no"); ?>
<?php
 include_once("foot.php"); 
    } 
?>

<form method="post" action="ftp_del.php">
<table class="tlist">
  <tr><th colspan="2"> </th><th><?php __("Username"); ?></th><th><?php __("Folder"); ?></th></tr>
<?php
reset($r);
$col=1;
while (list($key,$val)=each($r))
	{
	$col=3-$col;
?>
	<tr class="lst<?php echo $col; ?>">
		<td align="center"><input type="checkbox" class="inc" id="del_<?php echo $val["id"]; ?>" name="del_<?php echo $val["id"]; ?>" value="<?php echo $val["id"]; ?>" /></td>
<td><div class="ina"><a href="ftp_edit.php?id=<?php echo $val["id"] ?>"><img src="images/edit.png" alt="<?php __("Edit"); ?>" /><?php __("Edit"); ?></a></div></td>

		<td><label for="del_<?php echo $val["id"]; ?>"><?php echo $val["login"] ?></label>
                  <input type='hidden' name='names[<?php echo $val['id'];?>]' value='<?php echo $val["login"] ?>' >
                </td>
		<td><code><?php echo substr(str_replace(ALTERNC_HTML,'',$val["dir"]),strlen($mem->user['login'])+3) ?></code></td>
	</tr>
<?php
	}
?>
</table>
<p><input type="submit" name="submit" class="inb" value="<?php __("Delete checked accounts"); ?>" /></p>
</form>

<?php
$mem->show_help("ftp_list");
?>
<?php include_once("foot.php"); ?>
