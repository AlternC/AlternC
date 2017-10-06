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
	"sub_domain_id"    => array ("post", "integer", ""),
);
getFields($fields);

$dom->lock();
$r=$dom->get_sub_domain_all($sub_domain_id);
$dt=$dom->domains_type_lst();
$dom->unlock();

if (!$isinvited && $dt[strtolower($r['type'])]["enable"] != "ALL" ) {
  $msg->raise('Error', "dom", _("This page is restricted to authorized staff"));
  echo $msg->msg_html_all();
  exit();
}


$dom->lock();
$r=$dom->get_sub_domain_all($sub_domain_id);
$dom->del_sub_domain($sub_domain_id);
$dom->unlock();

?>
<h3><?php echo sprintf(_("Deleting the subdomain %s:"),(($r['name'])?$r['name'].".":$r['name']).$r['domain']); ?></h3>
<hr id="topbar"/>
<br />
<?php
	if ($msg->has_msgs('Error')) {
		echo $msg->msg_html_all();
		include_once("foot.php");
		exit();
	} else {
        $t = time();
	// XXX: we assume the cron job is at every 5 minutes
        $msg->raise('Ok', "dom", _("The modifications will take effect at %s.  Server time is %s."), array(date('H:i:s', ($t-($t%300)+300)), date('H:i:s', $t))); 
	echo $msg->msg_html_all();
	}
?>
<p><span class="ina"><a href="dom_edit.php?domain=<?php echo urlencode($r['domain']) ?>"><?php __("Click here to continue"); ?></a></span></p>
<?php include_once("foot.php"); ?>
