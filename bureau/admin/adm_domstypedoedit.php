<?php
require_once("../class/config.php");
if (!$admin->enabled) {
    $msg->raise("ERROR", "admin", _("This page is restricted to authorized staff"));
    echo $msg->msg_html_all();
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

if (! $dom->domains_type_update($name, $description, $target, $entry, $compatibility, $enable, $only_dns, $need_dns, $advanced,$create_tmpdir,$create_targetdir) ) {
    include("adm_domstypedoedit.php");
} else {
    $msg->raise("INFO", "admin", _("Domain type is updated"));
    include("adm_domstype.php");
}
?>


