<?php
/*
 $Id: ftp_add.php,v 1.5 2006/01/12 01:10:48 anarcat Exp $
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
 Purpose of file: Ask the required values to add a ftp account
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

if (!$quota->cancreate("ftp")) {
	$error=_("You cannot add any new ftp account, your quota is over.");
	$fatal=1;
}

?>
<h3><?php __("Create a new ftp account"); ?></h3>
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p>";
		if ($fatal) {
		  include_once("foot.php");
		  exit();
		}
	}
?>
<form method="post" action="ftp_doadd.php" name="main" id="main">
<table><thead><?php __("Create a new ftp account"); ?></thead>
<tr><th><input type="hidden" name="id" value="<?php echo $id ?>" />
<label for="login"><?php __("Username"); ?></label></th><td>
	<select class="inl" name="prefixe"><?php $ftp->select_prefix_list($prefixe); ?></select>&nbsp;<b>_</b>&nbsp;<input type="text" class="int" name="login" id="login" value="<?php echo $login; ?>" size="20" maxlength="64" />
</td></tr>
<tr><th><label for="pass"><?php __("Password"); ?></label></th><td><input type="password" class="int" name="pass" id="pass" value="<?php echo $pass; ?>" size="20" maxlength="64" /></td></tr>
<tr><th><label for="passconf"><?php __("Confirm password"); ?></label></th><td><input type="password" class="int" name="passconf" id="passconf" value="<?php echo $passconf; ?>" size="20" maxlength="64" /></td></tr>
<tr><th><label for="dir"><?php __("Folder"); ?></label></th><td><input type="text" class="int" name="dir" id="dir" value="<?php echo $dir; ?>" size="20" maxlength="255" />
<script type="text/javascript">
<!--
  document.write("&nbsp;<input type=\"button\" name=\"bff\" onclick=\"browseforfolder('main.dir');\" value=\" ... \" class=\"inb\">");
//  -->
</script>
</td></tr>
<tr><td colspan="2"><input type="submit" class="inb" name="submit" value="<?php __("Create this new FTP account."); ?>" /></td></tr>
</table>
</form>
<?php $mem->show_help("ftp_add"); ?>
<script type="text/javascript">
document.forms['main'].login.focus();
</script>
<?php include_once("foot.php"); ?>
