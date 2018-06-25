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

if (!defined("ALTERNC_PANEL")) exit(); // must be included ;) 

/**
 * main HEADER of all HTML page of the panel
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang; ?>" lang="<?php echo $lang; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
<title><?php __("AlternC Control Panel"); ?></title>

<link rel="stylesheet" href="/javascript/jquery-ui-themes/redmond/jquery-ui.min.css" type="text/css" />
<link rel="stylesheet" href="styles/style.css" type="text/css" />
<link rel="stylesheet" href="styles/solid.css" type="text/css" /><!-- fontawesome solid font -->
<link rel="stylesheet" href="styles/fontawesome.css" type="text/css" />
<?php
if (file_exists("styles/style-custom.css") ) {
  echo '<link rel="stylesheet" href="styles/style-custom.css" type="text/css" />';
}
if (count($addhead['css'])) {
    foreach($addhead['css'] as $css) echo $css."\n";
}
$favicon = variable_get('favicon', 'favicon.ico' ,'You can specify a favicon, for example /images/my_logo.ico', array('desc'=>'URL','type'=>'string'));
?>

<link rel="Shortcut Icon" href="<?php echo $favicon;?>" type="image/ico" />
<link rel="icon" href="<?php echo $favicon;?>" type="image/ico" />

<script src="js/alternc.js" type="text/javascript" ></script>
<script src="/javascript/jquery/jquery.min.js" type="text/javascript"></script>
<script src="/javascript/jquery-ui/jquery-ui.min.js" type="text/javascript"></script>
<script src="/javascript/jquery-tablesorter/jquery.tablesorter.min.js" type="text/javascript"></script>
<?php
if (count($addhead['js'])) {
    foreach($addhead['js'] as $js) echo $js."\n";
}
?>
</head>
