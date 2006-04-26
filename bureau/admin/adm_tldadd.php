<?php
/*
 $Id: adm_tldadd.php,v 1.3 2003/06/10 12:14:09 root Exp $
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
 Purpose of file: Manage allowed TLD on the server
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
	__("This page is restricted to authorized staff");
	exit();
}

include("head.php");
?>
</head>
<body>
<h3><?php __("Manage allowed domains (TLD)"); ?></h3>
<?php
	if ($error) {
	  echo "<p class=\"error\">$error</p>";
	}


?>
<h3><?php __("Add a new TLD"); ?></h3>
<p>
<?php __("Enter the new TLD (without the first dot) and choose what check should be done."); ?><br />
<small><?php __("Warning : only some final tld are known in the whois function of AlternC, please check m_domains.php accordingly."); ?></small>
</p>

<form method="post" action="adm_tlddoadd.php">

<table border="0" cellpadding="4" cellspacing="0">
<tr><th><label for="tld"><?php __("TLD"); ?></label></th><td><input type="text" id="tld" name="tld" class="int" value="<?php echo $tld; ?>" size="20" maxlength="64" /></td></tr>
<tr><th><label for="mode"><?php __("Allowed Mode"); ?></label></th><td><select name="mode" id="mode" class="inl">
	<?php $admin->selecttldmode($mode); ?>
</select></td></tr>
<tr><td colspan="2"><input type="submit" class="inb" value="<?php __("Add a new TLD"); ?>" /></td></tr>
</table>
</form>

</body>
</html>
