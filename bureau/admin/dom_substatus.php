<?php
require_once("../class/config.php");

$fields = array (
  "sub_id"    => array ("post", "integer", ""),
  "status"    => array ("post", "string", ""),
);
getFields($fields);

$dom->lock();

$r=$dom->sub_domain_change_status($sub_id,$status);

# Usefull for dom_edit
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
  // XXX: we assume the cron job is at every 5 minutes
  $msg->raise("INFO", "dom", _("The modifications will take effect at %s. Server time is %s."), array(date('H:i:s', ($t-($t%300)+300)), date('H:i:s', $t)));
  foreach($fields as $k=>$v) unset($k);
}
include("dom_edit.php");
exit;

?>
