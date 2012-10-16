<?php

@include_once("/etc/squirrelmail/alternc-changepass.conf");
bindtextdomain("alternc-changepass", ALTERNC_LOC."/bureau/locales");

function squirrelmail_plugin_init_alternc_changepass() {
    global $squirrelmail_plugin_hooks;
    $squirrelmail_plugin_hooks['optpage_register_block']['alternc_changepass'] = 'alternc_changepass_optpage_register_block';
}


function alternc_changepass_optpage_register_block() {
    global $optpage_blocks;
    textdomain("alternc-changepass");
    $optpage_blocks[] = array(
        'name' => _("Change Password"),
        'url'  => '../plugins/alternc_changepass/change.php',
        'desc' => _("Change the password of your email account."),
        'js'   => false
    );
    textdomain("squirrelmail");
}


?>
