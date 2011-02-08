<?php
require_once("../class/config.php");
if (!$admin->enabled) {
    __("This page is restricted to authorized staff");
    exit();
}

$fields = array (
  "name"    => array ("request", "string", ""),
);
getFields($fields);


if (empty($name) || (! $dom->domains_type_regenerate($name)) ) {
  die($err->errstr());
} else {
  $error="Regenerate pending"; 
  include("adm_domstype.php");
}

?>


