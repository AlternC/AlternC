<?php
/*
 $Id: hta_add.php,v 1.3 2003/06/10 13:16:11 root Exp $
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
 Original Author of file: Franck Missoum
 Purpose of file: Ask the required values to protect a folder
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

include("head.php");
?>

</head>
<body>
<h3><?php __("Protect a folder"); ?></h3>
<p>
<?php __("The folder must exists."); ?>
</p>
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p></body></html>";
		exit;
	}
?>

<form method="post" action="hta_doadd.php" name="main">
<table border="1" cellspacing="0" cellpadding="4">
<tr>
	<td><label for="dir"><?php __("Folder"); ?></label></td>
	<td><input type="text" class="int" name="dir" id="dir" value="<?php echo $value ?>" size="20" maxlength="64" />
<script type="text/javascript">
<!--
  document.write("&nbsp;<input type=\"button\" name=\"bff\" onclick=\"browseforfolder('main.dir');\" value=\" ... \" class=\"inb\">");
//  -->
</script></td>
</tr>
<tr><td colspan="2"><input type="submit" class="inb" value="<?php __("Protect this folder"); ?>" /></td></tr>
</table>
</form>

</body>
</html>

