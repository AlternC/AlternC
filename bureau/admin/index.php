<?php

/*
 $Id: index.php,v 1.11 2004/05/24 17:30:22 anonymous Exp $
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

if (!isset($restrictip)) {
  $restrictip=1;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<title>AlternC Desktop</title>
<link rel="stylesheet" href="styles/style.css" type="text/css" />
<script type="text/javascript" src="js/alternc.js"></script>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
</head>
<body>

<div id="content" style="position: absolute; left: 50%; top: 20px; margin-left: -400px">

    <p id="logo">  <img src="logo.png" border="0" alt="<?php __("Web Hosting Control Panel"); ?>" title="<?php __("Web Hosting Control Panel"); ?>" /></a>
    </p>
<p>&nbsp;</p>
<?php if (isset($error) && $error) echo "<font color=red>$error</font>"; ?>
<?php
/*
if (!$_SERVER[HTTPS]) {
  echo "<h4>ATTENTION : vous allez accéder à votre panel en mode *non sécurisé*<br/>
<a href=\"https://".$_SERVER["HTTP_HOST"]."/admin/\">Cliquez ici pour passer en mode sécurisé</a></h4>"; 
}
*/
?>
<div style="position: relative; left: 100px">
<table><tr><td style="width: 320px">
<?php __("To connect to the hosting control panel, enter your AlternC's login and password in the following form and click 'Enter'"); ?>
</td><td>
<form action="login.php" method="post" target="_top">
<table border="0" style="border: 1px solid #202020;" cellspacing="0" cellpadding="3" width="300px" >
<tr><th colspan="2" align="center"><?php __("AlternC access"); ?></th></tr>
<tr><th align="right"><label for="username"><?php echo _("Username"); ?></label></th><td><input type="text" class="int" name="username" id="username" value="" maxlength="128" size="15" /></td></tr>
<tr><th align="right"><label for="password"><?php echo _("Password"); ?></label></th><td><input type="password" class="int" name="password" id="password" value="" maxlength="128" size="15" /></td></tr>
<tr><td colspan="2" align="center"><input type="submit" class="inb" name="submit" value="<?php __("Enter"); ?>" /><input type="hidden" id="restrictip" name="restrictip" value="1" /></td></tr>
</table>
</form>

</td></tr>
<tr><td>

<?php __("If you want to read your mail, enter your Email address and password in the following form and click 'Enter'"); ?>

</td><td>

<form action="/webmail/src/redirect.php" method="post">
<table border="0" style="border: 1px solid #202020;" cellspacing="0" cellpadding="3" width="300px" >
<tr><th colspan="2" align="center"><?php __("Webmail Access"); ?></th></tr>
<tr><th align="right"><label for="login_username"><?php __("Email Address"); ?></label></th><td><input type="text" class="int" name="login_username" id="login_username" value="" maxlength="128" size="15" /></td></tr>

<tr><th align="right"><label for="secretkey"><?php __("Password"); ?></label></th><td><input type="password" class="int" name="secretkey" id="secretkey" value="" maxlength="128" size="15" /></td></tr>
<tr><td colspan="2" align="center"><input type="submit" class="inb" name="submit" value="<?php __("Enter"); ?>" /> </td></tr>
</table>
</form>
</td></tr>

</table>

</div>

<table width="800px" style="border: 0">
<tr><td style="text-align: left; font-size: 10px">
<?php __("You must accept the session cookie to log-in"); ?>
<br />
<?php echo "If you want to use a different language, choose it in the list below"; ?>
<br />
	    <?php 
		foreach($locales as $l) {
	    ?>
	    <a href="?setlang=<?php echo $l; ?>"><?php __($l); ?></a>
	    <?php } ?>
<br />
<?php
 $mem->show_help("login",true); 
?>
</td>
<td>
<p>
<a href="http://www.alternc.org/"><img src="alternc.jpg" width="128" height="32" alt="powered by AlternC" /></a>
</p>
</td>
</tr>
</table>


</div>

</body>
</html>
