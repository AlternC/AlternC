<?php
/*
----------------------------------------------------------------------
AlternC - Web Hosting System
Copyright (C) 2000-2012 by the AlternC Development Team.
https://alternc.org/
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
Purpose of file: Show a form to edit a member
----------------------------------------------------------------------
*/
require_once("../class/config.php");

include("head.php");

if (!$admin->enabled) {
    $msg->raise("ERROR", "admin", _("This page is restricted to authorized staff"));
    echo $msg->msg_html_all();
    exit();
}

?>
<h3><?php __("List of the databases servers"); ?></h3>
<hr/>
<?php

$lst_db_servers = $mysql->list_db_servers();

echo "<p>";
__("Here the list of the available databases servers.");
echo "</p>";

?>

<table class="tlist">
  <tr>
    <th><?php __("ID"); ?></th>
    <th><?php __("Name"); ?></th>
    <th><?php __("Hostname"); ?></th>
    <th><?php __("Login"); ?></th>
    <th><?php __("Client"); ?></th>
    <th><?php __("Users"); ?></th>
  </tr>
<?php 
foreach ( $lst_db_servers as $l) { 
  echo "<tr class='lst'>"; ?>
    <td><?php echo $l['id']; ?></td>
    <td><?php echo $l['name']; ?></td>
    <td><?php echo $l['host']; ?></td>
    <td><?php echo $l['login']; ?></td>
    <td><?php echo $l['client']; ?></td>
    <td><?php echo $l['nb_users']; ?></td>
  </tr>
<?php } //foreach lst_db_servers ?>
</table>
      
<?php 
echo "<p>";
__("To add a database server, do an INSERT into the db_servers table");
echo "</p>";
echo "<p>";
__("To update the list of the server on the PhpMyAdmin login page, launch alternc.install");
echo "</p>";

include_once('foot.php');
?>
