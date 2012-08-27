<?php
/*
 $Id: dom_doadd.php,v 1.3 2003/06/10 11:18:27 root Exp $
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
 Purpose of file: Add a new domain to a member's account
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"newdomain"    => array ("post", "string", ""),
	"dns"          => array ("post", "string", ""),
	"newisslave"   => array ("post", "integer" ,0), 
	"slavedom"     => array ("post", "string" ,0), 
);
getFields($fields);

$dom->lock();

if (!$dom->add_domain($newdomain,$dns,0,0,$newisslave,$slavedom)) {
	$error=$err->errstr();
	include("dom_add.php");
	exit();
}

$dom->unlock();

?>
<h3><?php __("Add a domain"); ?></h3>
<p>
<?php printf(_("Your new domain %s has been successfully installed"),$newdomain); ?><br /><br />
<span class="inb"><a href="dom_edit.php?domain=<?php echo urlencode($newdomain);?>" ><?php __("Click here to continue"); ?></a></span><br />
<?php $mem->show_help("add_domain"); ?>
<br />
<?php
	if (is_array($dom->dns)) {
		echo "<br />"._("Whois result on the domain")." : <pre>";
		reset($dom->dns);
		while (list($key,$val)=each($dom->dns)) {
			echo "nameserver: $val\n";
		}
		echo "</pre>";
	}
?>
</p>
<?php include_once("foot.php"); ?>
