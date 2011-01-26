<?php
require_once("../class/config.php");
if (!$admin->enabled) {
    __("This page is restricted to authorized staff");
    exit();
}


if ( ! $dom->domains_type_update($id, $name, $description, $ask_dest, $entry, $compatibility) ) {
    die($err->errstr());
} else {
include("adm_domstype.php");
}

?>


