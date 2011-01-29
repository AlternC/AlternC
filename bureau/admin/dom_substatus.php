<?php
require_once("../class/config.php");

$fields = array (
	"domain"    => array ("request", "string", ""),
	"sub"       => array ("request", "string", ""),
	"type"      => array ("request", "string", ""),
  "value"     => array ("request", "string", ""),
  "status"    => array ("request", "string", ""),
);
getFields($fields);

$dom->lock();

$r=$dom->sub_domain_change_status($domain,$sub,$type,$value,$status);

$dom->unlock();

if (!$r) {
  $error=$err->errstr();
  $noread=true;
  include("dom_edit.php"); 
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
