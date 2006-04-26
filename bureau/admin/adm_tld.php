<?php
/*
 $Id: adm_tld.php,v 1.4 2004/11/29 17:27:04 anonymous Exp $
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

if (is_array($sel)) {
	$error="";
	for($i=0;$i<count($sel);$i++) {
		if (!$admin->deltld($sel[$i])) {
			$error.=_("Some TLD cannot be deleted...")." : ".$sel[$i]."<br />";
		}
	}
	if (!$error) $error=_("The requested TLD has been deleted");
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

$c=$admin->listtld();

?>
<p>
<?php __("Here is the list of the TLD allowed on this server. Each TLD can be allowed or denied after some checks (whois, ns, domain exists...)"); ?>
</p>

<form method="post" action="adm_tld.php">
<table border="0" cellpadding="4" cellspacing="0">
<tr><th><?php __("Action"); ?></th><th><?php __("TLD"); ?></th><th><?php __("Allowed Mode"); ?></th></tr>
<?php
$col=1;
for($i=0;$i<count($c);$i++) {
 $col=3-$col;
?>

<tr class="lst<?php echo $col; ?>">
<td><input id="sel<?php echo $i; ?>" type="checkbox" name="sel[]" class="inc" value="<?php echo $c[$i]["tld"]; ?>" />&nbsp;<a href="adm_tldedit.php?tld=<?php echo urlencode($c[$i]["tld"]); ?>"><?php __("Edit"); ?></a></td>
<td><label for="sel<?php echo $i; ?>"><?php echo $c[$i]["tld"]; ?></label></td>
<td><?php __($admin->tldmode[$c[$i]["mode"]]); ?></td></tr>

<?php
}
?>
<tr><td colspan="3"><input type="submit" class="inb" value="<?php __("Delete the checked TLD"); ?>" /></td></tr>
</table>
</form>
<a href="adm_tldadd.php"><?php __("Add a new TLD"); ?></a>
</body>
</html>
