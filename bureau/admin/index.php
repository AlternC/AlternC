<?php
/*
 $Id: index.php,v 1.15 2005/05/20 02:47:18 anarcat Exp $
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
 Purpose of file: Main index : show the login page
 ----------------------------------------------------------------------
*/

require_once("../class/config_nochk.php");

if (!$mem->del_session()) {
	// No need to draw an error message ...
	//$error=$err->errstr();
}

$H=getenv("HTTP_HOST");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<title>AlternC Desktop</title>
<link rel="stylesheet" href="styles/style.css" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
</head>
<body onload="document.forms['fmain'].username.focus();">
<h1><?php echo _("Administration of")." ".$H ?></h1>
<?php if ($error) echo "<font color=red>$error</font>"; ?>
<p><?php echo _("Enter your username and password to connect to the virtual desktop")." [ ".$H." ]" ?></p>
<form action="login.php" method="post" target="_top" id="fmain" name="fmain">
<div class="dlogin">
<table border="1" cellspacing="0" cellpadding="4" width="300" class="login">
<tr><th align="right"><label for="username"><?php echo _("Username"); ?></label></th><td><input type="text" class="int" name="username" id="username" value="" maxlength="128" size="20" /></td></tr>
<tr><th align="right"><label for="password"><?php echo _("Password"); ?></label></th><td><input type="password" class="int" name="password" id="password" value="" maxlength="128" size="20" /></td></tr>
<tr><td colspan="2" align="center"><input type="submit" class="inb" name="submit" value="<?php __("Enter"); ?>" /></td></tr>
<tr><td colspan="2" align="right"><label><input type="checkbox" class="inc" id="restrictip" name="restrictip" value="1" checked="checked"><?php __("Restrict this session to my ip address"); ?></label></td></tr>
</table>
</div>
</form>
<p>&nbsp;</p>
<table width="100%" style="border: 0">
<tr><td style="text-align: left">
<?php __("You must accept the session cookie to log-in"); ?>
<br />
<?php __("You can use a different language: "); ?>
<?php 
foreach($locales as $l) {
?>
<a href="?setlang=<?php echo $l; ?>"><?php __($l); ?></a>&nbsp;
<?php } ?>
</td>
<td style="text-align: right">
<p> <a href="http://alternc.org"><img src="alternc.png" width="120" height="82" border="0" alt="<?php __("AlternC, Opensource hosting control panel"); ?>" title="<?php __("AlternC, Opensource hosting control panel"); ?>" /></a></p>
</td>
</tr>
</table>
</body>
</html>
