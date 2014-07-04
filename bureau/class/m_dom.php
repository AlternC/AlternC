<?php

/*
  ----------------------------------------------------------------------
  AlternC - Web Hosting System
  Copyright (C) 2000-2012 by the AlternC Development Team.
  https://alternc.org/
  ----------------------------------------------------------------------
  LICENSE

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License (GPL)
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  To read the license please visit http://www.gnu.org/copyleft/gpl.html
  ----------------------------------------------------------------------
  Purpose of file: PHP Class that manage domain names installed on the server
  ----------------------------------------------------------------------
 */

define('SLAVE_FLAG', "/var/run/alternc/refresh_slave");

/**
 * Classe de gestion des domaines de l'hébergé.
 * 
 * Cette classe permet de gérer les domaines / sous-domaines, redirections
 * dns et mx des domaines d'un membre hébergé.<br />
 */
class m_dom {

    /** $domains : Cache des domaines du membre
     * @access private
     */
    var $domains;

    /** $dns : Liste des dns trouvés par la fonction whois
     * @access private
     */
    var $dns;

    /** Flag : a-t-on trouvé un sous-domaine Webmail pour ce domaine ?
     * @access private
     */
    var $webmail;

    /**
     * Systéme de verrouillage du cron
     * Ce fichier permet de verrouiller le cron en attendant la validation
     * du domaine par update_domains.sh
     * @access private
     */
    var $fic_lock_cron = "/var/run/alternc/cron.lock";

    /**
     * Le cron a-t-il été bloqué ?
     * Il faut appeler les fonctions privées lock et unlock entre les
     * appels aux domaines.
     * @access private
     */
    var $islocked = false;
    var $type_local = "VHOST";
    var $type_url = "URL";
    var $type_ip = "IP";
    var $type_webmail = "WEBMAIL";
    var $type_ipv6 = "IPV6";
    var $type_cname = "CNAME";
    var $type_txt = "TXT";
    var $type_defmx = "DEFMX";
    var $type_defmx2 = "DEFMX2";
    var $action_insert = "0";
    var $action_update = "1";
    var $action_delete = "2";
    var $tld_no_check_at_all = "1";
    var $cache_domains_type_lst = false;

    /* ----------------------------------------------------------------- */

    /**
     * Constructeur
     */
    function m_dom() {
        $this->tld_no_check_at_all = variable_get('tld_no_check_at_all', 0, 'Disable ALL check on the TLD (users will be able to add any domain)', array('desc' => 'Disabled', 'type' => 'boolean'));
        variable_get('mailname_bounce', '%%FQDN%%', 'FQDN of the mail server, used to create vhost virtual mail_adress.', array('desc' => 'FQDN', 'type' => 'string'));
    }

    function get_panel_url_list() {
        global $db, $err;
        $err->log("dom", "get_panel_url_list");
        $db->query("SELECT sd.id as sub_id, if(length(sd.sub)>0,concat_ws('.',sd.sub,sd.domaine),sd.domaine) as fqdn from sub_domaines sd where type = 'PANEL';");
        $t = array();
        while ($db->next_record()) {
            $t[intval($db->f('sub_id'))] = $db->f('fqdn');
        }
        return $t;
    }

    /**
     * @param string $fqdn
     */
    function get_sub_domain_id_and_member_by_name($fqdn) {
        global $db, $err, $cuid;
        $err->log("dom", "get_sub_domain_by_name");
        $fqdn = mysql_real_escape_string($fqdn);
        $db->query("select sd.* from sub_domaines sd where if(length(sd.sub)>0,concat_ws('.',sd.sub,sd.domaine),sd.domaine) = '$fqdn';");
        if (!$db->next_record())
            return false;
        return array('sub_id' => intval($db->f('id')), 'member_id' => intval($db->f('compte')));
    }

    function hook_menu() {
        global $quota;
        $obj = array(
            'title' => _("Domains"),
            'ico' => 'images/dom.png',
            'link' => 'toggle',
            'pos' => 20,
            'links' => array(),
                );

        if ($quota->cancreate("dom")) {
            $obj['links'][] =
                    array(
                        'ico' => 'images/new.png',
                        'txt' => _("Add a domain"),
                        'url' => "dom_add.php",
            );
        }

        foreach ($this->enum_domains() as $d) {
            $obj['links'][] =
                    array(
                        'txt' => htmlentities($d),
                        'url' => "dom_edit.php?domain=" . urlencode($d),
            );
        }

        return $obj;
    }

    /* ----------------------------------------------------------------- */

    /**
     * Retourne un tableau contenant les types de domaines
     *
     * @return array retourne un tableau indexé contenant la liste types de domaines 
     *  authorisé. Retourne FALSE si une erreur s'est produite.
     */
    function domains_type_lst() {
        global $db, $err;
        $err->log("dom", "domains_type_lst");
        if (empty($this->cache_domains_type_lst)) {
            $db->query("select * from domaines_type order by advanced;");
            $this->cache_domains_type_lst = array();
            while ($db->next_record()) {
                $this->cache_domains_type_lst[strtolower($db->Record["name"])] = $db->Record;
            }
        }
        return $this->cache_domains_type_lst;
    }

    function domains_type_enable_values() {
        global $db, $err, $cuid;
        $err->log("dom", "domains_type_target_values");
        $db->query("desc domaines_type;");
        $r = array();
        while ($db->next_record()) {
            if ($db->f('Field') == 'enable') {
                $tab = explode(",", substr($db->f('Type'), 5, -1));
                foreach ($tab as $t) {
                    $r[] = substr($t, 1, -1);
                }
            }
        }
        return $r;
    }

    /**
     * @param integer $type
     */
    function domains_type_target_values($type = null) {
        global $db, $err, $cuid;
        $err->log("dom", "domains_type_target_values");
        if (is_null($type)) {
            $db->query("desc domaines_type;");
            $r = array();
            while ($db->next_record()) {
                if ($db->f('Field') == 'target') {
                    $tab = explode(",", substr($db->f('Type'), 5, -1));
                    foreach ($tab as $t) {
                        $r[] = substr($t, 1, -1);
                    }
                }
            }
            return $r;
        } else {
            $db->query("select target from domaines_type where name='$type';");
            if (!$db->next_record())
                return false;
            return $db->f('target');
        }
    }

    function import_manual_dns_zone($zone, $domain, $detect_redirect = true, $save = false) {
        global $err;
        if ($save) {
            if (!$this->import_manual_dns_prep_zone($domain)) {
                $err->raise('dom', _("Err: failed to prepare the zone"));
                return false;
            }
        }

        $val = array();
        foreach (explode("\n", $zone) as $z) {
            $z = trim($z);
            if (empty($z))
                continue;
            $val[] = $this->import_manual_dns_entry($z, $domain, $detect_redirect, $save);
        }
        return $val;
    }

    /**
     * @param string $zone
     */
    function import_manual_dns_entry($zone, $domain, $detect_redirect = true, $save = false) {
        global $cuid, $err;
        $err->log("dom", "import_manual_dns_entry");
        $zone = trim($zone);
        if (empty($zone))
            return false;

        $domain = trim($domain);
        if (empty($domain)) {
            $err->raise("dom", _("Missing domain name"));
            return false;
        }

        $val = array(
            'status' => 'err', // can be 'ok', 'err', 'warn'
            'comment' => 'no val',
            'entry_old' => $zone,
            'entry_new' => array('domain' => $domain),
        );

        // Examples:
        // ; hello comment
        if (preg_match('/^;/', $zone, $ret)) {
            $val['status'] = 'ok';
            $val['comment'] = 'Just a comment, do not import';
        } else
        // Examples:
        // $TTL 86400'
        if (preg_match('/^\$TTL\h+(?P<ttl>[\dMHDmhd]+)/', $zone, $ret)) {
            $val['status'] = 'ok';
            $val['comment'] = 'Set TTL to ' . $ret['ttl'];
            $val['entry_new']['type'] = 'set_ttl';
            $val['entry_new']['value'] = $ret['ttl'];
        } else

        // Examples:
        // @ IN AAAA 127.2.1.5
        // reseau IN AAAA 145.214.44.55
        if (preg_match('/^(?P<sub>[\w\.@\-]*)\h*(?P<ttl>\d*)\h*IN\h+AAAA\h+(?P<target>[0-9A-F:]{2,40})/i', $zone, $ret)) {

            // Check if it is just a redirect
            if (substr($ret['sub'], -1) == '.') { // if ending by a "." it is allready a FQDN
                $url = "http://" . $ret['sub'];
            } else {
                if ($ret['sub'] == '@' || empty($ret['sub'])) {
                    $url = "http://" . $domain;
                } else {
                    $url = "http://" . $ret['sub'] . "." . $domain;
                }
            }
            if ($detect_redirect && $dst_url = $this->is_it_a_redirect($url)) {
                $val['status'] = 'warn';
                $val['comment'] = "Became a redirect to $dst_url";
                $val['entry_new']['type'] = 'URL';
                $val['entry_new']['sub'] = $ret['sub'];
                $val['entry_new']['value'] = $dst_url;
            } else {
                $val['status'] = 'ok';
                $val['comment'] = "Create entry AAAA with " . $ret['sub'] . " go to " . $ret['target'] . " with ttl " . $ret['ttl'];
                $val['entry_new']['type'] = 'IPV6';
                $val['entry_new']['sub'] = $ret['sub'];
                $val['entry_new']['value'] = $ret['target'];
            }
        } else


        // Examples:
        // @ IN A 127.2.1.5
        // reseau IN A 145.214.44.55
        if (preg_match('/^(?P<sub>[\w\.@\-]*)\h*(?P<ttl>\d*)\h*IN\h+A\h+(?P<target>\d+\.\d+\.\d+\.\d+)/i', $zone, $ret)) {
            // Check if it is just a redirect
            if (substr($ret['sub'], -1) == '.') { // if ending by a "." it is allready a FQDN
                $url = "http://" . $ret['sub'];
            } else {
                if ($ret['sub'] == '@' || empty($ret['sub'])) {
                    $url = "http://" . $domain;
                } else {
                    $url = "http://" . $ret['sub'] . "." . $domain;
                }
            }
            if ($detect_redirect && $dst_url = $this->is_it_a_redirect($url)) {
                $val['status'] = 'warn';
                $val['comment'] = "Became a redirect to $dst_url";
                $val['entry_new']['type'] = 'URL';
                $val['entry_new']['sub'] = $ret['sub'];
                $val['entry_new']['value'] = $dst_url;
            } else {
                $val['status'] = 'ok';
                $val['comment'] = "Create entry A with " . $ret['sub'] . " go to " . $ret['target'] . " with ttl " . $ret['ttl'];
                $val['entry_new']['type'] = 'IP';
                $val['entry_new']['sub'] = $ret['sub'];
                $val['entry_new']['value'] = $ret['target'];
            }
        } else

        // Examples:
        // @ IN NS ns.example.tld.
        // ns 3600 IN NS 145.214.44.55
        if (preg_match('/^(?P<sub>[\-\w\.@]*)\h*(?P<ttl>\d*)\h*IN\h+NS\h+(?P<target>[\w\.\-]+)/i', $zone, $ret)) {
            if (empty($ret['sub']) || $ret['sub'] == '@') {
                $val['status'] = 'warn';
                $val['comment'] = "Won't migrate it, there will get a new value";
            } else {
                $val['status'] = 'ok';
                $val['comment'] = "Create entry NS with " . $ret['sub'] . " go to " . $ret['target'] . " with ttl " . $ret['ttl'];
                $val['entry_new']['type'] = 'FIXME-NS';
                $val['entry_new']['sub'] = $ret['sub'];
                $val['entry_new']['value'] = $ret['target'];
            }
        } else

        // Examples:
        // agenda IN CNAME ghs.google.com.
        // www 3600 IN CNAME @
        if (preg_match('/^(?P<sub>[\-\w\.@]*)\h*(?P<ttl>\d*)\h*IN\h+CNAME\h+(?P<target>[@\w+\.\-]+)/i', $zone, $ret)) {
            if (substr($ret['sub'], -1) == '.') { // if ending by a "." it is allready a FQDN
                $url = "http://" . $ret['sub'];
            } else {
                if ($ret['sub'] == '@' || empty($ret['sub'])) {
                    $url = "http://" . $domain;
                } else {
                    $url = "http://" . $ret['sub'] . "." . $domain;
                }
            }
            if ($detect_redirect && $dst_url = $this->is_it_a_redirect($url)) {
                $val['status'] = 'warn';
                $val['comment'] = "Became a redirect to $dst_url";
                $val['entry_new']['type'] = 'URL';
                $val['entry_new']['sub'] = $ret['sub'];
                $val['entry_new']['value'] = $dst_url;
            } else {
                $val['status'] = 'ok';
                $val['comment'] = "Create entry CNAME with " . $ret['sub'] . " go to " . $ret['target'] . " with ttl " . $ret['ttl'];
                $val['entry_new']['type'] = 'CNAME';
                $val['entry_new']['sub'] = $ret['sub'];
                $val['entry_new']['value'] = $ret['target'];
            }
        } else

        // Examples:
        // @ IN MX 10 aspmx.l.google.com.
        // arf 3600 IN MX 20 pouet.fr.
        if (preg_match('/^(?P<sub>[\-\w\.@]*)\h*(?P<ttl>\d*)\h*IN\h+MX\h+(?P<weight>\d+)\h+(?P<target>[@\w+\.\-]+)/i', $zone, $ret)) {
            $val['status'] = 'warn';
            $val['comment'] = "Create entry MX with " . $ret['sub'] . " go to " . $ret['target'] . " with ttl " . $ret['ttl'] . " and weight 5 (initial weight was " . $ret['weight'] . ")";
            $val['entry_new']['type'] = 'MX';
            $val['entry_new']['sub'] = $ret['sub'];
            $val['entry_new']['value'] = $ret['target'];
        } else

        // Examples:
        // _sip._tcp  IN      SRV             1 100 5061 sip.example.tld.
        if (preg_match('/^(?P<sub>[\_\w\.@\-]+)\h+(?P<ttl>\d*)\h*IN\h+SRV\h+/i', $zone, $ret)) {
            $val['status'] = 'err';
            $val['comment'] = "Please add yourself the entry $zone";
        } else

        // Examples:
        // @       IN      TXT             "google-site-verification=jjjjjjjjjjjjjjjjjjjjjjjjsdsdjlksjdljdslgNj5"
        if (preg_match('/^(?P<sub>[\_\w\.@\-]*)\h*(?P<ttl>\d*)\h*IN\h+TXT\h+\"(?P<target>.+)\"/i', $zone, $ret)) {
            $val['status'] = 'ok';
            $val['comment'] = "Create TXT entry with " . $ret['sub'] . " go to " . $ret['target'];
            $val['entry_new']['type'] = 'TXT';
            $val['entry_new']['sub'] = $ret['sub'];
            $val['entry_new']['value'] = $ret['target'];
        } else {

            // WTF can it be ?
            $val['comment'] = "Unknow: $zone";
        }

        if ($save) {
            return $this->import_manual_dns_entry_doit($val);
        }

        return $val;
    }

    private function import_manual_dns_entry_doit($entry) {
        global $err;
        $entry['did_it'] = 0;
        if ($entry['status'] == 'err') {
            return $entry;
        }

        $val = $entry['entry_new'];

        if (empty($val['type']))
            return false;

        switch ($val['type']) {
            case "set_ttl":
                $entry['did_it'] = $this->set_ttl($this->get_domain_byname($val['domain']), $val['value']);
                return $entry;
                break;
        }

        // If it is an unknown domains type
        if (!array_key_exists(strtolower($val['type']), $this->domains_type_lst())) {
            echo "what is this shit ?\n";
            print_r($entry);
            return $entry;
        }

        // If the subdomain is @, we want an empty subdomain
        if ($val['sub'] == '@')
            $val['sub'] = '';

        $this->lock();
        $entry['did_it'] = $this->set_sub_domain($val['domain'], $val['sub'], $val['type'], $val['value']);
        $this->unlock();

        return $entry;
    }

    private function import_manual_dns_prep_zone($domain) {
        global $err;
        // Prepare a domain to be importer : 
        // * create the domain
        // * delete all automatic subdomain
        // * set no mx
        $this->lock();

        // function add_domain($domain,$dns,$noerase=0,$force=0,$isslave=0,$slavedom="") 
        if (!$this->add_domain($domain, true, false, true)) {
            $err->raise('dom', "Error adding domain");
            return false;
        }

        // Set no mx
        $this->edit_domain($domain, true, false);

        $d = $this->get_domain_all($domain);
        foreach ($d['sub'] as $sd) {
            $this->del_sub_domain($sd['id']);
        }

        $this->unlock();

        return true;
    }

    // Take an URL, and return FALSE is there is no redirection,
    // and the target URL if there is one (HTTP CODE 301 & 302)
    // CURL is needed

    /**
     * @param string $url
     */
    function is_it_a_redirect($url) {
        try {
            $params = array('http' => array(
                    'method' => 'HEAD',
                    'ignore_errors' => true
            ));

            $context = stream_context_create($params);
            $fp = @fopen($url, 'rb', false, $context);
            $result = @stream_get_contents($fp);

            if ($result === false) {
                throw new Exception("Could not read data from {$url}");
                return false;
            }
            if (strstr($http_response_header[0], '301') || strstr($http_response_header[0], '302')) {
                // This is a redirection
                if (preg_match('/Location:\h+(?P<target>[\-\w:\/.\?\=.]+)/', implode("\n", $http_response_header), $ret)) {
                    // check if it is a redirection to himself
                    preg_match('/\/\/(?P<host>[\w\.\-]+)\//', ( substr($url, -1) == '/' ? $url : $url . '/'), $original_cname);
                    preg_match('/\/\/(?P<host>[\w\.\-]+)\//', $ret['target'], $target_url);
                    if (isset($target_url['host']) && ( $target_url['host'] == $original_cname['host'] )) { // if it's a redirection to himself (sub pages, http to https...)
                        return false; // do not do a redirection (we must point to the server)
                    }

                    // If it is a redirection to a sub directory
                    // (we know it is a redirection to a sub directory because it's not a complete URI)
                    if (substr($ret['target'], 0, 4) != 'http') {
                        return 'http://' . $original_cname['host'] . '/' . $ret['target'];
                    }
                    return $ret['target'];
                }
            } else { // it isn't a redirection 
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    function domains_type_regenerate($name) {
        global $db, $err, $cuid;
        $name = mysql_real_escape_string($name);
        $db->query("update sub_domaines set web_action='UPDATE' where lower(type) = lower('$name') ;");
        $db->query("update domaines d, sub_domaines sd set d.dns_action = 'UPDATE' where lower(sd.type)=lower('$name');");
        return true;
    }

    function domains_type_get($name) {
        global $db, $err, $cuid;
        $name = mysql_real_escape_string($name);
        $db->query("select * from domaines_type where name='$name' ;");
        $db->next_record();
        return $db->Record;
    }

    function domains_type_del($name) {
        global $db, $err, $cuid;
        $name = mysql_real_escape_string($name);
        $db->query("delete domaines_type where name='$name';");
        return true;
    }

    function domains_type_update($name, $description, $target, $entry, $compatibility, $enable, $only_dns, $need_dns, $advanced, $create_tmpdir, $create_targetdir) {
        global $err, $cuid, $db;
        // The name MUST contain only letter and digits, it's an identifier after all ...
        if (!preg_match("#^[a-z0-9]+$#", $name)) {
            $err->raise("dom", _("The name MUST contain only letter and digits"));
            return false;
        }
        $name = mysql_real_escape_string($name);
        $description = mysql_real_escape_string($description);
        $target = mysql_real_escape_string($target);
        $entry = mysql_real_escape_string($entry);
        $compatibility = mysql_real_escape_string($compatibility);
        $enable = mysql_real_escape_string($enable);
        $only_dns = intval($only_dns);
        $need_dns = intval($need_dns);
        $advanced = intval($advanced);
        $create_tmpdir = intval($create_tmpdir);
        $create_targetdir = intval($create_targetdir);
        $db->query("UPDATE domaines_type SET description='$description', target='$target', entry='$entry', compatibility='$compatibility', enable='$enable', need_dns=$need_dns, only_dns=$only_dns, advanced='$advanced',create_tmpdir=$create_tmpdir,create_targetdir=$create_targetdir where name='$name';");
        return true;
    }

    function sub_domain_change_status($sub_id, $status) {
        global $db, $err, $cuid;
        $err->log("dom", "sub_domain_change_status");
        $sub_id = intval($sub_id);
        $status = strtoupper($status);
        if (!in_array($status, array('ENABLE', 'DISABLE')))
            return false;

        $jh = $this->get_sub_domain_all($sub_id);
        if ($status == 'ENABLE') { // check compatibility with existing sub_domains
            if (!$this->can_create_subdomain($jh['domain'], $jh['name'], $jh['type'], $sub_id)) {
                $err->raise("dom", _("The parameters for this subdomain and domain type are invalid. Please check for subdomain entries incompatibility"));
                return false;
            }
        }

        $db->query("update sub_domaines set enable='$status' where id = '" . intval($sub_id) . "';");
        $this->set_dns_action($jh['domain'], 'UPDATE');

        return true;
    }

    /* ----------------------------------------------------------------- */

    /**
     * Retourne un tableau contenant les domaines d'un membre.
     * Par défaut le membre connecté
     *
     * @return array retourne un tableau indexé contenant la liste des
     *  domaines hébergés sur le compte courant. Retourne FALSE si une
     *  erreur s'est produite.
     */
    function enum_domains($uid = -1) {
        global $db, $err, $cuid;
        $err->log("dom", "enum_domains");
        if ($uid == -1) {
            $uid = $cuid;
        }
        $db->query("SELECT * FROM domaines WHERE compte='{$uid}' ORDER BY domaine ASC;");
        $this->domains = array();
        if ($db->num_rows() > 0) {
            while ($db->next_record()) {
                $this->domains[] = $db->f("domaine");
            }
        }
        return $this->domains;
    }

    function del_domain_cancel($dom) {
        global $db, $err, $classes, $cuid;
        $err->log("dom", "del_domaini_canl", $dom);
        $dom = strtolower($dom);
        $db->query("UPDATE sub_domaines SET web_action='UPDATE'  WHERE domaine='$dom';");
        $this->set_dns_action($dom, 'UPDATE');

        # TODO : some work with domain sensitive classes

        return true;
    }

    /* ----------------------------------------------------------------- */

    /**
     *  Efface un domaine du membre courant, et tous ses sous-domaines
     *
     * Cette fonction efface un domaine et tous ses sous-domaines, ainsi que
     * les autres services attachés é celui-ci. Elle appelle donc les autres
     * classe. Chaque classe peut déclarer une fonction del_dom qui sera
     * appellée lors de la destruction d'un domaine.
     *
     * @param string $dom nom de domaine é effacer
     * @return boolean Retourne FALSE si une erreur s'est produite, TRUE sinon.
     */
    function del_domain($dom) {
        global $db, $err, $classes, $cuid, $hooks;
        $err->log("dom", "del_domain", $dom);
        $dom = strtolower($dom);

        $this->lock();
        if (!$r = $this->get_domain_all($dom)) {
            return false;
        }
        $this->unlock();

        // Call Hooks to delete the domain and the MX management:
        // TODO : the 2 calls below are using an OLD hook call, FIXME: remove them when unused
        $hooks->invoke("alternc_del_domain", array($dom));
        $hooks->invoke("alternc_del_mx_domain", array($dom));
        // New hook calls: 
        $hooks->invoke("hook_dom_del_domain", array($r["id"]));
        $hooks->invoke("hook_dom_del_mx_domain", array($r["id"]));

        // Now mark the domain for deletion:
        $db->query("UPDATE sub_domaines SET web_action='DELETE'  WHERE domaine='$dom';");
        $this->set_dns_action($dom, 'DELETE');

        return true;
    }

    function domshort($dom, $sub = "") {
        return str_replace("-", "", str_replace(".", "", empty($sub) ? "" : "$sub.") . $dom);
    }

    /* ----------------------------------------------------------------- */

    /**
     *  Installe un domaine sur le compte courant.
     *
     * <p>Si le domaine existe déjé ou est interdit, ou est celui du serveur,
     * l'installation est refusée. Si l'hébergement DNS est demandé, la fonction
     * checkhostallow vérifiera que le domaine peut étre installé conformément
     * aux demandes des super-admin.
     * Si le dns n'est pas demandé, le domaine peut étre installé s'il est en
     * seconde main d'un tld (exemple : test.eu.org ou test.com, mais pas
     * toto.test.org ou test.test.asso.fr)</p>
     * <p>Chaque classe peut définir une fonction add_dom($dom) qui sera
     * appellée lors de l'installation d'un nouveau domaine.</p>
     *
     * @param boolean $dns 1 ou 0 pour héberger le DNS du domaine ou pas.
     * @param boolean $noerase 1 ou 0 pour rendre le domaine inamovible ou non
     * @param boolean $force 1 ou 0, si 1, n'effectue pas les tests de DNS.
     *  force ne devrait étre utilisé que par le super-admin.
      $ @return boolean Retourne FALSE si une erreur s'est produite, TRUE sinon.
     */
    function add_domain($domain, $dns, $noerase = false, $force = false, $isslave = false, $slavedom = "") {
        global $db, $err, $quota, $classes, $L_MX, $L_FQDN, $tld, $cuid, $bro, $hooks;
        $err->log("dom", "add_domain", $domain);

        // Locked ?
        if (!$this->islocked) {
            $err->raise("dom", _("--- Program error --- No lock on the domains!"));
            return false;
        }
        // Verifie que le domaine est rfc-compliant
        $domain = strtolower($domain);
        $t = checkfqdn($domain);
        if ($t) {
            $err->raise("dom", _("The domain name is syntaxically incorrect"));
            return false;
        }
        // Interdit les domaines clés (table forbidden_domains) sauf en cas FORCE
        $db->query("SELECT domain FROM forbidden_domains WHERE domain='$domain'");
        if ($db->num_rows() && !$force) {
            $err->raise("dom", _("The requested domain is forbidden in this server, please contact the administrator"));
            return false;
        }
        if ($domain == $L_FQDN || $domain == "www.$L_FQDN") {
            $err->raise("dom", _("This domain is the server's domain! You cannot host it on your account!"));
            return false;
        }
        $db->query("SELECT compte FROM domaines WHERE domaine='$domain';");
        if ($db->num_rows()) {
            $err->raise("dom", _("The domain already exist"));
            return false;
        }
        $db->query("SELECT compte FROM `sub_domaines` WHERE sub != \"\" AND concat( sub, \".\", domaine )='$domain' OR domaine='$domain';");
        if ($db->num_rows()) {
            $err->raise("dom", _("The domain already exist"));
            return false;
        }
        $this->dns = $this->whois($domain);
        if (!$force) {
            $v = checkhostallow($domain, $this->dns);
            if ($v == -1) {
                $err->raise("dom", _("The last member of the domain name is incorrect or cannot be hosted in that server"));
                return false;
            }
            if ($dns && $v == -2) {
                $err->raise("dom", _("The domain cannot be found in the whois database"));
                return false;
            }
            if ($dns && $v == -3) {
                $err->raise("dom", _("The domain cannot be found in the whois database"));
                return false;
            }

            if ($dns)
                $dns = "1";
            else
                $dns = "0";

            // mode 5 : force DNS to NO.
            if ($tld[$v] == 5)
                $dns = 0;
            // It must be a real domain (no subdomain)
            if (!$dns) {
                $v = checkhostallow_nodns($domain);
                if ($v) {
                    $err->raise("dom", _("The requested domain is forbidden in this server, please contact the administrator"));
                    return false;
                }
            }
        }
        // Check the quota :
        if (!$quota->cancreate("dom")) {
            $err->raise("dom", _("Your domain quota is over, you cannot create more domain names"));
            return false;
        }
        if ($noerase)
            $noerase = "1";
        else
            $noerase = "0";
        if ($dns)
            $gesmx = "1";
        else
            $gesmx = "0"; // do not host mx by default if not hosting the DNS
        $db->query("INSERT INTO domaines (compte,domaine,gesdns,gesmx,noerase,dns_action) VALUES ('$cuid','$domain','$dns','$gesmx','$noerase','UPDATE');");
        if (!($id = $db->lastid())) {
            $err->raise("dom", _("An unexpected error occured when creating the domain"));
            return false;
        }

        if ($isslave) {
            $isslave = true;
            $db->query("SELECT domaine FROM domaines WHERE compte='$cuid' AND domaine='$slavedom';");
            $db->next_record();
            if (!$db->Record["domaine"]) {
                $err->raise("dom", _("Domain '%s' not found"), $slavedom);
                $isslave = false;
            }
            // Point to the master domain : 
            $this->create_default_subdomains($domain, $slavedom);
        }
        if (!$isslave) {
            $this->create_default_subdomains($domain);
        }

        // TODO: Old hooks, FIXME: when unused remove them
        $hooks->invoke("alternc_add_domain", array($domain));
        if ($isslave) {
            $hooks->invoke("alternc_add_slave_domain", array($domain));
        }
        // New Hooks: 
        $hooks->invoke("hook_dom_add_domain", array($id));
        if ($gesmx)
            $hooks->invoke("hook_dom_add_mx_domain", array($id));
        if ($isslave) {
            $hooks->invoke("hook_dom_add_slave_domain", array($id, $slavedom));
        }
        return true;
    }

    /**
     * @param string $domain
     */
    function create_default_subdomains($domain, $target_domain = "") {
        global $db, $err;
        $err->log("dom", "create_default_subdomains", $domain);
        $query = "SELECT sub, domain_type, domain_type_parameter FROM default_subdomains WHERE (concerned = 'SLAVE' or concerned = 'BOTH') and enabled=1;";
        if (empty($target_domain)) {
            $query = "SELECT sub, domain_type, domain_type_parameter FROM default_subdomains WHERE (concerned = 'MAIN' or concerned = 'BOTH') and enabled=1;";
        }
        $domaindir = $this->domdefaultdir($domain);
        $db->query($query);
        $jj = array();
        while ($db->next_record()) {
            $jj[] = Array("domain_type_parameter" => $db->f('domain_type_parameter'), "sub" => $db->f('sub'), "domain_type" => $db->f('domain_type'));
        }
        $src_var = array("%%SUB%%", "%%DOMAIN%%", "%%DOMAINDIR%%", "%%TARGETDOM%%");
        foreach ($jj as $j) {
            $trg_var = array($j['sub'], $domain, $domaindir, $target_domain);
            $domain_type_parameter = str_ireplace($src_var, $trg_var, $j['domain_type_parameter']);
            $this->set_sub_domain($domain, $j['sub'], strtolower($j['domain_type']), $domain_type_parameter);
        }
    }

    /**
     * @param string $domain
     */
    function domdefaultdir($domain) {
        global $bro, $cuid;
        $dest_root = $bro->get_userid_root($cuid);
        #  return $dest_root."/www/".$this->domshort($domain);
        return "/www/" . $this->domshort($domain);
    }

    function dump_axfr($domain, $ns = 'localhost') {
        exec('/usr/bin/dig AXFR "' . escapeshellcmd($domain) . '" @"' . escapeshellcmd($ns) . '"', $axfr);
        return $axfr;
    }

    function lst_default_subdomains() {
        global $db, $err;
        $err->log("dom", "lst_default_subdomains");
        $c = array();
        $db->query("select * from default_subdomains;");

        while ($db->next_record()) {
            $c[] = array('id' => $db->f('id'),
                'sub' => $db->f('sub'),
                'domain_type' => $db->f('domain_type'),
                'domain_type_parameter' => $db->f('domain_type_parameter'),
                'concerned' => $db->f('concerned'),
                'enabled' => $db->f('enabled')
                    );
        }

        return $c;
    }

    function update_default_subdomains($arr) {
        global $err;
        $err->log("dom", "update_default_subdomains");
        $ok = true;
        foreach ($arr as $a) {
            if (!isset($a['id']))
                $a['id'] = null;
            if (!empty($a['sub']) || !empty($a['domain_type_parameter'])) {

                if (!isset($a['enabled']))
                    $a['enabled'] = 0;
                if (!$this->update_one_default($a['domain_type'], $a['sub'], $a['domain_type_parameter'], $a['concerned'], $a['enabled'], $a['id'])) {
                    $ok = false;
                }
            }
        }
        return $ok;
    }

    function update_one_default($domain_type, $sub, $domain_type_parameter, $concerned, $enabled, $id = null) {
        global $db, $err;
        $err->log("dom", "update_one_default");

        if ($id == null)
            $db->query("INSERT INTO default_subdomains values ('','" . addslashes($sub) . "','" . addslashes($domain_type) . "','" . addslashes($domain_type_parameter) . "','" . addslashes($concerned) . "','" . addslashes($enabled) . "');");
        else
            $db->query("UPDATE default_subdomains set sub='" . addslashes($sub) . "', domain_type='" . addslashes($domain_type) . "',domain_type_parameter='" . addslashes($domain_type_parameter) . "',concerned='" . addslashes($concerned) . "',enabled='" . addslashes($enabled) . "' where id=" . addslashes($id) . ";");
        return true;
        //update
    }

    function del_default_type($id) {
        global $err, $db;
        $err->log("dom", "del_default_type");

        if (!$db->query("delete from default_subdomains where id=$id;")) {
            $err->raise("dom", _("Could not delete default type"));
            return false;
        }

        return true;
    }

    /* ----------------------------------------------------------------- */

    /**
     * Retourne les entrées DNS du domaine $domain issues du WHOIS.
     *
     * Cette fonction effectue un appel WHOIS($domain) sur Internet,
     * et extrait du whois les serveurs DNS du domaine demandé. En fonction
     * du TLD, on sait (ou pas) faire le whois correspondant.
     * Actuellement, les tld suivants sont supportés :
     * .com .net .org .be .info .ca .cx .fr .biz .name
     *
     * @param string $domain Domaine fqdn dont on souhaite les serveurs DNS
     * @return array Retourne un tableau indexé avec les NOMS fqdn des dns
     *   du domaine demandé. Retourne FALSE si une erreur s'est produite.
     *
     */
    function whois($domain) {
        global $db, $err;
        $err->log("dom", "whois", $domain);
        // pour ajouter un nouveau TLD, utiliser le code ci-dessous.
        //  echo "whois : $domain<br />";
        preg_match("#.*\.([^\.]*)#", $domain, $out);
        $ext = $out[1];
        // pour ajouter un nouveau TLD, utiliser le code ci-dessous.
        //  echo "ext: $ext<br />";

        $serveur = "";
        if (($fp = @fsockopen("whois.iana.org", 43)) > 0) {
            fputs($fp, "$domain\r\n");
            $found = false;
            $state = 0;
            while (!feof($fp)) {
                $ligne = fgets($fp, 128);
                if (preg_match('#^whois:#', $ligne)) {
                    $serveur = preg_replace('/whois:\ */', '', $ligne, 1);
                }
            }
        }
        $serveur = str_replace(array(" ", "\n"), "", $serveur);

        $egal = "";
        switch ($ext) {
            case "net":
                $egal = "=";
                break;
            case "name":
                $egal = "domain = ";
                break;
        }
        $serveurList = array();
        // pour ajouter un nouveau TLD, utiliser le code ci-dessous.
        //  echo "serveur : $serveur <br />";
        if (($fp = @fsockopen($serveur, 43)) > 0) {
            fputs($fp, "$egal$domain\r\n");
            $found = false;
            $state = 0;
            while (!feof($fp)) {
                $ligne = fgets($fp, 128);
                // pour ajouter un nouveau TLD, utiliser le code ci-dessous.
                //  echo "| $ligne<br />";
                switch ($ext) {
                    case "org":
                    case "com":
                    case "net":
                    case "info":
                    case "biz":
                    case "name":
                    case "cc":
                        if (preg_match("#Name Server:#", $ligne)) {
                            $found = true;
                            $tmp = strtolower(str_replace(chr(10), "", str_replace(chr(13), "", str_replace(" ", "", str_replace("Name Server:", "", $ligne)))));
                            if ($tmp)
                                $serveurList[] = $tmp;
                        }
                        break;
                    case "cx":
                        $ligne = str_replace(chr(10), "", str_replace(chr(13), "", str_replace(" ", "", $ligne)));
                        if ($ligne == "" && $state == 1)
                            $state = 2;
                        if ($state == 1)
                            $serveurList[] = strtolower($ligne);
                        if ($ligne == "Nameservers:" && $state == 0) {
                            $state = 1;
                            $found = true;
                        }
                        break;
                    case "eu":
                    case "be":
                        $ligne = preg_replace("/^ *([^ ]*) \(.*\)$/", "\\1", trim($ligne));
                        if ($found)
                            $tmp = trim($ligne);
                        if ($tmp)
                            $serveurList[] = $tmp;
                        if ($ligne == "Nameservers:") {
                            $state = 1;
                            $found = true;
                        }
                        break;
                    case "im":
                        if (preg_match('/Name Server:/', $ligne)) {
                            $found = true;
                            // weird regexp (trailing garbage after name server), but I could not make it work otherwise
                            $tmp = strtolower(preg_replace('/Name Server: ([^ ]+)\..$/', "\\1", $ligne));
                            $tmp = preg_replace('/[^-_a-z0-9\.]/', '', $tmp);
                            if ($tmp)
                                $serveurList[] = $tmp;
                        }
                        break;
                    case "it":
                        if (preg_match("#nserver:#", $ligne)) {
                            $found = true;
                            $tmp = strtolower(preg_replace("/nserver:\s*[^ ]*\s*([^\s]*)$/", "\\1", $ligne));
                            if ($tmp)
                                $serveurList[] = $tmp;
                        }
                        break;
                    case "fr":
                    case "re":
                        if (preg_match("#nserver:#", $ligne)) {
                            $found = true;
                            $tmp = strtolower(preg_replace("#nserver:\s*([^\s]*)\s*.*$#", "\\1", $ligne));
                            if ($tmp)
                                $serveurList[] = $tmp;
                        }
                        break;
                    case "ca":
                    case "ws";
                        if (preg_match('#Name servers#', $ligne)) {
                            // found the server
                            $state = 1;
                        } elseif ($state) {
                            if (preg_match('#^[^%]#', $ligne) && $ligne = preg_replace('#[[:space:]]#', "", $ligne)) {
                                // first non-whitespace line is considered to be the nameservers themselves
                                $found = true;
                                $serveurList[] = $ligne;
                            }
                        }
                        break;
                    case "coop":
                        if (preg_match('#Host Name:\s*([^\s]+)#', $ligne, $matches)) {
                            $found = true;
                            $serveurList[] = $matches[1];
                        }
                } // switch
            } // while
            fclose($fp);
        } else {
            $err->raise("dom", _("The Whois database is unavailable, please try again later"));
            return false;
        }

        if ($found) {
            return $serveurList;
        } else {
            $err->raise("dom", _("The domain cannot be found in the Whois database"));
            return false;
        }
    }

// whois


    /* ----------------------------------------------------------------- */

    /**
     *  vérifie la presence d'un champs mx valide sur un serveur DNS
     * $domaine est le domaine dont on veux véririfer les MX
     * $ref_domaine est le domaine avec lequel on veux comparer les MX
     *              si $ref_domaine == '', on prend les MX par default
     *
     * @param string $domaine
     */
    function checkmx($domaine, $ref_domain = '') {
        global $L_DEFAULT_MX, $L_DEFAULT_SECONDARY_MX;

        if (!empty($ref_domain)) {
            getmxrr($ref_domain, $ref_mx);
        } else {
            $ref_mx = array($L_DEFAULT_MX, $L_DEFAULT_SECONDARY_MX);
        }

        if (empty($ref_mx)) {
            // No reference mx
            return 3;
        }

        //récupére les champs mx
        if (!getmxrr($domaine, $mxhosts)) {
            //aucun héte mx spécifié
            return 1;
        }

        if (empty($mxhosts)) {
            // no mx on the target domaine
            return 1;
        }

        $intersect = array_intersect($mxhosts, $ref_mx);

        if (empty($intersect)) {
            // no shared mx server
            return 2;
        }

        return 0;
    }

//checkmx


    /* ----------------------------------------------------------------- */

    /**
     *  retourne TOUTES les infos d'un domaine
     *
     * @param string $dom Domaine dont on souhaite les informations
     * @return array Retourne toutes les infos du domaine sous la forme d'un
     * tableau associatif comme suit :<br /><pre>
     *  $r["name"] =  Nom fqdn
     *  $r["dns"]  =  Gestion du dns ou pas ?
     *  $r["mx"]   =  Valeur du champs MX si "dns"=true
     *  $r["mail"] =  Heberge-t-on le mail ou pas ? (si "dns"=false)
     *  $r["nsub"] =  Nombre de sous-domaines
     *  $r["sub"]  =  tableau associatif des sous-domaines
     *  $r["sub"][0-(nsub-1)]["name"] = nom du sous-domaine (NON-complet)
     *  $r["sub"][0-(nsub-1)]["dest"] = Destination (url, ip, local ...)
     *  $r["sub"][0-(nsub-1)]["type"] = Type (0-n) de la redirection.
     *  </pre>
     *  Retourne FALSE si une erreur s'est produite.
     *
     */
    function get_domain_all($dom) {
        global $db, $err, $cuid;
        $err->log("dom", "get_domain_all", $dom);
        // Locked ?
        if (!$this->islocked) {
            $err->raise("dom", _("--- Program error --- No lock on the domains!"));
            return false;
        }
        $t = checkfqdn($dom);
        if ($t) {
            $err->raise("dom", _("The domain name is syntaxically incorrect"));
            return false;
        }
        $r = array();
        $r["name"] = $dom;
        $db->query("SELECT * FROM domaines WHERE compte='$cuid' AND domaine='$dom'");
        if ($db->num_rows() == 0) {
            $err->raise("dom", sprintf(_("Domain '%s' not found"), $dom));
            return false;
        }
        $db->next_record();
        $r["id"] = $db->Record["id"];
        $r["dns"] = $db->Record["gesdns"];
        $r["dns_action"] = $db->Record["dns_action"];
        $r["dns_result"] = $db->Record["dns_result"];
        $r["mail"] = $db->Record["gesmx"];
        $r["zonettl"] = $db->Record["zonettl"];
        $r['noerase'] = $db->Record['noerase'];
        $db->free();
        $db->query("SELECT COUNT(*) AS cnt FROM sub_domaines WHERE compte='$cuid' AND domaine='$dom'");
        $db->next_record();
        $r["nsub"] = $db->Record["cnt"];
        $db->free();
        #$db->query("SELECT sd.*, dt.description AS type_desc, dt.only_dns FROM sub_domaines sd, domaines_type dt WHERE compte='$cuid' AND domaine='$dom' AND UPPER(dt.name)=UPPER(sd.type) ORDER BY sd.sub,sd.type");
        $db->query("SELECT sd.*, dt.description AS type_desc, dt.only_dns FROM sub_domaines sd LEFT JOIN domaines_type dt on  UPPER(dt.name)=UPPER(sd.type) WHERE compte='$cuid' AND domaine='$dom'  ORDER BY sd.sub,sd.type ;");
        // Pas de webmail, on le cochera si on le trouve.
        $r["sub"] = array();
        for ($i = 0; $i < $r["nsub"]; $i++) {
            $db->next_record();
            $r["sub"][$i] = array();
            $r["sub"][$i]["id"] = $db->Record["id"];
            $r["sub"][$i]["name"] = $db->Record["sub"];
            $r["sub"][$i]["dest"] = $db->Record["valeur"];
            $r["sub"][$i]["type"] = $db->Record["type"];
            $r["sub"][$i]["enable"] = $db->Record["enable"];
            $r["sub"][$i]["type_desc"] = $db->Record["type_desc"];
            $r["sub"][$i]["only_dns"] = $db->Record["only_dns"];
            $r["sub"][$i]["web_action"] = $db->Record["web_action"];
            $r["sub"][$i]["fqdn"] = ((!empty($r["sub"][$i]["name"])) ? $r["sub"][$i]["name"] . "." : "") . $r["name"];
        }
        $db->free();
        return $r;
    }

// get_domain_all


    /* ----------------------------------------------------------------- */

    /**
     * Retourne TOUTES les infos d'un sous domaine du compte courant.
     *
     * @param integer sub_domain_id id du subdomain
     * @return array Retourne un tableau associatif contenant les
     *  informations du sous-domaine demandé.<pre>
     *  $r["name"]= nom du sous-domaine (NON-complet)
     *  $r["dest"]= Destination (url, ip, local ...)
     *  </pre>
     *  $r["type"]= Type (0-n) de la redirection.
     *  Retourne FALSE si une erreur s'est produite.
     */
    function get_sub_domain_all($sub_domain_id) {
        global $db, $err, $cuid;
        $err->log("dom", "get_sub_domain_all", $sub_domain_id);
        // Locked ?
        if (!$this->islocked) {
            $err->raise("dom", _("--- Program error --- No lock on the domains!"));
            return false;
        }
        $db->query("select sd.*, dt.description as type_desc, dt.only_dns from sub_domaines sd, domaines_type dt where compte='$cuid' and sd.id='$sub_domain_id'  and upper(dt.name)=upper(sd.type);");
        if ($db->num_rows() == 0) {
            $err->raise("dom", _("The sub-domain does not exist"));
            return false;
        }
        $db->next_record();
        $r = array();
        $r["id"] = $db->Record["id"];
        $r["name"] = $db->Record["sub"];
        $r["domain"] = $db->Record["domaine"];
        $r["dest"] = $db->Record["valeur"];
        $r["enable"] = $db->Record["enable"];
        $r["type"] = $db->Record["type"];
        $r["type_desc"] = $db->Record["type_desc"];
        $r["only_dns"] = $db->Record["only_dns"];
        $r["web_action"] = $db->Record["web_action"];
        $db->free();
        return $r;
    }

// get_sub_domain_all

    /**
     * @param integer $type
     * @param string $value
     */
    function check_type_value($type, $value) {
        global $db, $err, $cuid;

        // check the type we can have in domaines_type.target
        switch ($this->domains_type_target_values($type)) {
            case 'NONE':
                if (empty($value) or is_null($value)) {
                    return true;
                }
                break;
            case 'URL':
                if ($value == strval($value)) {
                    if (filter_var($value, FILTER_VALIDATE_URL)) {
                        return true;
                    } else {
                        $err->raise("dom", _("invalid url"));
                        return false;
                    }
                }
                break;
            case 'DIRECTORY':
                if (substr($value, 0, 1) != "/") {
                    $value = "/" . $value;
                }
                if (!checkuserpath($value)) {
                    $err->raise("dom", _("The folder you entered is incorrect or does not exist"));
                    return false;
                }
                return true;
                break;
            case 'IP':
                if (checkip($value)) {
                    return true;
                } else {
                    $err->raise("dom", _("The ip address is invalid"));
                    return false;
                }
                break;
            case 'IPV6':
                if (checkipv6($value)) {
                    return true;
                } else {
                    $err->raise("dom", _("The ip address is invalid"));
                    return false;
                }
                break;
            case 'DOMAIN':
                if (checkcname($value)) {
                    return true;
                } else {
                    $err->raise("dom", _("The name you entered is incorrect"));
                    return false;
                }
                break;
            case 'TXT':
                if ($value == strval($value)) {
                    return true;
                } else {
                    $err->raise("dom", _("The TXT value you entered is incorrect"));
                    return false;
                }
                break;
            default:
                $err->raise("dom", _("Invalid domain type selected, please check"));
                return false;
                break;
        }
        return false;
    }

//check_type_value


    /* ----------------------------------------------------------------- */

    /**
     * Check the compatibility of the POSTed parameters with the chosen
     * domain type
     *
     * @param string $dom FQDN of the domain name
     * @param string $sub SUBdomain 
     * @return boolean tell you if the subdomain can be installed there 
     */
    function can_create_subdomain($dom, $sub, $type, $sub_domain_id = 'null') {
        global $db, $err, $cuid;
        $err->log("dom", "can_create_subdomain", $dom . "/" . $sub);

        // Get the compatibility list for this domain type
        $db->query("select upper(compatibility) as compatibility from domaines_type where upper(name)=upper('$type');");
        if (!$db->next_record())
            return false;
        $compatibility_lst = explode(",", $db->f('compatibility'));

        // Get the list of type of subdomains already here who have the same name
        $db->query("select * from sub_domaines where sub='$sub' and domaine='$dom' and not id = $sub_domain_id and web_action != 'DELETE' and enabled not in ('DISABLED', 'DISABLE') ");
        #$db->query("select * from sub_domaines where sub='$sub' and domaine='$dom';");
        while ($db->next_record()) {
            // And if there is a domain with a incompatible type, return false
            if (!in_array(strtoupper($db->f('type')), $compatibility_lst))
                return false;
        }

        // All is right, go ! Create ur domain !
        return true;
    }

    /* ----------------------------------------------------------------- */

    /**
     * Modifier les information du sous-domaine demandé.
     *
     * <b>Note</b> : si le sous-domaine $sub.$dom n'existe pas, il est créé.<br />
     * <b>Note : TODO</b> : vérification de concordance de $dest<br />
     *
     * @param string $dom Domaine dont on souhaite modifier/ajouter un sous domaine
     * @param string $sub Sous domaine é modifier / créer
     * @param integer $type Type de sous-domaine (local, ip, url ...)
     * @param string $dest Destination du sous-domaine, dépend de la valeur
     *  de $type (url, ip, dossier...)
     * @return boolean Retourne FALSE si une erreur s'est produite, TRUE sinon.
     */
    function set_sub_domain($dom, $sub, $type, $dest, $sub_domain_id = null) {
        global $db, $err, $cuid, $bro;
        $err->log("dom", "set_sub_domain", $dom . "/" . $sub . "/" . $type . "/" . $dest);
        // Locked ?
        if (!$this->islocked) {
            $err->raise("dom", _("--- Program error --- No lock on the domains!"));
            return false;
        }
        $dest = trim($dest);
        $sub = trim(trim($sub), ".");
        $dom = strtolower($dom);
        $sub = strtolower($sub);

        //    if (!(($sub == '*') || ($sub=="") || (preg_match('/([a-z0-9][\.\-a-z0-9]*)?[a-z0-9]/', $sub)))) {
        $fqdn = checkfqdn($sub);
        // Special cases : * (all subdomains at once) and '' empty subdomain are allowed.
        if (($sub != '*' && $sub != '') && !($fqdn == 0 || $fqdn == 4)) {
            $err->raise("dom", _("There is some forbidden characters in the sub domain (only A-Z 0-9 and - are allowed)"));
            return false;
        }

        if (!$this->check_type_value($type, $dest)) {
            //plutot verifier si la chaine d'erreur est vide avant de raise sinon sa veut dire que l(erruer est deja remonté
            #$err->raise("dom",_("Invalid domain type selected, please check"));
            return false;
        }

        // On a épuré $dir des problémes eventuels ... On est en DESSOUS du dossier de l'utilisateur.
        if ($t = checkfqdn($dom)) {
            $err->raise("dom", _("The domain name is syntaxically incorrect"));
            return false;
        }

        if (!$this->can_create_subdomain($dom, $sub, $type, $sub_domain_id)) {
            $err->raise("dom", _("The parameters for this subdomain and domain type are invalid. Please check for subdomain entries incompatibility"));
            return false;
        }

        if (!is_null($sub_domain_id)) { // It's not a creation, it's an edit. Delete the old one
            $this->del_sub_domain($sub_domain_id);
        }

        // Re-create the one we want
        if (!$db->query("replace into sub_domaines (compte,domaine,sub,valeur,type,web_action) values ('$cuid','$dom','$sub','$dest','$type','UPDATE');")) {
            echo "query failed: " . $db->Error;
            return false;
        }

        // Create TMP dir and TARGET dir if needed by the domains_type
        $dest_root = $bro->get_userid_root($cuid);
        $domshort = $this->domshort($dom, $sub);
        $db->query("select create_tmpdir, create_targetdir from domaines_type where name = '$type';");
        $db->next_record();
        if ($db->f('create_tmpdir')) {
            if (!is_dir($dest_root . "/tmp")) {
                if (!@mkdir($dest_root . "/tmp", 0777, true)) {
                    $err->raise("dom", _("Cannot write to the destination folder"));
                }
            }
        }
        if ($db->f('create_targetdir')) {
            $dirr = $dest_root . $dest;
            $dirr = str_replace('//', '/', $dirr);

            if (!is_dir($dirr)) {
                $old = umask(0);
                if (!@mkdir($dirr, 0770, true)) {
                    $err->raise("dom", _("Cannot write to the destination folder"));
                }
                umask($old);
            }
        }

        // Tell to update the DNS file
        $db->query("update domaines set dns_action='UPDATE' where domaine='$dom';");

        return true;
    }

// set_sub_domain


    /* ----------------------------------------------------------------- */

    /**
     *  Supprime le sous-domaine demandé
     *
     * @return boolean Retourne FALSE si une erreur s'est produite, TRUE sinon.
     *
     */
    function del_sub_domain($sub_domain_id) {
        global $db, $err, $cuid;
        $err->log("dom", "del_sub_domain", $sub_domain_id);
        // Locked ?
        if (!$this->islocked) {
            $err->raise("dom", _("--- Program error --- No lock on the domains!"));
            return false;
        }
        if (!$r = $this->get_sub_domain_all($sub_domain_id)) {
            $err->raise("dom", _("The sub-domain does not exist"));
            return false;
        } else {
            $db->query("update sub_domaines set web_action='DELETE' where id='$sub_domain_id'; ");
            $db->query("update domaines set dns_action='UPDATE' where domaine='" . $r['domain'] . "';");
        }
        return true;
    }

// del_sub_domain

    /**
     * @param integer $dom_id
     */
    function set_ttl($dom_id, $ttl) {
        global $err;
        $err->log("dom", "set_ttl", "$dom_id / $ttl");
        $this->lock();
        $domaine = $this->get_domain_byid($dom_id);
        $d = $this->get_domain_all($domaine);

        $j = $this->edit_domain($domaine, $d['dns'], $d['mail'], false, $ttl);
        $this->unlock();
        return $j;
    }

    /* ----------------------------------------------------------------- */

    /**
     * Modifie les information du domaine précisé.
     *
     * @param string $dom Domaine du compte courant que l'on souhaite modifier
     * @param boolean $dns Vaut 1 ou 0 pour héberger ou pas le DNS du domaine
     * @param boolean $gesmx Héberge-t-on le emails du domaines sur ce serveur ?
     * @param boolean $force Faut-il passer les checks DNS ou MX ? (admin only)
     * @return boolean appelle $mail->add_dom ou $ma->del_dom si besoin, en
     *  fonction du champs MX. Retourne FALSE si une erreur s'est produite,
     *  TRUE sinon.
     *
     */
    function edit_domain($dom, $dns, $gesmx, $force = false, $ttl = 86400) {
        global $db, $err, $L_MX, $classes, $cuid, $hooks;
        $err->log("dom", "edit_domain", $dom . "/" . $dns . "/" . $gesmx);
        // Locked ?
        if (!$this->islocked && !$force) {
            $err->raise("dom", _("--- Program error --- No lock on the domains!"));
            return false;
        }
        if ($dns == true && !$force) {
            $this->dns = $this->whois($dom);
            $v = checkhostallow($dom, $this->dns);
            if ($v == -1) {
                $err->raise("dom", _("The last member of the domain name is incorrect or cannot be hosted in that server"));
                return false;
            }
            if ($dns && $v == -2) {
                $err->raise("dom", _("The domain cannot be found in the Whois database"));
                return false;
            }
            if ($dns && $v == -3) {
                $err->raise("dom", _("The DNS of this domain do not match the server's DNS. Please change your domain's DNS before you install it again"));
                return false;
            }
        }

        # Can't have ttl == 0. There is also a check in function_dns
        if ($ttl == 0) {
            $ttl = 86400;
        }

        $t = checkfqdn($dom);
        if ($t) {
            $err->raise("dom", _("The domain name is syntaxically incorrect"));
            return false;
        }
        if (!$r = $this->get_domain_all($dom)) {
            // Le domaine n'existe pas, Failure
            $err->raise("dom", _("The domain name %s does not exist"), $dom);
            return false;
        }
        if ($dns != "1")
            $dns = "0";
        // On vérifie que des modifications ont bien eu lieu :)
        if ($r["dns"] == $dns && $r["mail"] == $gesmx && $r["zonettl"] == $ttl) {
            $err->raise("dom", _("No change has been requested..."));
            return true;
        }

        //si gestion mx uniquement, vérification du dns externe
        if ($dns == "0" && $gesmx == "1" && !$force) {
            $vmx = $this->checkmx($dom);
            if ($vmx == 1) {
                $err->raise("dom", _("There is no MX record pointing to this server, and you are asking us to host the mail here. Make sure to update your MX entries or no mail will be received"));
            }

            if ($vmx == 2) {
                // Serveur non spécifié parmi les champx mx
                $err->raise("dom", _("There is no MX record pointing to this server, and you are asking us to host the mail here. Make sure to update your MX entries or no mail will be received"));
            }
        }

        if ($gesmx && !$r["mail"]) {
            $hooks->invoke("hook_dom_add_mx_domain", array($r["id"]));
        }

        if (!$gesmx && $r["mail"]) { // on a dissocié le MX : on détruit donc l'entree dans LDAP
            $hooks->invoke("hook_dom_del_mx_domain", array($r["id"]));
        }

        $db->query("UPDATE domaines SET gesdns='$dns', gesmx='$gesmx', zonettl='$ttl' WHERE domaine='$dom'");
        $this->set_dns_action($dom, 'UPDATE');

        return true;
    }

// edit_domain


    /*     * ************************* */
    /*  Slave dns ip managment  */
    /*     * ************************* */


    /* ----------------------------------------------------------------- */

    /** Return the list of ip addresses and classes that are allowed access to domain list
     * through AXFR Transfers from the bind server.
     */
    function enum_slave_ip() {
        global $db, $err;
        $db->query("SELECT * FROM slaveip;");
        if (!$db->next_record()) {
            return false;
        }
        $res = array();
        do {
            $res[] = $db->Record;
        } while ($db->next_record());
        return $res;
    }

    /* ----------------------------------------------------------------- */

    /** Add an ip address (or a ip class) to the list of allowed slave ip access list.
     */
    function add_slave_ip($ip, $class = "32") {
        global $db, $err;
        if (!checkip($ip)) {
            $err->raise("dom", _("The IP address you entered is incorrect"));
            return false;
        }
        $class = intval($class);
        if ($class < 8 || $class > 32)
            $class = 32;
        $db->query("SELECT * FROM slaveip WHERE ip='$ip' AND class='$class';");
        if ($db->next_record()) {
            $err->raise("err", _("The requested domain is forbidden in this server, please contact the administrator"));
            return false;
        }
        $db->query("INSERT INTO slaveip (ip,class) VALUES ('$ip','$class');");
        $f = fopen(SLAVE_FLAG, "w");
        fputs($f, "yopla");
        fclose($f);
        return true;
    }

    /* ----------------------------------------------------------------- */

    /** Remove an ip address (or a ip class) from the list of allowed slave ip access list.
     */
    function del_slave_ip($ip) {
        global $db, $err;
        if (!checkip($ip)) {
            $err->raise("dom", _("The IP address you entered is incorrect"));
            return false;
        }
        $db->query("DELETE FROM slaveip WHERE ip='$ip'");
        $f = fopen(SLAVE_FLAG, "w");
        fputs($f, "yopla");
        fclose($f);
        return true;
    }

    /* ----------------------------------------------------------------- */

    /** Check for a slave account
     */
    function check_slave_account($login, $pass) {
        global $db, $err;
        $db->query("SELECT * FROM slaveaccount WHERE login='$login' AND pass='$pass';");
        if ($db->next_record()) {
            return true;
        }
        return false;
    }

    /* ----------------------------------------------------------------- */

    /** Out (echo) the complete hosted domain list : 
     */
    function echo_domain_list($integrity = false) {
        global $db, $err;
        $db->query("SELECT domaine FROM domaines WHERE gesdns=1 ORDER BY domaine");
        $tt = "";
        while ($db->next_record()) {
            #echo $db->f("domaine")."\n";
            $tt.=$db->f("domaine") . "\n";
        }
        echo $tt;
        if ($integrity) {
            echo md5($tt) . "\n";
        }
        return true;
    }

    /* ----------------------------------------------------------------- */

    /** Returns the complete hosted domain list : 
     */
    function get_domain_list($uid = -1) {
        global $db, $err;
        $uid = intval($uid);
        $res = array();
        $sql = "";
        if ($uid != -1) {
            $sql .= " AND compte='$uid' ";
        }
        $db->query("SELECT domaine FROM domaines WHERE gesdns=1 $sql ORDER BY domaine");
        while ($db->next_record()) {
            $res[] = $db->f("domaine");
        }
        return $res;
    }

    /**
     * 
     * @return array
     */
    function get_domain_all_summary() {
        global $db, $err;
        $res = array();
        $db->query("SELECT domaine, gesdns, gesmx, dns_action, zonettl FROM domaines ORDER BY domaine");
        while ($db->next_record()) {
            $res[$db->f("domaine")] = array(
                "gesdns" => $db->f("gesdns"),
                "gesmx" => $db->f("gesmx"),
                "dns_action" => $db->f("dns_action"),
                "zonettl" => $db->f("zonettl"),
            );
        }
        return $res;
    }

    /* ----------------------------------------------------------------- */

    /** Returns the name of a domain for the current user, from it's domain_id
     * @param $dom_id integer the domain_id to search for
     * @return string the domain name, or false with an error raised.
     */
    function get_domain_byid($dom_id) {
        global $db, $err, $cuid;
        $dom_id = intval($dom_id);
        $db->query("SELECT domaine FROM domaines WHERE id=$dom_id AND compte=$cuid;");
        if ($db->next_record()) {
            $domain = $db->f("domaine");
            if (!$domain) {
                $err->raise("dom", _("This domain is not installed in your account"));
                return false;
            } else {
                return $domain;
            }
        } else {
            $err->raise("dom", _("This domain is not installed in your account"));
            return false;
        }
    }

    /* ----------------------------------------------------------------- */

    /** Returns the id of a domain for the current user, from it's domain name
     * @param $domain string the domain name to search for
     * @return integer the domain id, or false with an error raised.
     */
    function get_domain_byname($domain) {
        global $db, $err, $cuid;
        $domain = trim($domain);
        $db->query("SELECT id FROM domaines WHERE domaine='" . addslashes($domain) . "' AND compte=$cuid;");
        if ($db->next_record()) {
            $id = $db->f("id");
            if (!$id) {
                $err->raise("dom", _("This domain is not installed in your account"));
                return false;
            } else {
                return $id;
            }
        } else {
            $err->raise("dom", _("This domain is not installed in your account"));
            return false;
        }
    }

    /* ----------------------------------------------------------------- */

    /** Count all domains, for all users
     */
    function count_domains_all() {
        global $db, $err, $cuid;
        $db->query("SELECT COUNT(*) AS count FROM domaines;");
        if ($db->next_record()) {
            return $db->f('count');
        } else {
            return 0;
        }
    }

    /* ----------------------------------------------------------------- */

    /** Return the list of allowed slave accounts 
     */
    function enum_slave_account() {
        global $db, $err;
        $db->query("SELECT * FROM slaveaccount;");
        $res = array();
        while ($db->next_record()) {
            $res[] = $db->Record;
        }
        if (!count($res))
            return false;
        return $res;
    }

    /* ----------------------------------------------------------------- */

    /** Add a slave account that will be allowed to access the domain list
     */
    function add_slave_account($login, $pass) {
        global $db, $err;
        $db->query("SELECT * FROM slaveaccount WHERE login='$login'");
        if ($db->next_record()) {
            $err->raise("dom", _("The specified slave account already exists"));
            return false;
        }
        $db->query("INSERT INTO slaveaccount (login,pass) VALUES ('$login','$pass')");
        return true;
    }

    /* ----------------------------------------------------------------- */

    /** Remove a slave account
     */
    function del_slave_account($login) {
        global $db, $err;
        $db->query("DELETE FROM slaveaccount WHERE login='$login'");
        return true;
    }

    /*     * ********** */
    /*  Private  */
    /*     * ********** */


    /* ----------------------------------------------------------------- */

    /** Try to lock a domain
     * @access private
     */
    function lock() {
        global $db, $err;
        $err->log("dom", "lock");
        if ($this->islocked) {
            $err->raise("dom", _("--- Program error --- Lock already obtained!"));
        }
        while (file_exists($this->fic_lock_cron)) {
            sleep(2);
        }
        $this->islocked = true;
        return true;
    }

    /* ----------------------------------------------------------------- */

    /** Unlock the cron for domain management
     * return true
     * @access private
     */
    function unlock() {
        global $db, $err;
        $err->log("dom", "unlock");
        if (!$this->islocked) {
            $err->raise("dom", _("--- Program error --- No lock on the domains!"));
        }
        $this->islocked = false;
        return true;
    }

    /* ----------------------------------------------------------------- */

    /** Declare that a domain's emails are hosted in this server : 
     * This adds 2 MX entries in this domain (if required)
     */
    function hook_dom_add_mx_domain($dom_id) {
        global $err;
        $domain = $this->get_domain_byid($dom_id);
        $err->log("dom", "hook_dom_add_mx_domain");
        $this->set_sub_domain($domain, '', $this->type_defmx, '');
        if (!empty($GLOBALS['L_DEFAULT_SECONDARY_MX'])) {
            $this->set_sub_domain($domain, '', $this->type_defmx2, '');
        }
        return true;
    }

    /* ----------------------------------------------------------------- */

    /**
     * Delete an account (all his domains)
     */
    function hook_admin_del_member() {
        global $err;
        $err->log("dom", "alternc_del_member");
        $li = $this->enum_domains();
        foreach ($li as $dom) {
            $this->del_domain($dom);
        }
        return true;
    }

    /* ----------------------------------------------------------------- */

    /** Returns the used quota for the $name service for the current user.
     * @param $name string name of the quota
     * @return integer the number of service used or false if an error occured
     * @access private
     */
    function hook_quota_get() {
        global $db, $err, $cuid;
        $err->log("dom", "get_quota");
        $q = Array("name" => "dom", "description" => _("Domain name"), "used" => 0);
        $db->query("SELECT COUNT(*) AS cnt FROM domaines WHERE compte='$cuid'");
        if ($db->next_record()) {
            $q['used'] = $db->f("cnt");
        }
        return $q;
    }

    /* --------------------------------------------------------------------- */

    /** Returns the global domain(s) configuration(s) of a particular user
     * No parameters needed 
     * */
    function alternc_export_conf() {
        global $db, $err;
        $err->log("dom", "export");
        $this->enum_domains();
        $str="";
        foreach ($this->domains as $d) {
            $str.= "  <domaines>\n";
            $str.="   <nom>" . $d . "</nom>\n";
            $this->lock();
            $s = $this->get_domain_all($d);
            $this->unlock();
            if (empty($s["dns"])) {
                $s["dns"] = "non";
            } else {
                $s["dns"] = "oui";
            }
            $str.="   <dns>" . $s["dns"] . "</dns>\n";

            if (empty($s["mx"])) {
                $s["mx"] = "non";
            } else {
                $s["mx"] = "oui";
            }

            $str.="   <mx>" . $s["mx"] . "</mx>\n";

            if (empty($s["mail"])) {
                $s["mail"] = "non";
            }
            $str.="   <mail>" . $s["mail"] . "</mail>\n";
            if (is_array($s["sub"])) {
                foreach ($s["sub"] as $sub) {
                    $str.="     <subdomain>\n";
                    $str.="       <enabled>" . $sub["enable"] . " </enabled>\n";
                    $str.="       <destination>" . $sub["dest"] . " </destination>\n";
                    $str.="       <type>" . $sub["type"] . " </type>\n";
                    $str.="     </subdomain>\n";
                }
            }
            $str.=" </domaines>\n";
        }
        return $str;
    }

    /**
     * Return an array with all the needed parameters to generate conf 
     * of a vhost.
     * If no parameters, return the parameters for ALL the vhost.
     * Optionnal parameters: id of the sub_domaines
     *
     * */
    function generation_parameters($id = null, $only_apache = true) {
        global $db, $err;
        $err->log("dom", "generation_parameters");
        $params = "";
        if (!is_null($id) && intval($id) == $id) {
            $id = intval($id);
            $params = " AND sd.id = $id ";
        }
        if ($only_apache) {
            $params.=" and dt.only_dns is false ";
        }
        $db->query("
select 
  sd.id as sub_id, 
  lower(sd.type) as type, 
  m.login, 
  m.uid as uid, 
  if(length(sd.sub)>0,concat_ws('.',sd.sub,sd.domaine),sd.domaine) as fqdn, 
  concat_ws('@',m.login,v.value) as mail, 
  sd.valeur  
from 
  sub_domaines sd left join membres m on sd.compte=m.uid,
  variable v, 
  domaines_type dt 
where 
  v.name='mailname_bounce' 
  and sd.web_action in ('OK', 'UPDATE')
  and sd.enable in ('ENABLE', 'ENABLED')
  and lower(dt.name) = lower(sd.type) 
  $params 
order by 
  m.login, 
  sd.domaine, 
  sd.sub 
;");
        $r = array();
        while ($db->next_record()) {
            $r[$db->Record['sub_id']] = $db->Record;
        } 
        return $r;
    }

    /**
     * Return an array with all informations of the domains_type
     * used to generate Apache conf.
     * Die if templates missing.
     * Warning: an Apache domains_type must have 'only_dns' == TRUE
     *
     * */
    function generation_domains_type() {
        global $dom;
        $d = array();
        foreach ($dom->domains_type_lst() as $k => $v) {
            if ($v['only_dns'] == true)
                continue;
            if (!$j = file_get_contents(ALTERNC_APACHE2_GEN_TMPL_DIR . '/' . strtolower($k) . '.conf')) {
                die("Error: missing file for $k");
            }
            $d[$k] = $v;
            $d[$k]['tpl'] = $j;
        }
        return $d;
    }

// Launch old fashionned hooks as there was in AlternC 1.0
    function generate_conf_oldhook($action, $lst_sub, $sub_obj = null) {
        if (is_null($sub_obj))
            $sub_obj = $this->generation_parameters(null, false);

        if (!isset($lst_sub[strtoupper($action)]) || empty($lst_sub[strtoupper($action)])) {
            return false;
        }

        $lst_by_type = $lst_sub[strtoupper($action)];

        foreach ($lst_by_type as $type => $lid_arr) {
            $script = "/etc/alternc/functions_hosting/hosting_" . strtolower($type) . ".sh";
            if (!@is_executable($script)) {
                continue;
            }
            foreach ($lid_arr as $lid) {
                $o = $sub_obj[$lid];
                $cmd = $script . " " . escapeshellcmd(strtolower($action)) . " ";
                $cmd .= escapeshellcmd($o['fqdn']) . " " . escapeshellcmd($o['valeur']);

                system($cmd);
            }
        } // foreach $lst_by_type
    }

    /**
     * Generate apache configuration.
     * Die if a specific FQDN have 2 vhost conf.
     *
     * */
    function generate_apacheconf($p = null) {
        // Get the parameters
        $lst = $this->generation_parameters($p);

        $gdt = $this->generation_domains_type();

        // Initialize duplicate check
        $check_dup = array();

        $ret = "## AlternC Generated conf\n";
        foreach ($lst as $p) {
            // Check if duplicate
            if (in_array($p['fqdn'], $check_dup)) {
                die("Error: duplicate fqdn : " . $p['fqdn']);
            } else {
                $check_dup[] = $p['fqdn'];
            }

            // Get the needed template
            $tpl = $gdt[$p['type']] ['tpl'];

            // Replace needed vars
            $tpl = strtr($tpl, array(
                "%%LOGIN%%" => $p['login'],
                "%%fqdn%%" => $p['fqdn'],
                "%%document_root%%" => getuserpath($p['login']) . $p['valeur'],
                "%%account_root%%" => getuserpath($p['login']),
                "%%redirect%%" => $p['valeur'],
                "%%UID%%" => $p['uid'],
                "%%GID%%" => $p['uid'],
                "%%mail_account%%" => $p['mail'],
                "%%user%%" => "FIXME",
            ));

            // Security check
            if ($p['uid'] < 1999) { // if UID is not an AlternC uid
                $ret.= "# ERROR: Sub_id: " . $p['sub_id'] . "- The uid seem to be dangerous\n";
                continue;
            }

            // Return the conf
            $ret.= "# Sub_id: " . $p['sub_id'] . "\n" . $tpl;
        }

        return $ret;
    }

    // Return an array with the list of id of sub_domains waiting for an action
    function generation_todo() {
        global $db, $err;
        $err->log("dom", "generation_todo");
        $db->query("select id as sub_id, web_action, type from sub_domaines where web_action !='ok';");
        $r = array();
        while ($db->next_record()) {
            $r[strtoupper($db->Record['web_action'])][strtoupper($db->Record['type'])][] = $db->f('sub_id');
        }
        return $r;
    }

    function subdomain_modif_are_done($sub_domain_id, $action) {
        global $db;
        $sub_domain_id = intval($sub_domain_id);
        switch (strtolower($action)) {
            case "delete":
                $sql = "DELETE FROM sub_domaines WHERE id =$sub_domain_id;";
                break;
            default:
                $sql = "UPDATE sub_domaines SET web_action='OK' WHERE id='$sub_domain_id'; ";
        }
        $db->query($sql);
        return true;
    }

    /**
    * @param string $domain_id: domain id to delete from database
    * called from generate_apacheconf.php after a domain deletion
    **/
    function domain_delete() {
        global $db,$err;
        $err->log("dom", "domain_delete");
        $sql = "delete from domaines where dns_action = 'delete' and domaine not in (select domaine from sub_domaines);";
        if(!$db->query($sql)){
        	$err->raise("dom", "Error deleting domain from database");
		return false;
	}
        return true;
    }

    /**
     * @param string $dns_action
     */
    function set_dns_action($domain, $dns_action) {
        global $db;
        $db->query("UPDATE domaines SET dns_action='" . mysql_escape_string($dns_action) . "' WHERE domaine='" . mysql_escape_string($domain) . "'; ");
        return true;
    }

    function set_dns_result($domain, $dns_result) {
        global $db;
        $db->query("UPDATE domaines SET dns_result='" . mysql_escape_string($dns_result) . "' WHERE domaine='" . mysql_escape_string($domain) . "'; ");
        return true;
    }

    /* ----------------------------------------------------------------- */

    /** hook function called by AlternC-upnp to know which open 
     * tcp or udp ports this class requires or suggests
     * @return array a key => value list of port protocol name mandatory values
     * @access private
     */
    function hook_upnp_list() {
        return array(
            "dns-tcp" => array("port" => 53, "protocol" => "tcp", "mandatory" => 1),
            "dns-udp" => array("port" => 53, "protocol" => "udp", "mandatory" => 1),
        );
    }

    // List if there is problems in the domains.
    // Problems can appear when editing domains type properties
    function get_problems($domain) {
        $this->lock();
        $da = $this->get_domain_all($domain);
        $this->unlock();

        $errors = array();
        // Check if there is more than 1 apache conf
        // by subdomain
        $tmp = array();
        foreach ($da['sub'] as $sub) {
            // Skip when disable or deleted
            if ($sub['enable']=='DISABLE') continue;
            if ($sub['enable']=='DISABLED') continue;
            if ($sub['web_action']=='DELETE') continue;

            // Skip if "only_dns" and no conf generated
            if ($sub['only_dns']) continue;

            // Init the value in the tmp array
            if (!isset($tmp[$sub['fqdn']])) {
                $tmp[$sub['fqdn']] = 0;
            }
            // Increment value
            $tmp[$sub['fqdn']]++;

            // If there is more than 1 configuration file for this FQDN, alert !
            if ($tmp[$sub['fqdn']] >= 2) {
                $errors[$sub['fqdn']] = sprintf(_("Problem on %s: there is more than 1 web configuration going to be generated for this sub-domain."), $sub['fqdn']);
            }
        }

        // Check if we know each type of subdomain
        // Example: we may not know WEBMAIL if we upgrade from a previous setup
        foreach ($da['sub'] as $sub) {
            if (is_null($sub['type_desc'])) {
                $errors[$sub['fqdn']] = sprintf(_("Problem on %s: we do not know domain's type <b>%s</b>."), $sub['fqdn'], $sub['type']);
            }
        }

        // TODO: add a full compatibility check.

        return $errors;
    }

    function default_domain_type() {
        // This function is only used to allow translation of default domain types:
        _("Locally hosted");
        _("URL redirection");
        _("IPv4 redirect");
        _("Webmail access");
        _("Squirrelmail Webmail access");
        _("Roundcube Webmail access");
        _("IPv6 redirect");
        _("CNAME DNS entry");
        _("TXT DNS entry");
        _("MX DNS entry");
        _("secondary MX DNS entry");
        _("Default mail server");
        _("Default backup mail server");
        _("AlternC panel access");
    }

}

/* Class m_domains */
