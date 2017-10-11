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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang; ?>" lang="<?php echo $lang; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
<title><?php __("AlternC Control Panel"); ?></title>

<link rel="stylesheet" href="js/jquery_ui/css/redmond/jquery-ui-1.10.3.custom.min.css" type="text/css" />
<link rel="stylesheet" href="styles/style.css" type="text/css" />
<?php
if (file_exists("styles/style-custom.css") ) {
  echo '<link rel="stylesheet" href="styles/style-custom.css" type="text/css" />';
}

$favicon = variable_get('favicon', 'favicon.ico' ,'You can specify a favicon, for example /images/my_logo.ico', array('desc'=>'URL','type'=>'string'));

?>

<link rel="stylesheet" href="styles/style-empty.css" type="text/css" title="Default - Desktop TNG"/>
<link rel="alternate stylesheet" href="styles/style-bluedesktop10.css" type="text/css" title="Blue Desktop 1.0" />
<link rel="alternate stylesheet" href="styles/style-hw.css" type="text/css" title="Halloween" />

<link rel="Shortcut Icon" href="<?php echo $favicon;?>" type="image/ico" />
<link rel="icon" href="<?php echo $favicon;?>" type="image/ico" />

<script src="js/alternc.js" type="text/javascript" ></script>
<script src="js/jquery.min_embedded.js" type="text/javascript"></script>
<script src="js/jquery_ui/js/jquery-ui-1.10.3.custom.min.js" type="text/javascript"></script>

<script src="js/jquery.tablesorter.min.js" type="text/javascript"></script>

<link href="prettify/prettify.css" type="text/css" rel="stylesheet" />
<script src="prettify/prettify.js" type="text/javascript"></script>

</head>
<body onload="prettyPrint()">
<?php

if ($isinvited && isset($oldid) && !empty($oldid) && $oldid!=$cuid ) {
  echo "<div align=center><p class='alert alert-warning'>";
  __("Administrator session. you may <a href='adm_login.php'>return to your account</a> or <a href='adm_cancel.php'>cancel this feature</a>.");
  if ($oldid == 2000) echo ' '._("You can also <a href='adm_update_domains.php'>apply changes</a>."); // Yes, hardcoded uid. We will rewrite permissions another day
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
