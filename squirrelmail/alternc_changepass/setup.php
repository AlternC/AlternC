<?php

@include_once("/etc/squirrelmail/alternc-changepass.conf");
if (!defined("ALTERNC_CHANGEPASS_LOC")) {
  error_log("No configuration for squirrelmail plugin at /etc/squirrelmail/alternc-changepass.conf, please check");
  return;
}

bindtextdomain("alternc-changepass", ALTERNC_CHANGEPASS_LOC."/bureau/locales");

function squirrelmail_plugin_init_alternc_changepass() {
    global $squirrelmail_plugin_hooks;
    $squirrelmail_plugin_hooks['optpage_register_block']['alternc_changepass'] = 'alternc_changepass_optpage_register_block';
}


function alternc_changepass_optpage_register_block() {
    global $optpage_blocks;
    textdomain("alternc-changepass");
    $optpage_blocks[] = array(
        'name' => __("Change Password", "alternc", true),
        'url'  => '../plugins/alternc_changepass/change.php',
        'desc' => __("Change the password of your email account.", "alternc", true),
        'js'   => false
    );
    textdomain("squirrelmail");
}


?>
