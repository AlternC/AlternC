#!/usr/bin/php -q
<?php

/**
  *
  * Generate Bind configuration for AlternC
  *
  * To force generation, /launch/generate_bind_conf.php --force
  *
  *
 **/

require_once("/usr/share/alternc/panel/class/config_nochk.php");
ini_set("display_errors", 1);

$bind = new system_bind();

$force = false;
if (in_array('--force', $argv)) { // Want to force
  $force=true;
}

$bind->regenerate_conf($force);

