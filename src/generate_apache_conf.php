#!/usr/bin/php -q
<?php

require_once("/usr/share/alternc/panel/class/config_nochk.php");
ini_set("display_errors", 1);

echo $dom->generate_apacheconf();


