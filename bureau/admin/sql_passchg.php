<?php
/*
 $Id: sql_passchg.php,v 1.5 2006/01/12 01:10:48 anarcat Exp $
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
 Purpose of file: Change the MySQL password of a member
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$r=$mysql->get_dblist()) {
	$error=$err->errstr();
}

include("head.php");
?>
</head>
<body>
<h3><?php __("MySQL Databases"); ?></h3>
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p><p>&nbsp;</p>";
	}

echo "<p>"._("Enter the new password of your SQL database and click 'change the password' to change it")."</p>";
?>

<form method="post" action="sql_pass.php">
<table cellspacing="0" cellpadding="4">
		<tr class="lst2"><th><?php __("Username"); ?></th><td><code><?php echo $mem->user["login"]; ?></code></td></tr>
		<tr class="lst1"><th><label for="pass"><?php __("Password"); ?></label></th><td><code><input class="int" type="password" name="pass" id="pass" value="" /></code></td></tr>
		<tr class="lst2"><th><?php __("SQL Server"); ?></th><td><code><?php echo $mysql->server; ?></code></td></tr>
		<tr class="lst1"><th><?php __("Database"); ?></th><td><code><?php echo $r[0]["db"]; ?></code></td></tr>
	<tr><td><input type="submit" class="inb" name="submit" value="<?php __("Change the password"); ?>" /></td></tr>
</table>
</form>

</body>
</html>
