<?php
/*
 $Id: sql_del.php,v 1.3 2003/06/10 07:20:29 root Exp $
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
 Purpose of file: Delete a mysql user database
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
$fields = array (
        "confirm"                => array ("post", "string", ""),
);
getFields($fields);
if(!isset($error)){
	$error="";
}
if (isset($confirm) && ($confirm=="y")) {
  reset($_POST);
  while (list($key,$val)=each($_POST)) {
    if (substr($key,0,4)=="del_") {
      // Effacement de la base $val
      $r=$mysql->del_user($val);
      if (!$r) {
	$error.=$err->errstr()."<br />";
      } else {
	$error.=sprintf(_("The user %s has been successfully deleted"),$val)."<br />";
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
<p>
<input type="hidden" name="confirm" value="y" />
<?php
reset($_POST);
while (list($key,$val)=each($_POST)) {
  if (substr($key,0,4)=="del_") {
    echo "<input type=\"hidden\" name=\"$key\" value=\"$val\" />".$val."<br />\n";
  }
}

?>
<br />
<input type="submit" class="inb" name="sub" value="<?php __("Yes"); ?>" /> - <input type="button" class="inb" name="non" value="<?php __("No"); ?>" onclick="history.back()" />
</p>
</form>
<?php include_once("foot.php"); ?>
