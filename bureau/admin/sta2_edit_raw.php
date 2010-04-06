<?php
/*
 $Id: sta2_edit_raw.php,v 1.2 2004/08/27 18:06:13 anonymous Exp $
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

include_once("head.php");

if (!$id) {
	$error=_("No Statistics selected!");
} else {
	$r=$sta2->get_stats_details_raw($id);
	if (!$r) {
		$error=$err->errstr();
	}
}

?>
<h3><?php __("Change the Raw Statistics"); ?></h3>
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p>";
		include_once("foot.php");
		exit();
	}
?>
<form method="post" action="sta2_doedit_raw.php" id="main" name="main">
<table border="1" cellspacing="0" cellpadding="4">
<tr><th><input type="hidden" name="id" value="<?php echo $id ?>" />
        <?php __("Domain name:"); ?></th><td>
	<?php echo $r["hostname"]; ?>
</td></tr>
<tr><th><label for="dir"><?php __("Folder where we will put the log file:"); ?></label></th><td><input type="text" class="int" name="dir" id="dir" value="<?php echo $r["folder"]; ?>" size="20" maxlength="255" />
<script type="text/javascript">
<!--
  document.write("&nbsp;<input type=\"button\" name=\"bff\" class=\"bff\" onclick=\"browseforfolder('main.dir');\" value=\" <?php __("Choose a folder..."); ?> \" />");
//  -->
</script>
</td></tr>
<tr><td colspan="2"><input type="submit" class="inb" name="submit" value="<?php __("Change those Raw Statistics."); ?>" /></td></tr>
</table>
</form>
<?php include_once("foot.php"); ?>