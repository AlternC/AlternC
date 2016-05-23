<?php

require_once("../class/config.php");

$fields = array (
  "enable" => array("post","string","0")
);

getFields($fields);

print_r($enable);
if ($enable) {
  $debug_alternc->activate();
} else {
  $debug_alternc->desactivate();
}

header("Location: /main.php");


?>
