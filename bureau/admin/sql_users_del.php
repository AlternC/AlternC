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
 * Delete a MySQL user account
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */

require_once("../class/config.php");
$fields = array (
        "confirm"                => array ("post", "string", ""),
);
getFields($fields);

if (isset($confirm) && ($confirm=="y")) {
  reset($_POST);
  while (list($key,$val)=each($_POST)) {
    if (substr($key,0,4)=="del_") {
      // Effacement de la base $val
      if($mysql->del_user($val)) {
	$msg->raise("INFO", "mysql", __("The user '%s' has been successfully deleted", "alternc", true), $val);
      }
    }
  }
  include("sql_users_list.php");
  exit();
}

include_once("head.php");

?>
<h3><?php __("MySQL users"); ?></h3>
<hr id="topbar"/>
<br />
<p class="alert alert-warning"><?php __("WARNING"); ?></big><br /><?php __("Confirm the deletion of the following MySQL users"); ?><br />
</p>
<form method="post" action="sql_users_del.php" id="main">
  <?php csrf_get(); ?>
<p>
<input type="hidden" name="confirm" value="y" />
<ul>
<?php
reset($_POST);
while (list($key,$val)=each($_POST)) {
  if (substr($key,0,4)=="del_") {
      echo "<li><input type=\"hidden\" name=\"".ehe($key,false)."\" value=\"".ehe($val,false)."\" /><b>".ehe($val,false)."</b></li>\n";
  }
}

?>
</ul>
<input type="submit" class="inb ok" name="sub" value="<?php __("Yes, delete the MySQL user"); ?>" /> <input type="button" class="inb cancel" name="non" value="<?php __("No, don't delete the MySQL user"); ?>" onclick="history.back()" />
</p>
</form>
<?php include_once("foot.php"); ?>
