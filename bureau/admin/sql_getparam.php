<?php
/*
 $Id: sql_getparam.php,v 1.4 2005/05/27 20:10:18 arnaud-lb Exp $
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
 Purpose of file: Return the current SQL settings
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"dbname"    => array ("request", "string", ""),
);
getFields($fields);
if (!$r=$mysql->get_dblist()) {
	$error=$err->errstr();
}

$r=$mysql->get_defaultsparam($dbname);
if (!$r) {
	$error=$err->errstr();
}

?>
<h3><?php __("MySQL Databases"); ?></h3>
<hr id="topbar"/>
<br />
<?php
	if (isset($error) && $error) {
		echo "<p class=\"alert alert-danger\">$error</p><p>&nbsp;</p>";
        include_once("foot.php"); 
        exit();
    }
?>
<p><?php __("Your current connection settings are"); ?> : </p>
<table class="tedit">
        <tr>
	<th colspan="2" style='text-align:center;'><?php echo '<h1>'.$mysql->dbus->HumanHostname.'</h1>'; ?></th>
        </tr>
	<tr>
		<th><?php __("Mysql Server"); ?></th>
		<td><code><?php echo $mysql->dbus->Host; ?></code></td>
	</tr>
	<tr>
		<th><?php __("Database"); ?></th>
<td><code><?php ehe($dbname); ?></code></td>
	</tr>
<?php
if(isset($r['user'])){
?>
	<tr>
		<th><?php __("Login"); ?></th>
		<td><code><?php echo $r['user']; ?></code></td>
	</tr>
	<tr>
		<th><?php __("Password"); ?></th>
		<td><code><?php echo $r['password']; ?></code></td>
	</tr>
	<tr>
		<td colspan="2" align="center">
                  <a href="/alternc-sql/" target="_blank"><?php __("Web interface PhpMyAdmin"); ?></a>
                  <p>
                  <code>http://<?php echo $L_FQDN; ?>/alternc-sql/</code>
                  </p>
                </td>
	</tr>
<?php
}
?>
</table>
<?php
if(!isset($r['user'])){
	echo "<p class=\"alert alert-warning\">";__("You changed the MySQL User base configuration. Please refer to your configuration");echo"</p><p>&nbsp;</p>";
}
?>
<p><span class="ina back"><a href="sql_list.php"><?php __("Back to the MySQL database list"); ?></a></span></p>


<?php
 include_once("foot.php"); ?>

