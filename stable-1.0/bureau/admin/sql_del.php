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
include_once ("head.php");

if ($confirm=="y") {
  reset($_POST);
  while (list($key,$val)=each($_POST)) {
    if (substr($key,0,4)=="del_") {
      // Effacement de la base $val
      $r=$mysql->del_db(substr($key,4));
      if (!$r) {
	$error.=$err->errstr()."<br />";
      } else {
	$error.=sprintf(_("The database %s has been successfully deleted"),$mem->user["login"].(($val)?"_":"").$val)."<br />";
      }
    }
  }
  include("sql_list.php");
  exit();
}

$found=false;
foreach($_POST as $key=>$val) {
  if (substr($key,0,4)=="del_") {
    $found=true;
  }
}
if (!$found) {
  $error=_("Please check which databases you want to delete"); 
  include("sql_list.php");
  exit();
 }

?>
<h3><?php __("MySQL Databases"); ?></h3>
<hr id="topbar"/>
<br />
<p class="error"><?php __("WARNING"); ?></big><br /><?php __("Confirm the deletion of the following SQL databases"); ?><br />
<?php __("This will delete all the tables currently in those db."); ?></p>
<form method="post" action="sql_del.php" id="main">
<p>
<input type="hidden" name="confirm" value="y" />
<?php
reset($_POST);
while (list($key,$val)=each($_POST)) {
  if (substr($key,0,4)=="del_") {
    echo "<input type=\"hidden\" name=\"$key\" value=\"$val\" />".$mem->user["login"].(($val)?"_":"")."$val<br />\n";
  }
}

?>
<br />
<input type="submit" class="inb" name="sub" value="<?php __("Yes"); ?>" /> <input type="button" class="inb" name="non" value="<?php __("No"); ?>" onclick="history.back()" />
</p>
</form>
<?php include_once("foot.php"); ?>
