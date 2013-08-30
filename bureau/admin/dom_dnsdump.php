<?php
require_once("../class/config.php");

$fields = array (
  "domain"               => array ("get", "string", ""),
);
getFields($fields);

if (empty($domain)) die(_("Error: no domain"));

foreach ($dom->dump_axfr($domain) as $o ) {
  echo "$o\n";
}

?>
