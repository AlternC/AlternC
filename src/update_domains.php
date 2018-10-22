#!/usr/bin/php -q
<?php

// bootstrap
require_once("/usr/share/alternc/panel/class/config_nochk.php");

putenv("PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin");

$dom->update_domains();

