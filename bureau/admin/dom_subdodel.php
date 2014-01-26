<?php
/*
 $Id: dom_subdodel.php,v 1.2 2003/06/10 11:18:27 root Exp $
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
include_once("head.php");

$fields = array (
	"sub_domain_id"    => array ("request", "integer", ""),
);
getFields($fields);

$dom->lock();
if (!$r=$dom->get_sub_domain_all($sub_domain_id)) {
        $error=$err->errstr();
}
$dom->unlock();


$dt=$dom->domains_type_lst();
if (!$isinvited && $dt[strtolower($r['type'])]["enable"] != "ALL" ) {
  __("This page is restricted to authorized staff");
  exit();
}


$dom->lock();
if (!$r=$dom->get_sub_domain_all($sub_domain_id)) {
        $error=$err->errstr();
}

if (!$dom->del_sub_domain($sub_domain_id)) {
	$error=$err->errstr();
}

$dom->unlock();

?>
<h3><?php echo sprintf(_("Deleting the subdomain %s:"),(($r['name'])?$r['name'].".":$r['name']).$r['domain']); ?></h3>
<hr id="topbar"/>
<br />
<?php
	if (isset($error) && $error) {
		echo "<p class=\"alert alert-danger\">$error</p>";
		include_once("foot.php");
		exit();
	} else {
        $t = time();
	// XXX: we assume the cron job is at every 5 minutes
        $error=strtr(_("The modifications will take effect at %time.  Server time is %now."), array('%now' => date('H:i:s', $t), '%time' => date('H:i:s', ($t-($t%300)+300)))); 
	    echo "<p class=\"alert alert-info\">".$error."</p>";
	}
?>
<p><span class="ina"><a href="dom_edit.php?domain=<?php echo urlencode($r['domain']) ?>"><?php __("Click here to continue"); ?></a></span></p>
<?php include_once("foot.php"); ?>
