#!/usr/bin/php -q
<?php

/**
 * Launch the users crontab for AlternC
 * php, parallel-curl, secured mode.
 **/

require_once("/usr/share/alternc/panel/class/config_nochk.php");
ini_set("display_errors", 1);

if (file_exists("/run/alternc/jobs-lock")) {
  echo "jobs-lock exists, did you ran alternc.install?\n";
  echo "canceling cron_users\n";
  exit(1);
}

if (isset($argv[1]) && $argv[1]=="debug") {
  $GLOBALS["DEBUG"]=true;
}

$cron->execute_cron();


