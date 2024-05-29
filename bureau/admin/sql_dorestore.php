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
 * restore (do it) a MySQL database
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */

require_once("../class/config.php");

include_once("head.php");
$fields = array (
	"id"    		=> array ("post", "string", ""),
	"restfile"    		=> array ("post", "string", ""),
);
getFields($fields);
?>

<h3><?php __("MySQL Databases"); ?></h3>
<hr id="topbar"/>
<br />

<?php

$r=$mysql->get_mysql_details($id);

if (! $r["enabled"]) { 
  echo "<p class=\"alert alert-danger\">".__("You currently have no database defined", "alternc", true)."</p>";
  include_once("foot.php");
  die();
}
?>

<h3><?php __("Restore a SQL backup"); ?></h3>

<p>
<?php
if ($mysql->restore($restfile,true,$id))  {
  $msg->raise("INFO", "mysql", __("Your database have been restored, check out the previous text for error messages.", "alternc", true));
}

echo $msg->msg_html_all();
?>
</p>
<?php include_once("foot.php"); ?>
