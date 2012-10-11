<?php
require_once("../class/config.php");
if (!$admin->enabled) {
    __("This page is restricted to authorized staff");
    exit();
}

$fields = array (
	"name"    		=> array ("post", "string", ""),
	"description"    	=> array ("post", "string", ""),
	"target"    		=> array ("post", "string", ""),
	"entry"    		=> array ("post", "string", ""),
	"compatibility"    	=> array ("post", "string", ""),
	"enable"    		=> array ("post", "string", ""),
	"only_dns"    		=> array ("post", "string", ""),
	"need_dns"    		=> array ("post", "string", ""),
	"advanced"    		=> array ("post", "string", ""),
	"create_tmpdir"    		=> array ("post", "string", ""),
	"create_targetdir"    		=> array ("post", "string", ""),
);
getFields($fields);

if ( ! $dom->domains_type_update($name, $description, $target, $entry, $compatibility, $enable, $only_dns, $need_dns, $advanced,$create_tmpdir,$create_targetdir) ) {
    die($err->errstr());
} else {
    include("adm_domstype.php");
}

?>


