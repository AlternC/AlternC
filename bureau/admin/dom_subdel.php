<?php
/*
 $Id: dom_subdel.php,v 1.3 2003/08/13 23:31:47 root Exp $
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
 Purpose of file: delete a subdomain
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"sub_domain_id"       => array ("request", "integer", ""),
);
getFields($fields);

$dt=$dom->domains_type_lst();
if (!$isinvited && $dt[strtolower($type)]["enable"] != "ALL" ) {
  __("This page is restricted to authorized staff");
  exit();
}


$dom->lock();
if (!$r=$dom->get_sub_domain_all($sub_domain_id)) {
	$error=$err->errstr();
}
$dom->unlock();

?>
<h3><?php printf(_("Deleting subdomain %s"),"http://".ife($r['name'],$r['name'].".").$r['domain']); ?> : </h3>
<?php
	if (isset($error) && $error) {
		echo "<p class=\"error\">$error</p>";
		include_once("foot.php");
		exit();
	}
?>
<hr id="topbar"/>
<br />
<!-- *****************************************
		 gestion du sous-domaine
 -->
<form action="dom_subdodel.php" method="post">
	<p class="error">
	<input type="hidden" name="sub_domain_id" value="<?php echo $sub_domain_id ?>" />
<?php __("WARNING : Confirm the deletion of the subdomain"); ?> : </p>
	<p><?php ecif($r['name'],$r['name']."."); echo $r['domain']; ?></p>
	<blockquote>
	<input type="submit" class="inb" name="confirm" value="<?php __("Yes"); ?>" />&nbsp;&nbsp;
	<input type="button" class="inb" name="cancel" value="<?php __("No"); ?>" onclick="history.back();" />
	</blockquote>
</form>
<?php include_once("foot.php"); ?>
