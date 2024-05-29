<?php
/*
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
*/

/**
 * Show the settings (may be plural) available 
 * to access a MySQL database for an account
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */

require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"dbname"    => array ("request", "string", ""),
);
getFields($fields);
if (!$res=$mysql->get_dblist()) {
	$error=$err->errstr();
}

$res=$mysql->get_defaultsparam($dbname);

?>
<h3><?php printf(__("MySQL settings for database '%s'", "alternc", true),$dbname); ?></h3>
<hr id="topbar"/>
<br />
<?php
echo $msg->msg_html_all();

if ($msg->has_msgs("ERROR")) {
    include_once("foot.php"); 
    exit();
}
?>
<p><?php __("Your current connection settings are"); ?> : </p>

<?php
$i = 0;
foreach ($res as $r) { 
	$i++;
?>
<table class="tedit">
        <tr>
	<th colspan="2" style='text-align:center;'><?php echo '<h1>'.__("Database Settings", "alternc", true).'</h1>'; ?></th>
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
    <th><?php __("User Rights"); ?></th>
<?php

// We test the  'Rights' value to know if this user have all or only specific rights.
if ($r["Rights"] == 'All') {
	$rights = __("All permissions", "alternc", true);
} else {
	$rights = "<span style='color:orange;'>".__("Specific permissions", "alternc", true)."</span>";
}
?>
<td>
<?php echo $rights; ?>
&nbsp;
<a class="inb permissions" href="sql_users_rights.php?id=<?php echo $r["user"] ?>"><?php __("Manage the rights"); ?></a>
</td>
	</tr>
<?php
}
?>
</table>

<p>
   <a class="inb settings" href="/sql_pma_sso.php?db=<?php echo $dbname; ?>" target="_blank"><?php __("Access PhpMyAdmin interface"); ?></a>
</p>
<p>


<?php
if(!isset($r['user'])){
	echo "<p class=\"alert alert-warning\">";__("You changed the MySQL User base configuration. Please refer to your configuration");echo"</p><p>&nbsp;</p>";
}
?>
<br>
<?php
} // end foreach 
?>
<p><span class="ina back"><a href="sql_list.php"><?php __("Back to the MySQL database list"); ?></a></span></p>


<?php
 include_once("foot.php"); 
?>

