<?php
/*
 $Id: dom_subedit.php,v 1.3 2003/08/13 23:01:45 root Exp $
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
	"sub_domain_id"    => array ("request", "integer", 0),
);
getFields($fields);

$dom->lock();

$r=true;
if (!isset($noread) || !$noread) {
  if (!$r=$dom->get_sub_domain_all($sub_domain_id)) {
    echo $msg->msg_html_all();
    include_once('foot.php');
    die();
  }
}

// Just in case
if (!$r) { 
  include_once('foot.php');
  die();
}

$dt=$dom->domains_type_lst();
if (!$isinvited && $dt[strtolower($r['type'])]["enable"] != "ALL" ) {
  $msg->raise("ERROR", "dom", _("This page is restricted to authorized staff"));
  include("dom_edit.php");
  exit();
}

$domroot=$dom->get_domain_all($r['domain']);
$dom->unlock();

if ($msg->has_msgs("ERROR")) {
  include_once("dom_edit.php");
  exit();
} 

echo "<h3>";
__("Editing subdomain");
echo " http://"; ecif($r['name'],$r['name']."."); echo $r['domain']."</h3>";

echo $msg->msg_html_all();
?>

<hr id="topbar"/>
<br />
<?php 
  $isedit=true;
require_once('dom_edit.inc.php');
sub_domains_edit($r['domain'],$sub_domain_id);

include_once("foot.php"); 
?>
