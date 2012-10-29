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
 Purpose of file: Main header of all html files
 ----------------------------------------------------------------------
*/
if (!$charset) $charset="UTF-8";
@header("Content-Type: text/html; charset=$charset");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang; ?>" lang="<?php echo $lang; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
<title><?php __("AlternC Control Panel"); ?></title>

<link rel="stylesheet" href="js/jquery_ui/css/smoothness/jquery-ui-1.8.23.custom.css" type="text/css" />
<link rel="stylesheet" href="styles/style.css" type="text/css" />
<!-- <link rel="stylesheet" href="styles/style-hw.css" type="text/css" /> -->
<link rel="stylesheet" href="styles/passwordStrengthMeter.css" type="text/css" />

<link rel="stylesheet" href="styles/style-empty.css" type="text/css" title="Default"/>
<link rel="alternate stylesheet" href="styles/style-hw.css" type="text/css" title="Halloween" />

<link rel="Shortcut Icon" href="favicon.ico" type="image/ico" />
<link rel="icon" href="favicon.ico" type="image/ico" />

<script src="js/alternc.js" type="text/javascript" ></script>
<script src="js/jquery.min.js" type="text/javascript"></script>
<script src="js/jquery_ui/js/jquery-ui-1.8.23.custom.min.js" type="text/javascript"></script>
<script src="js/passwordStrengthMeter.js" type="text/javascript"></script>
<?php
$lang_date_picker="js/jquery_ui/js/jquery.ui.datepicker-".substr($lang,0,2).".js";
if (file_exists($lang_date_picker)) 
  echo "<script src=\"$lang_date_picker\" type=\"text/javascript\"></script>";
?>
</head>
<body>
<?

if ($isinvited && isset($oldid) && !empty($oldid) && $oldid!=$cuid ) {
  echo "<div align=center><p class='error'>";
  __("Administrator session. you may <a href='adm_login.php'>return to your account</a> or <a href='adm_cancel.php'>cancel this feature</a>");
  echo "</p></div>";
}
?>
<div id="global">
<table>
<tr>
<td id="tdMenu"><div id="menu"><?php include_once("menu.php"); ?></div></td>
<td id="tdContent"><div id="content">
