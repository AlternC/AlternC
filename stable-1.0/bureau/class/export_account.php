<?php

// EXPERIMENTAL : user data export.

include("config.php");

sem_release($alternc_sem);

$mem->su($id);

$dom->lock();

foreach($classes as $c) {
  if (method_exists($GLOBALS[$c],"alternc_export")) {
    $GLOBALS[$c]->alternc_export("/tmp");
  }
}

$dom->unlock();

$mem->unsu();

?>