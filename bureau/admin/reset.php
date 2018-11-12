<?php

require_once("../class/config_nochk.php");

if (isset($_GET['uid']) && isset($_GET['token']) && isset($_GET['timestamp'])) {
    // We may have received a one-time use link.
    $logged_in = $mem->temporary_login($_GET['uid'], $_GET['timestamp'],
                                       $_GET['token']);
    if ($logged_in) {
        $msg->raise('INFO', 'admin/reset', _('Please change your password'));
        header("Location: /mem_param.php");
        exit;
    }
}
header("Location: /index.php");
