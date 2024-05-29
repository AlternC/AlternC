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
 * Subdomain change status page
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");

$fields = array (
  "sub_id"    => array ("get", "integer", ""),
  "status"    => array ("get", "string", ""),
);
getFields($fields);

$dom->lock();

$r=$dom->sub_domain_change_status($sub_id,$status);

# Useful for dom_edit
$domi = $dom->get_sub_domain_all($sub_id);
$domain=$domi['domain'];
$sub=$domi['name'];

$dom->unlock();

if (!$r) {
  $noread=true;
  include("dom_edit.php"); 
  exit();
} else {
  $t = time();
  // TODO: we assume the cron job is at every 5 minutes 
  $msg->raise("INFO", "dom", __("The modifications will take effect at %s. Server time is %s.", "alternc", true), array(date('H:i:s', ($t-($t%300)+300)), date('H:i:s', $t)));
  foreach($fields as $k=>$v) unset($k);
}
include("dom_edit.php");
exit;

?>
