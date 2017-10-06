<?php
require_once("../class/config.php");
if (!$admin->enabled) {
    $msg->raise('Error', "admin", _("This page is restricted to authorized staff"));
    echo $msg->msg_html_all();
    exit();
}

$fields = array (
  "name"    => array ("request", "string", ""),
);
getFields($fields);


if (! empty($name) || ($dom->domains_type_regenerate($name)) ) {
  $msg->raise('Ok', "admin", _("Regenerate pending"));
}

include("adm_domstype.php");
?>
