#!/usr/bin/php
<?php

// EXPERIMENTAL : user data export.
die('Proof of concept');

include("config.php");

sem_release($alternc_sem);

$mem->su($id);

$dom->lock();

$hooks->invoke("alternc_export",array("/tmp"));

$dom->unlock();

$mem->unsu();

?>
