<?php
/*
 $Id: adm_doms.php,v 1.1 2003/09/20 19:41:06 root Exp $
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

include_once ("head.php");

?>
<h3><?php __("Manage installed domains"); ?></h3>
<?php
	if ($error) {
	  echo "<p class=\"error\">$error</p>";
	}

// List the domains. If the first parameter is true, also check their DNS & other IPs actual parameters.
// If the second parameter is true, check the domains whatever the dis cache is.
$forcecheck=($_REQUEST["force"]!="");
$c=$admin->dom_list(true,$forcecheck);

?>
<p>
<?php __("Here is the list of the domains installed on this server. You can remove a domain if it does not exist or does not point to our server anymore. You can also set the 'Lock' flag on a domain so that the user will not be able to change any DNS parameter or delete this domain from his account."); ?>
</p>
<p>
<?php __("The domain OK column are green when the domain exists in the worldwide registry and has a proper NS,MX and IP depending on its configuration. It is red if we have serious doubts about its NS, MX or IP configuration. Contact the user of this domain or a system administrator."); ?>
</p>
<p>
<?php __("If you want to force the check of NS, MX, IP on domains, click the link"); ?> <a href="adm_doms.php?force=1"><?php __("Show domain list with refreshed checked NS, MX, IP information"); ?></a>
</p>
<form method="post" action="adm_dodom.php" name="main" id="main">
<table class="tlist">
    <tr><th></th><th><?php __("Action"); ?></th><th><?php __("Domain"); ?></th><th><?php __("Creator"); ?></th><th><?php __("Connect as"); ?><th><?php __("OK?"); ?></th><th><?php __("Status"); ?></th></tr>
<?php
$col=1;
for($i=0;$i<count($c);$i++) {
 $col=3-$col;
?>

<tr class="lst<?php echo $col; ?>">
				    <td><?php if ($c[$i]["noerase"]) {
			echo "<img src=\"icon/encrypted.png\" width=\"16\" height=\"16\" alt=\""._("Locked Domain")."\" />";
				    } ?></td>
<td><div class="ina"><a href="adm_domlock.php?domain=<?php echo urlencode($c[$i][domaine]); ?>"><?php
   if ($c[$i]["noerase"]) __("Unlock"); else __("Lock");  ?></a></div></td>
<td><a href="http://<?php echo $c[$i][domaine]; ?>" target="_blank"><?php echo $c[$i]["domaine"]; ?></a></td>
<td><?php echo $c[$i]["login"]; ?></td>
<td>
<?php		  if($admin->checkcreator($c[$i]['uid'])) {
		?>
			<div class="ina"><a href="adm_login.php?id=<?php echo $c[$i]["uid"];?>"><?php __("Connect as"); ?></a></div>
		<?php } ?>
</td>
<td style="background: <?php 
				       if ($c[$i]["errno"]==0) {
					 echo "green";
				       } else {
					 echo  "red";
				       }
  ?>">&nbsp;
</td>
<td><?php echo nl2br($c[$i]["errstr"]); ?></td>
</tr>
<?php
}
?>
</table>
</form>
<?php include_once("foot.php"); ?>
