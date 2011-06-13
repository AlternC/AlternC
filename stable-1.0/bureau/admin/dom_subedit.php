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
	"domain"    => array ("request", "string", ""),
	"sub"       => array ("request", "string", ""),
	"type"      => array ("request", "string", $dom->type_local),
	"value"     => array ("request", "string",  ""),
);
getFields($fields);

$dom->lock();
$domroot=$dom->get_domain_all($domain);

$dt=$dom->domains_type_lst();
if (!$isinvited && $dt[strtolower($type)]["enable"] != "ALL" ) {
  __("This page is restricted to authorized staff");
  exit();
}


if (!$noread) {
  if (!$r=$dom->get_sub_domain_all($domain,$sub,$type,$value)) {
    $error=$err->errstr();
  }
}

echo "<h3>";
__("Editing subdomain");
echo " http://"; ecif($sub,$sub."."); echo $domain."</h3>";
if ($error) {
	echo "<p class=\"error\">$error</p>";
  include_once("foot.php");
  exit();
} 
$dom->unlock();
?>

<hr id="topbar"/>
<br />
<?php 
  $isedit=true;
require_once('dom_edit.inc.php');
sub_domains_edit($domain,$sub,$type,$value);

include_once("foot.php"); 
?>
