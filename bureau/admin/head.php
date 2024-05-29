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
 * main HEADER of all HTML page of the panel
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

if (!isset($charset) || ! $charset) $charset="UTF-8";
@header("Content-Type: text/html; charset=$charset");

require_once("html-head.php");
?>
<body>
<?php

if ($isinvited && isset($oldid) && !empty($oldid) && $oldid!=$cuid ) {
  echo "<div align=center><p class='alert alert-warning'>";
  __("Administrator session. you may <a href='adm_login.php'>return to your account</a> or <a href='adm_cancel.php'>cancel this feature</a>.");
  if ($oldid == 2000) echo ' '.__("You can also <a href='adm_update_domains.php'>apply changes</a>.", "alternc", true); // Yes, hardcoded uid. We will rewrite permissions another day
  echo "</p></div>";
}
if ( panel_islocked() ) {
  echo "<div align=center><p class='alert alert-warning'>";
    __("Panel is locked! No one can login!");
  echo "</p></div>";
}
?>
<div id="global" class="clearfix">
<div id="menu"><?php include_once("menu.php"); ?></div>
<div id="content">
