<?php
/*
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
*/

/**
 * Edit a subdomain parameters 
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");

$fields = array (
	"domain"    => array ("post", "string", ""),
	"sub"       => array ("post", "string", ""),
	"type"      => array ("post", "string", $dom->type_local),
  	"sub_domain_id" => array ("post", "integer", 0),
);
getFields($fields);

// here we get a dynamic-named value
$dynamicvar="t_$type";
$httpsvar="https_$type";
$fields = array (
  "$dynamicvar"   => array ("post", "string", ""),
  "$httpsvar"   => array ("post", "string", ""),
);
getFields($fields);
$value=$$dynamicvar;
$https=$$httpsvar;
// The dynamic value is now in $value

$dom->lock();

$dt=$dom->domains_type_lst();
if ( (!isset($isinvited) || !$isinvited) && $dt[strtolower($type)]["enable"] != "ALL" ) {
  $msg->raise("ERROR", "dom", __("This page is restricted to authorized staff", "alternc", true));
  include("dom_edit.php");
  exit();
}

if (empty($sub_domain_id)) $sub_domain_id=null;
$r=$dom->set_sub_domain($domain, $sub, $type, $value, $sub_domain_id, $https);

$dom->unlock();

if (!$r) {
  if ($sub_domain_id!=0) {
    $noread=true;
    include("dom_subedit.php"); 
  } else {
    // it was a creation, not an edit
    include("dom_edit.php");
  }
    exit();
} else {
  $t = time();
  // TODO: we assume the cron job is at every 5 minutes
  $noread=false;
  $msg->raise("INFO", "dom", __("The modifications will take effect at %s. Server time is %s.", "alternc", true), array(date('H:i:s', ($t-($t%300)+300)), date('H:i:s', $t)));
  foreach($fields as $k=>$v) unset($$k);
}
include("dom_edit.php");
exit;

?>
