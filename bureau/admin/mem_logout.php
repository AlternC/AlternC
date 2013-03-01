<?php
/*
 $Id: mem_logout.php,v 1.3 2003/08/13 23:52:24 root Exp $
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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$mem->del_session();

if (!$charset) $charset="UTF-8";
@header("Content-Type: text/html; charset=$charset");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<title><?php __("Disconnected"); ?></title>
<link rel="stylesheet" href="styles/style.css" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
</head>
<body style="margin: 20px;" onLoad="setTimeout('redirect_panel()', 1500)">
  <div id="global">

    <div id="content" style="width:1000px;">
      <h3 style="text-align: center"><?php __("Disconnected"); ?></h3>

      <?php __("You have been logged out of your administration desktop."); ?><br />
      <p><a href="index.php"><?php __("Click here to log in"); ?></a></p>
      <p>&nbsp;</p>
    </div>
  </div>
<script type="text/javascript">
function redirect_panel() {
  window.location = "index.php"
}
</script>
</body>
</html>
