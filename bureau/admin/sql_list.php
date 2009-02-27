<?php
/*
 $Id: sql_list.php,v 1.8 2006/02/16 16:26:28 benjamin Exp $
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
 Purpose of file: Manage the MySQL database of a member
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");


$r=$mysql->get_dblist();


include("head.php");
?>
</head>
<body>
<h3><?php __("MySQL Databases"); ?></h3>
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p><p>&nbsp;</p>";
	}

if ($r) {

echo "<p>"._("help_sql_list_ok")."</p>";
?>

<form method="post" action="sql_del.php">
<table cellspacing="0" cellpadding="4">
   <tr><th>&nbsp;</th><th><?php __("Database"); ?></th><th><?php __("Backup"); ?></th><th><?php __("Restore"); ?></th><th><?php __("Size"); ?></th></tr>

<?php
$col=1;
for($i=0;$i<count($r);$i++) {
  $val=$r[$i];
  $val['size'] = $mysql->get_db_size($val['db']);
 $col=3-$col;
?>
	<tr  class="lst<?php echo $col; ?>">
		<td align="center"><input type="checkbox" class="inc" id="del_<?php echo $val["name"]; ?>" name="del_<?php echo $val["name"]; ?>" value="<?php echo $val["name"]; ?>" /></td>
	   <td><label for="del_<?php echo $val["name"]; ?>"><?php echo $val["db"]; ?></label></td>
		<td><a href="sql_bck.php?id=<?php echo $val["name"] ?>"><?php __("Backup"); ?></a></td>
		<td><a href="sql_restore.php?id=<?php echo $val["name"] ?>"><?php __("Restore"); ?></a></td>
		<td><code><?php echo format_size($val["size"]); ?></code></td>
	</tr>
<?php


 }
?>
<tr><td colspan="5">
   <input type="submit" name="sub" value="<?php __("Delete the checked databases"); ?>" class="inb" />
</td></tr>
</table>
</form>

<p>&nbsp;</p>
<p>
<?php if ($quota->cancreate("mysql")) { ?>
 <a href="sql_add.php"><?php __("Create a new database"); ?></a><br /><br />
<?php } ?>
<a href="sql_passchg.php"><?php __("Change the SQL password"); ?></a><br /><br />
<a href="sql_getparam.php"><?php __("Get the current SQL parameters"); ?></a><br /><br />

</p>

<?php
	} else {
  echo "<p>"._("help_sql_list_no")."</p>";
 
?>
<form method="post" action="sql_addmain.php">
<table cellspacing="0" cellpadding="4">
		<tr  class="lst2"><th><?php __("Username"); ?></th><td><code><?php echo $mem->user["login"]; ?></code></td></tr>
		<tr  class="lst1"><th><label for="pass"><?php __("Password"); ?></label></th><td><code><input class="int" type="password" name="pass" id="pass" value="" /></code></td></tr>
		<tr  class="lst2"><th><?php __("SQL Server"); ?></th><td><code><?php echo $mysql->server; ?></code></td></tr>
		<tr  class="lst1"><th><?php __("Database"); ?></th><td><code><?php echo $mem->user["login"]; ?></code></td></tr>
	<tr><td><input type="submit" class="inb" name="submit" value="<?php __("Create my main database"); ?>" /></td></tr>
</table>
</form>
<?php
	}
?>

</body>
</html>
