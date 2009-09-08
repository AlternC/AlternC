<?php
/*
 $Id: sta2_add_raw.php,v 1.3 2004/09/07 17:09:57 anonymous Exp $
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

if (!$quota->cancreate("sta2")) {
	$error=_("You cannot add any new statistics, your quota is over.");
}

include_once("head.php");
?>
<h3><?php __("New Raw Statistics (apache)"); ?></h3>
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p>";
		include_once("foot.php");
		exit();
	}
?>
<form method="post" action="sta2_doadd_raw.php" id="main" name="main">
<table border="1" cellspacing="0" cellpadding="4">
<tr><th><input type="hidden" name="id" value="<?php echo $id ?>" />
        <label for="hostname"><?php __("Domain name"); ?></label></th><td>
	<select class="inl" name="hostname" id="hostname"><?php $sta2->select_host_list($hostname); ?></select>
</td></tr>
<tr><th><label for="folder"><?php __("Folder"); ?></label></th><td><input type="text" class="int" name="folder" id="folder" value="<?php echo $folder; ?>" size="20" maxlength="255" />
<script type="text/javascript">
<!--
  document.write("&nbsp;<input type=\"button\" name=\"bff\" onclick=\"browseforfolder('main.folder');\" value=\" ... \" class=\"inb\" />");
//  -->
</script>
</td></tr>
<tr><td colspan="2"><input type="submit" class="inb" name="submit" value="<?php __("Create those raw statistics"); ?>" /></td></tr>
</table>
</form>
<?php $mem->show_help("sta2_add"); ?>
<?php include_once("foot.php"); ?>