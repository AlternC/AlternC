#!/usr/bin/php -q
<?php

// we don't check our AlternC session
if(!chdir("/usr/share/alternc/panel"))
exit(1);
require("/usr/share/alternc/panel/class/config_nochk.php");

$db->query("SELECT * FROM domaines WHERE gesdns=1 AND gesmx=1;");
$add=array();
while ($db->next_record()) {
    $add[$db->Record["domaine"]]=$db->Record["compte"];
}
foreach($add as $domain => $id) {
    // Convert DKIM keys into SUB_DOMAINES table
    if (file_exists("/etc/opendkim/keys/".$domain."/alternc.txt")) {
        $dkim_key = $mail->dkim_get_entry($domain);
        if ($dkim_key) {
            // Add subdomain dkim entry
            $db->query("INSERT INTO sub_domaines 
        SET compte=?, domaine=?, sub='alternc._domainkey', valeur=?, type='dkim', web_action='OK', web_result=0, enable='ENABLED';",
            array($id, $domain, $dkim_key)
            );
            // Alternc.INSTALL WILL reload DNS zones anyway, so fear not we don't set dns_action="RELOAD" here.
        }
    }
    // Convert autodiscover into SUB_DOMAINES table
    $db->query("INSERT INTO sub_domaines
        SET compte=?, domaine=?, sub='autodiscover', valeur='', type='autodiscover', web_action='UPDATE', web_result=0, enable='ENABLED';",
        array($id, $domain)
    );

    // Convert autodiscover into SUB_DOMAINES table
    $db->query("INSERT INTO sub_domaines
        SET compte=?, domaine=?, sub='autoconfig', valeur='', type='autodiscover', web_action='UPDATE', web_result=0, enable='ENABLED';",
        array($id, $domain)
    );
}

