<?php

require_once("config.php");

function squirrelmail_plugin_init_alternc_changepass() {
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['optpage_register_block']['alternc_changepass'] = 'alternc_changepass_optpage_register_block';
}


function alternc_changepass_optpage_register_block() {
    global $optpage_blocks;
    textdomain("changepass");
    $optpage_blocks[] = array(
        'name' => _("Change Password"),
        'url'  => '../plugins/alternc_changepass/change.php',
        'desc' => _("This allow you to change your mail password."),
        'js'   => false
    );
    textdomain("squirrelmail");
}


?>
