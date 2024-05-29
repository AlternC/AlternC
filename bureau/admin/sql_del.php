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
 * Delete MySQL databases for the account
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */

require_once("../class/config.php");
include_once ("head.php");

$fields = array (
        "confirm"                => array ("post", "string", ""),
);
getFields($fields);

// DO IT 
if ($confirm=="y" ) {
  reset($_POST);
  while (list($key,$val)=each($_POST)) {
    if (substr($key,0,4)=="del_") {
      // Effacement de la base $val
      $r=$mysql->del_db(substr($key,4));
      if ($r) {
	$msg->raise("INFO", "mysql", __("The database '%s' has been successfully deleted", "alternc", true), $val);
      }
    }
  }
  include("sql_list.php");
  exit();
}

// Confirm form 
$found=false;
foreach($_POST as $key=>$val) {
  if (substr($key,0,4)=="del_") {
    $found=true;
  }
}
if (!$found) {
  $msg->raise("ALERT", "mysql", __("Please check which databases you want to delete", "alternc", true));
  include("sql_list.php");
  exit();
 }

?>
<h3><?php __("MySQL Databases"); ?></h3>
<hr id="topbar"/>
<br />
<p class="alert alert-warning"><?php __("WARNING"); ?></big><br /><?php __("Confirm the deletion of the following SQL databases"); ?><br />
<?php __("This will delete all the tables currently in those db."); ?></p>
<form method="post" action="sql_del.php" id="main">
  <?php csrf_get(); ?>
<p>
<input type="hidden" name="confirm" value="y" />
<?php
reset($_POST);
while (list($key,$val)=each($_POST)) {
  if (substr($key,0,4)=="del_") {
      echo "<input type=\"hidden\" name=\"".ehe($key,false)."\" value=\"".ehe($val,false)."\" /><ul><li><b>".ehe($val,false)."</b></li></ul>\n";
  }
}

?>
<br />
<input type="submit" class="inb ok" name="sub" value="<?php __("Yes, delete the database"); ?>" /> <input type="button" class="inb cancel" name="non" value="<?php __("No, don't delete the database"); ?>" onclick="history.back()" />
</p>
</form>
<?php include_once("foot.php"); ?>
