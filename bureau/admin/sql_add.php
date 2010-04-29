<?php
/*
 $Id: sql_add.php,v 1.5 2004/05/19 14:23:06 benjamin Exp $
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

if (!$quota->cancreate("mysql")) {
	$error=_("err_mysql_1");
	$fatal=1;
}

?>
<h3><?php __("Create a new MySQL database"); ?></h3>
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p>";
		if ($fatal) {
			include_once("foot.php");
			exit();
		}
	}
?>
<form method="post" action="sql_doadd.php" id="main" name="main">
<table class="tedit">
<tr><th><label for="dbn"><?php __("MySQL Database"); ?></label></th><td>
	<span class="int" id="dbnpfx"><?php echo $mem->user["login"]; ?>_</span><input type="text" class="int" name="dbn" id="dbn" value="<?php echo $dbn; ?>" size="20" maxlength="30" />
</td></tr>
</table>
<br />
<input type="submit" class="inb" name="submit" value="<?php __("Create this new MySQL database."); ?>" />
</form>

<script type="text/javascript">
  document.forms['main'].dbn.focus();
</script>

<?php include_once("foot.php"); ?>
