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
	"domain"    => array ("request", "string", ""),
	"sub"       => array ("request", "string", ""),
	"sub_old"   => array ("request", "string", ""),
	"type"      => array ("request", "string", $dom->type_local),
	"type_old"  => array ("request", "string", ""),
  "value_old" => array ("request", "string", ""),
);
getFields($fields);

// here we get a dynamic-named value
$dynamicvar="t_$type";
$fields = array (
  "$dynamicvar"   => array ("request", "string", ""),
);
getFields($fields);
$value=$$dynamicvar;
// The dynamic value is now in $value

$dom->lock();

$r=$dom->set_sub_domain($domain,$sub,$type,$value, $type_old, $sub_old, $value_old);

$dom->unlock();

if (!$r) {
  $error=$err->errstr();
  $noread=true;
  include("dom_subedit.php"); 
  exit();
} else {
  $t = time();
  // XXX: we assume the cron job is at every 5 minutes
  $error=strtr(_("The modifications will take effect at %time. Server time is %now."), array('%now' => date('H:i:s', $t), '%time' => date('H:i:s', ($t-($t%300)+300))));
  foreach($fields as $k=>$v) unset($k);
}
include("dom_edit.php");
exit;

?>
