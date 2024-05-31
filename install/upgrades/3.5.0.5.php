#!/usr/bin/php
<?php

// we don't check our AlternC session
if(!chdir("/usr/share/alternc/panel"))
exit(1);
require("/usr/share/alternc/panel/class/config_nochk.php");

$help_baseurl = variable_get('help_baseurl');

if (preg_match('#aide-alternc.org#', $help_baseurl)) {
  $help_baseurl = variable_set('help_baseurl', 'https://aide.alternc.org/', 'The base URL for help liks', array('desc' => 'Help URL', 'type' => 'string'));
}