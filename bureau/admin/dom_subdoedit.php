<?php
/*
 $Id: dom_subdoedit.php,v 1.1.1.1 2003/03/26 17:41:29 root Exp $
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
 Purpose of file: Edit a domain parameters
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$fields = array (
	"domain"    => array ("post", "string", ""),
	"sub"       => array ("post", "string", ""),
	"type"      => array ("post", "string", $dom->type_local),
  	"sub_domain_id" => array ("post", "integer", ""),
);
getFields($fields);

// here we get a dynamic-named value
$dynamicvar="t_$type";
$fields = array (
  "$dynamicvar"   => array ("post", "string", ""),
);
getFields($fields);
$value=$$dynamicvar;
// The dynamic value is now in $value

$dom->lock();

$dt=$dom->domains_type_lst();
if ( (!isset($isinvited) || !$isinvited) && $dt[strtolower($type)]["enable"] != "ALL" ) {
  $msg->raise("ERROR", "dom", _("This page is restricted to authorized staff"));
  include("dom_edit.php");
  exit();
}

if (empty($sub_domain_id)) $sub_domain_id=null;
$r=$dom->set_sub_domain($domain,$sub,$type,$value, $sub_domain_id);

$dom->unlock();

if (!$r) {
  $noread=true;
  include("dom_subedit.php"); 
  exit();
} else {
  $t = time();
  // XXX: we assume the cron job is at every 5 minutes
  $noread=false;
  $msg->raise("INFO", "dom", _("The modifications will take effect at %s. Server time is %s."), array(date('H:i:s', ($t-($t%300)+300)), date('H:i:s', $t)));
  foreach($fields as $k=>$v) unset($$k);
}
include("dom_edit.php");
exit;

?>
