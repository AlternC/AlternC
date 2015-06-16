#!/usr/bin/php
<?php
/*
  at alternc.install time
  synchronize the required domain templates with the current install
  (do they have php52, roundcube, squirrelmail, etc.?)
 */
if ($argv[1] == "templates") {
    // install ssl.conf
    echo "[alternc-ssl] Installing ssl.conf template\n";
    copy("/etc/alternc/templates/apache2/mods-available/ssl.conf","/etc/apache2/mods-available/ssl.conf");
    mkdir("/var/run/alternc-ssl");
    chown("/var/run/alternc-ssl","alterncpanel");
    chgrp("/var/run/alternc-ssl","alterncpanel");
    // replace open_basedir line if necessary : 
    exec('sed -i -e "s#:/var/run/alternc#:/var/run#" -e "s#:/run/alternc#:/run#" /etc/alternc/apache2.conf /etc/alternc/templates/alternc/apache2.conf');
}

if ($argv[1] == "before-reload") {
    // Bootstrap
    require_once("/usr/share/alternc/panel/class/config_nochk.php");

    echo "[alternc-ssl] Installing domaines-types\n";
    $db->query("INSERT IGNORE INTO `domaines_type` (name, description, target, entry, compatibility, enable, only_dns, need_dns, advanced ) VALUES
            ('vhost-ssl', 'Locally hosted forcing HTTPS', 'DIRECTORY', '%SUB% IN A @@PUBLIC_IP@@', 'vhost,url,txt,defmx,defmx2,mx,mx2', 'ALL', 0, 0, 0);");

    $db->query("INSERT IGNORE INTO `domaines_type` (name, description, target, entry, compatibility, enable, only_dns, need_dns, advanced ) VALUES
            ('vhost-mixssl', 'Locally hosted HTTP and HTTPS', 'DIRECTORY', '%SUB% IN A @@PUBLIC_IP@@', 'vhost,url,txt,defmx,defmx2,mx,mx2', 'ALL', 0, 0, 1);");

    $db->query("INSERT IGNORE INTO `domaines_type` (name, description, target, entry, compatibility, enable, only_dns, need_dns, advanced ) VALUES
            ('panel-ssl', 'HTTPS AlternC panel access', 'NONE', '%SUB% IN A @@PUBLIC_IP@@', 'txt,mx,mx2,defmx,defmx2', 'ALL', 0, 0, 1);");

    $db->query("INSERT IGNORE INTO `domaines_type` (name, description, target, entry, compatibility, enable, only_dns, need_dns, advanced ) VALUES
            ('url-ssl', 'URL redirection, HTTP & HTTPS', 'URL', '%SUB% IN A @@PUBLIC_IP@@', 'txt,mx,mx2,defmx,defmx2', 'ALL', 0, 0, 1);");

    $db->query("SELECT * FROM domaines_type WHERE name='roundcube';");
    if ($db->next_record()) {
        $db->query("INSERT IGNORE INTO `domaines_type` (name, description, target, entry, compatibility, enable, only_dns, need_dns, advanced ) VALUES
    ('roundcube-ssl', 'HTTPS Roundcube Webmail', 'NONE', '%SUB% IN A @@PUBLIC_IP@@', 'mx,mx2,defmx,defmx2,txt', 'ALL', 0, 0, 1;");
    } else {
        $db->query("DELETE FROM domaines_type WHERE name='roundcube-ssl';");
        $db->query("UPDATE sub_domaines SET web_action='DELETE' WHERE type='roundcube-ssl';");
    }

    $db->query("SELECT * FROM domaines_type WHERE name='squirrelmail';");
    if ($db->next_record()) {
        $db->query("INSERT IGNORE INTO `domaines_type` (name, description, target, entry, compatibility, enable, only_dns, need_dns, advanced ) VALUES
            ('squirrelmail-ssl', 'HTTPS Squirrelmail Webmail', 'NONE', '%SUB% IN A @@PUBLIC_IP@@', 'mx,mx2,defmx,defmx2,txt', 'ALL', 0, 0, 1);");
    } else {
        $db->query("DELETE FROM domaines_type WHERE name='squirrelmail-ssl';");
        $db->query("UPDATE sub_domaines SET web_action='DELETE' WHERE type='squirrelmail-ssl';");
    }

    $db->query("SELECT * FROM domaines_type WHERE name='php52';");
    if ($db->next_record()) {
        $db->query("INSERT IGNORE INTO `domaines_type` (name, description, target, entry, compatibility, enable, only_dns, need_dns, advanced ) VALUES
            ('php52-ssl', 'php52 forcing HTTPS', 'DIRECTORY', '%SUB% IN A @@PUBLIC_IP@@', 'vhost,url,txt,defmx,defmx2,mx,mx2', 'ALL', 0, 0, 0);");
        $db->query("INSERT IGNORE INTO `domaines_type` (name, description, target, entry, compatibility, enable, only_dns, need_dns, advanced ) VALUES
            ('php52-mixssl', 'php52 HTTP and HTTPS', 'DIRECTORY', '%SUB% IN A @@PUBLIC_IP@@', 'vhost,url,txt,defmx,defmx2,mx,mx2', 'ALL', 0, 0, 0);");
    } else {
        $db->query("DELETE FROM domaines_type WHERE name='php52-ssl';");
        $db->query("UPDATE sub_domaines SET web_action='DELETE' WHERE type='php52-ssl';");
        $db->query("DELETE FROM domaines_type WHERE name='php52-mixssl';");
        $db->query("UPDATE sub_domaines SET web_action='DELETE' WHERE type='php52-mixssl';");
    }

    // Enable name-based virtual hosts in Apache2 : 
    $f = fopen("/etc/apache2/ports.conf", "rb");
    if (!$f) {
        echo "FATAL: there is no /etc/apache2/ports.conf ! I can't configure name-based virtual hosts\n";
    } else {
        $found = false;
        while ($s = fgets($f, 1024)) {
            if (preg_match(":^[^#]*NameVirtualHost.*443:", $s)) {
                $found = true;
                break;
            }
        }
        fclose($f);
        if (!$found) {
            $f = fopen("/etc/apache2/ports.conf", "ab");
            fputs($f, "\n<IfModule mod_ssl.c>\n  NameVirtualHost *:443\n\n</IfModule>\n");
            fclose($f);
        }
    }
} // before-reload
