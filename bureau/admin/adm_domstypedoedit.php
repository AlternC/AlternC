<?php
require_once("../class/config.php");
if (!$admin->enabled) {
    __("This page is restricted to authorized staff");
    exit();
}


if ( ! $dom->domains_type_update($name, $description, $target, $entry, $compatibility, $enable, $only_dns, $need_dns, $advanced) ) {
    die($err->errstr());
} else {
    include("adm_domstype.php");
}

?>


