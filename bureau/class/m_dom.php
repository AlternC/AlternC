<?php

/*
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
*/

define('SLAVE_FLAG', "/run/alternc/refresh_slave");

/**
 * Classe de gestion des domaines de l'hébergé.
 * 
 * Cette classe permet de gérer les domaines / sous-domaines, redirections
 * dns et mx des domaines d'un membre hébergé.<br />
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */
class m_dom {

    /** 
     * $domains : Cache des domaines du membre
     * @access private
     */
    var $domains;

    /**
     *  $dns : Liste des dns trouvés par la fonction whois
     * @access private
     */
    var $dns;

    /**
     * Flag : a-t-on trouvé un sous-domaine Webmail pour ce domaine ?
     * @access private
     */
    var $webmail;

    /**
     * Systéme de verrouillage du cron
     * Ce fichier permet de verrouiller le cron en attendant la validation
     * du domaine par update_domains.sh
     * @access private
     */
    var $fic_lock_cron = "/run/alternc/cron.lock";

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


    /**
     * Constructeur
     */
    function m_dom() {
        global $L_FQDN;
        $this->tld_no_check_at_all = variable_get('tld_no_check_at_all', 0, 'Disable ALL check on the TLD (users will be able to add any domain)', array('desc' => 'Disabled', 'type' => 'boolean'));
        variable_get('mailname_bounce', $L_FQDN, 'FQDN of the mail server, used to create vhost virtual mail_adress.', array('desc' => 'FQDN', 'type' => 'string'));
    }


    function get_panel_url_list() {
        global $db, $msg;
        $msg->debug("dom", "get_panel_url_list");
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
    public static function get_sub_domain_id_and_member_by_name($fqdn) {
        global $db, $msg;
        $msg->debug("dom", "get_sub_domain_by_name");
        $db->query("select sd.* from sub_domaines sd where if(length(sd.sub)>0,concat_ws('.',sd.sub,sd.domaine),sd.domaine) = ?;", array($fqdn));
        if (!$db->next_record()) {
            return false;
        }
        return array('sub_id' => intval($db->f('id')), 'member_id' => intval($db->f('compte')));
    }


    /** 
     * hook function called by the menu class
     * to add menu to the left panel
     */
    function hook_menu() {
        global $quota;
        $obj = array(
            'title' => _("Domains"),
            'link' => 'toggle',
            'pos' => 20,
            'links' => array(),
        );

        if ($quota->cancreate("dom")) {
            $obj['links'][] = array(
                'txt' => _("Add a domain"),
                'url' => "dom_add.php",
            );
        }

        foreach ($this->enum_domains() as $d) {
            $obj['links'][] = array(
                'txt' => htmlentities($d),
                'url' => "dom_edit.php?domain=" . urlencode($d),
            );
        }

        return $obj;
    }


    /**
     * Retourne un tableau contenant les types de domaines
     *
     * @return array retourne un tableau indexé contenant la liste types de domaines 
     *  authorisé. Retourne FALSE si une erreur s'est produite.
     */
    function domains_type_lst() {
        global $db, $msg;
        $msg->debug("dom", "domains_type_lst");
        if (empty($this->cache_domains_type_lst)) {
            $db->query("select * from domaines_type order by advanced;");
            $this->cache_domains_type_lst = array();
            while ($db->next_record()) {
                $this->cache_domains_type_lst[strtolower($db->Record["name"])] = $db->Record;
            }
        }
        return $this->cache_domains_type_lst;
    }


    // returns array(ALL,NONE,ADMIN) 
    function domains_type_enable_values() {
        global $db, $msg, $cuid;
        $msg->debug("dom", "domains_type_enable_values");
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
     * all = 'NONE','URL','DIRECTORY','IP','IPV6','DOMAIN','TXT'
     */
    function domains_type_target_values($type = null) {
        global $db, $msg;
        $msg->debug("dom", "domains_type_target_values");
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
            $db->query("select target from domaines_type where name= ? ;", array($type));
            if (!$db->next_record()) {
                return false;
            }
            return $db->f('target');
        }
    }


    function import_manual_dns_zone($zone, $domain, $detect_redirect = true, $save = false) {
        global $msg;
        if ($save) {
            if (!$this->import_manual_dns_prep_zone($domain)) {
                $msg->raise("ERROR", 'dom', _("Err: failed to prepare the zone"));
                return false;
            }
        }

        $val = array();
        foreach (explode("\n", $zone) as $z) {
            $z = trim($z);
            if (empty($z)) {
                continue;
            }
            $val[] = $this->import_manual_dns_entry($z, $domain, $detect_redirect, $save);
        }
        return $val;
    }


    /**
     * @param string $zone
     */
    function import_manual_dns_entry($zone, $domain, $detect_redirect = true, $save = false) {
        global $msg;
        $msg->log("dom", "import_manual_dns_entry");
        $zone = trim($zone);
        if (empty($zone)) {
            return false;
        }

        $domain = trim($domain);
        if (empty($domain)) {
            $msg->raise("ERROR", "dom", _("Missing domain name"));
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
        $entry['did_it'] = 0;
        if ($entry['status'] == 'err') {
            return $entry;
        }

        $val = $entry['entry_new'];

        if (empty($val['type'])) {
            return false;
        }

        switch ($val['type']) {
        case "set_ttl":
            $entry['did_it'] = $this->set_ttl($this->get_domain_byname($val['domain']), $val['value']);
            return $entry;
        }

        // If it is an unknown domains type
        if (!array_key_exists(strtolower($val['type']), $this->domains_type_lst())) {
            echo "what is this shit ?\n";
            print_r($entry);
            return $entry;
        }

        // If the subdomain is @, we want an empty subdomain
        if ($val['sub'] == '@') {
            $val['sub'] = '';
        }

        $this->lock();
        $entry['did_it'] = $this->set_sub_domain($val['domain'], $val['sub'], $val['type'], $val['value']);
        $this->unlock();

        return $entry;
    }


    private function import_manual_dns_prep_zone($domain) {
        global $msg;
        // Prepare a domain to be importer : 
        // * create the domain
        // * delete all automatic subdomain
        // * set no mx
        $this->lock();

        // function add_domain($domain,$dns,$noerase=0,$force=0,$isslave=0,$slavedom="") 
        if (!$this->add_domain($domain, true, false, true)) {
            $msg->raise("ERROR", 'dom', "Error adding domain");
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
        global $db, $msg, $cuid;
        $db->query("update sub_domaines set web_action='UPDATE' where lower(type) = lower(?) ;", array($name));
        $db->query("update domaines d, sub_domaines sd set d.dns_action = 'UPDATE' where lower(sd.type)=lower(?);", array($name));
        return true;
    }


    function domains_type_get($name) {
        global $db;
        $db->query("select * from domaines_type where name= ?;", array($name));
        $db->next_record();
        return $db->Record;
    }


    function domains_type_del($name) {
        global $db;
        $db->query("delete domaines_type where name= ? ;", array($name));
        return true;
    }


    function domains_type_update($name, $description, $target, $entry, $compatibility, $enable, $only_dns, $need_dns, $advanced, $create_tmpdir, $create_targetdir,$has_https_option=0) {
        global $msg, $db;
        // The name MUST contain only letter and digits, it's an identifier after all ...
        if (!preg_match("#^[a-z0-9]+$#", $name)) {
            $msg->raise("ERROR", "dom", _("The name MUST contain only letter and digits"));
            return false;
        }
        $only_dns = intval($only_dns);
        $need_dns = intval($need_dns);
        $advanced = intval($advanced);
        $has_https_option = intval($has_https_option);
        $create_tmpdir = intval($create_tmpdir);
        $create_targetdir = intval($create_targetdir);
        $db->query("UPDATE domaines_type SET description= ?, target= ?, entry= ?, compatibility= ?, enable= e, need_dns= ?, only_dns= ?, advanced= ?,create_tmpdir= ?,create_targetdir= ?, has_https_option=? where name= ?;", array($description, $target, $entry, $compatibility, $enable, $need_dns, $only_dns, $advanced, $create_tmpdir, $create_targetdir, $has_https_option, $name));
        return true;
    }


    function sub_domain_change_status($sub_id, $status) {
        global $db, $msg;
        $msg->log("dom", "sub_domain_change_status");
        $sub_id = intval($sub_id);
        $status = strtoupper($status);
        if (!in_array($status, array('ENABLE', 'DISABLE'))) {
            return false;
        }

        $jh = $this->get_sub_domain_all($sub_id);
        if ($status == 'ENABLE') { // check compatibility with existing sub_domains
            if (!$this->can_create_subdomain($jh['domain'], $jh['name'], $jh['type'], $sub_id)) {
                $msg->raise("ERROR", "dom", _("The parameters for this subdomain and domain type are invalid. Please check for subdomain entries incompatibility"));
                return false;
            }
        }

        $db->query("update sub_domaines set enable= ? where id = ? ;", array($status, intval($sub_id)));
        $this->set_dns_action($jh['domain'], 'UPDATE');

        return true;
    }


    /**
     * Retourne un tableau contenant les domaines d'un membre.
     * Par défaut le membre connecté
     *
     * @return array retourne un tableau indexé contenant la liste des
     *  domaines hébergés sur le compte courant. Retourne FALSE si une
     *  erreur s'est produite.
     */
    function enum_domains($uid = -1) {
        global $db, $msg, $cuid;
        $msg->debug("dom", "enum_domains");
        if ($uid == -1) {
            $uid = $cuid;
        }
        $db->query("SELECT * FROM domaines WHERE compte= ? ORDER BY domaine ASC;", array($uid));
        $this->domains = array();
        if ($db->num_rows() > 0) {
            while ($db->next_record()) {
                $this->domains[] = $db->f("domaine");
            }
        }
        return $this->domains;
    }

    function del_domain_cancel($dom) {
        global $db, $msg, $classes, $cuid;
        $msg->log("dom", "del_domain_cancel", $dom);
        $dom = strtolower($dom);
        $db->query("UPDATE sub_domaines SET web_action='UPDATE'  WHERE domaine= ?;", array($dom));
        $this->set_dns_action($dom, 'UPDATE');
        # TODO : some work with domain sensitive classes
        return true;
    }


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
        global $db, $msg, $hooks;
        $msg->log("dom", "del_domain", $dom);
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
        $db->query("UPDATE sub_domaines SET web_action='DELETE'  WHERE domaine= ?;", array($dom));
        $this->set_dns_action($dom, 'DELETE');

        return true;
    }


    function domshort($dom, $sub = "") {
        return str_replace("-", "", str_replace(".", "", empty($sub) ? "" : "$sub.") . $dom);
    }


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
     * @return boolean Retourne FALSE si une erreur s'est produite, TRUE sinon.
     */
    function add_domain($domain, $dns, $noerase = false, $force = false, $isslave = false, $slavedom = "") {
        global $db, $msg, $quota, $L_FQDN, $tld, $cuid, $hooks;
        $msg->log("dom", "add_domain", $domain);

        // Locked ?
        if (!$this->islocked) {
            $msg->raise("ERROR", "dom", _("--- Program error --- No lock on the domains!"));
            return false;
        }
        // Verifie que le domaine est rfc-compliant
        $domain = strtolower($domain);
        $t = checkfqdn($domain);
        if ($t) {
            $msg->raise("ERROR", "dom", _("The domain name is syntaxically incorrect"));
            return false;
        }
        // Interdit les domaines clés (table forbidden_domains) sauf en cas FORCE
        $db->query("SELECT domain FROM forbidden_domains WHERE domain= ? ;", array($domain));
        if ($db->num_rows() && !$force) {
            $msg->raise("ERROR", "dom", _("The requested domain is forbidden in this server, please contact the administrator"));
            return false;
        }
        if ($domain == $L_FQDN || $domain == "www.$L_FQDN") {
            $msg->raise("ERROR", "dom", _("This domain is the server's domain! You cannot host it on your account!"));
            return false;
        }
        $db->query("SELECT compte FROM domaines WHERE domaine= ?;", array($domain));
        if ($db->num_rows()) {
            $msg->raise("ERROR", "dom", _("The domain already exist"));
            return false;
        }
        $db->query("SELECT compte FROM `sub_domaines` WHERE sub != \"\" AND concat( sub, \".\", domaine )= ? OR domaine= ?;", array($domain, $domain));
        if ($db->num_rows()) {
            $msg->raise("ERROR", "dom", _("The domain already exist"));
            return false;
        }
        $this->dns = $this->whois($domain);
        if (!$force) {
            $v = checkhostallow($domain, $this->dns);
            if ($v == -1) {
                $msg->raise("ERROR", "dom", _("The last member of the domain name is incorrect or cannot be hosted in that server"));
                return false;
            }
            if ($dns && $v == -2) {
                $msg->raise("ALERT", "dom", _("The domain cannot be found in the whois database"));
                return false;
            }
            if ($dns && $v == -3) {
                $msg->raise("ALERT", "dom", _("The domain cannot be found in the whois database"));
                return false;
            }

            if ($dns) {
                $dns = "1";
            } else {
                $dns = "0";
            }
            // mode 5 : force DNS to NO.
            if ($tld[$v] == 5) {
                $dns = 0;
            }
            // It must be a real domain (no subdomain)
            if (!$dns) {
                $v = checkhostallow_nodns($domain);
                if ($v) {
                    $msg->raise("ERROR", "dom", _("The requested domain is forbidden in this server, please contact the administrator"));
                    return false;
                }
            }
        }
        // Check the quota :
        if (!$quota->cancreate("dom")) {
            $msg->raise("ALERT", "dom", _("Your domain quota is over, you cannot create more domain names"));
            return false;
        }
        if ($noerase) {
            $noerase = "1";
        } else {
            $noerase = "0";
        }
        if ($dns) {
            $gesmx = "1";
        } else {
            $gesmx = "0"; // do not host mx by default if not hosting the DNS
        }
        $db->query("INSERT INTO domaines (compte,domaine,gesdns,gesmx,noerase,dns_action) VALUES (?, ?, ?, ?, ?, 'UPDATE');", array($cuid,$domain,$dns,$gesmx,$noerase));
        if (!($id = $db->lastid())) {
            $msg->raise("ERROR", "dom", _("An unexpected error occured when creating the domain"));
            return false;
        }

        if ($isslave) {
            $isslave = true;
            $db->query("SELECT domaine FROM domaines WHERE compte= ? AND domaine= ?;", array($cuid, $slavedom));
            $db->next_record();
            if (!$db->Record["domaine"]) {
                $msg->raise("ERROR", "dom", _("Domain '%s' not found"), $slavedom);
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
        if ($gesmx) {
            $hooks->invoke("hook_dom_add_mx_domain", array($id));
        }
        if ($isslave) {
            $hooks->invoke("hook_dom_add_slave_domain", array($id, $slavedom));
        }
        return true;
    }


    /**
     * @param string $domain
     */
    function create_default_subdomains($domain, $target_domain = "") {
        global $db, $msg;
        $msg->log("dom", "create_default_subdomains", $domain);
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
        return "/www/" . $this->domshort($domain);
    }


    function dump_axfr($domain, $ns = 'localhost') {
        $axfr = array();
        exec('/usr/bin/dig AXFR "' . escapeshellcmd($domain) . '" @"' . escapeshellcmd($ns) . '"', $axfr);
        return $axfr;
    }


    function lst_default_subdomains() {
        global $db, $msg;
        $msg->debug("dom", "lst_default_subdomains");
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
        global $msg;
        $msg->log("dom", "update_default_subdomains");
        $ok = true;
        foreach ($arr as $a) {
            if (!isset($a['id'])) {
                $a['id'] = null;
            }
            if (!empty($a['sub']) || !empty($a['domain_type_parameter'])) {

                if (!isset($a['enabled'])) {
                    $a['enabled'] = 0;
                }
                if (!$this->update_one_default($a['domain_type'], $a['sub'], $a['domain_type_parameter'], $a['concerned'], $a['enabled'], $a['id'])) {
                    $ok = false;
                }
            }
        }
        return $ok;
    }


    function update_one_default($domain_type, $sub, $domain_type_parameter, $concerned, $enabled, $id = null) {
        global $db, $msg;
        $msg->log("dom", "update_one_default");

        if ($id == null) {
            $db->query("INSERT INTO default_subdomains values ('', ?, ?, ?, ?, ?);", array($sub, $domain_type, $domain_type_parameter, $concerned, $enabled));
        } else {
            $db->query("UPDATE default_subdomains set sub= ?, domain_type= ?, domain_type_parameter= ?, concerned= ?, enabled= ? where id= ?;", array($sub, $domain_type, $domain_type_parameter, $concerned, $enabled, $id));
        }
        return true;
        //update
    }


    function del_default_type($id) {
        global $msg, $db;
        $msg->log("dom", "del_default_type");

        if (!$db->query("delete from default_subdomains where id= ?;", array($id))) {
            $msg->raise("ERROR", "dom", _("Could not delete default type"));
            return false;
        }

        return true;
    }


    /**
     * Return the NS of a server by interrogating its parent zone.
     * 
     * @param string $domain FQDN we are searching for
     * @return array Return the authoritative NS of this domain
     *   or FALSE if an error occurred
     *
     */
    function whois($domain) {
        global $msg;
        $msg->debug("dom", "whois", $domain);

        $domain=trim($domain,"."); // strip initial/final .
        $parent=$domain; $loopmax=32;
        do {
            $parent=substr($parent,strpos($parent,".")+1);
            $parent=trim($parent,".");
            if (!$parent) {
                $msg->raise("ALERT", "dom", _("The domain has no parent. Check syntax"));
                return false; // no . in this fqdn??
            }
            // ask the parent for its NS (no +trace)
            $out=array();
            exec("dig +short NS ".escapeshellarg($parent),$out);
            $loopmax--;
        } while (!count($out) && $loopmax); // will stop when : we have no parent, or
        if (!count($out)) {
            return false; // bad exit of the loop
        }
        $parentns=trim($out[0]); 

        // we take the first NS of the SOA of the parent and interrogate it for the child domain:
        $out=array();
        exec("dig NS ".escapeshellarg($domain)." ".escapeshellarg("@".$parentns),$out);
        // we scan the dig result for authoritative information :
        $ns=array();
        foreach($out as $line) {
            if (preg_match('#^'.str_replace(".","\\.",$domain).'\..*IN\s*NS\s*(.*)$#',$line,$mat)) {
                $ns[]=trim($mat[1]);
            }
        }
        return $ns;
    } // whois


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

        $ref_mx = array();
        $mxhosts = array();
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
     *  $r["sub"][0-(nsub-1)]["https"] = is https properly enabled for this subdomain? (http/https/both)
     *  </pre>
     *  Retourne FALSE si une erreur s'est produite.
     *
     */
    function get_domain_all($dom) {
        global $db, $msg, $cuid;
        $msg->debug("dom", "get_domain_all", $dom);
        // Locked ?
        if (!$this->islocked) {
            $msg->raise("ERROR", "dom", _("--- Program error --- No lock on the domains!"));
            return false;
        }
        $t = checkfqdn($dom);
        if ($t) {
            $msg->raise("ERROR", "dom", _("The domain name is syntaxically incorrect"));
            return false;
        }
        $r = array();
        $r["name"] = $dom;
        $db->query("SELECT * FROM domaines WHERE compte= ? AND domaine= ?;", array($cuid, $dom));
        if ($db->num_rows() == 0) {
            $msg->raise("ERROR", "dom", sprintf(_("Domain '%s' not found"), $dom));
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
        $db->query("SELECT COUNT(*) AS cnt FROM sub_domaines WHERE compte= ? AND domaine= ?;", array($cuid, $dom));
        $db->next_record();
        $r["nsub"] = $db->Record["cnt"];
        $db->free();
        $db->query("SELECT sd.*, dt.description AS type_desc, dt.only_dns, dt.advanced, dt.has_https_option FROM sub_domaines sd LEFT JOIN domaines_type dt on  UPPER(dt.name)=UPPER(sd.type) WHERE compte= ? AND domaine= ? ORDER BY dt.advanced,sd.sub,sd.type ;", array($cuid, $dom));
        // Pas de webmail, on le cochera si on le trouve.
        $r["sub"] = array();
        $data = $db->fetchAll();
        foreach($data as $i=>$record) {
            $r["sub"][$i] = $record;
            // FIXME : replace sub by name and dest by valeur in the code that exploits this function :
            $r["sub"][$i]["name"] = $record["sub"];
            $r["sub"][$i]["dest"] = $record["valeur"];
            $r["sub"][$i]["fqdn"] = ((!empty($r["sub"][$i]["name"])) ? $r["sub"][$i]["name"] . "." : "") . $r["name"];
        }
        $db->free();
        return $r;
    } // get_domain_all


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
        global $db, $msg, $cuid;
        $msg->debug("dom", "get_sub_domain_all", $sub_domain_id);
        // Locked ?
        if (!$this->islocked) {
            $msg->raise("ERROR", "dom", _("--- Program error --- No lock on the domains!"));
            return false;
        }
        $db->query("select sd.*, dt.description as type_desc, dt.only_dns, dt.advanced from sub_domaines sd, domaines_type dt where compte= ? and sd.id= ?  and upper(dt.name)=upper(sd.type) ORDER BY dt.advanced, sd.sub;", array($cuid, $sub_domain_id));
        if ($db->num_rows() == 0) {
            $msg->raise("ERROR", "dom", _("The sub-domain does not exist"));
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
        $r["https"] = $db->Record["https"];
        $db->free();
        return $r;
    } // get_sub_domain_all


    function clean_https_value($type, $https) {
        global $db;
        $db->query("select has_https_option from domaines_type where name= ? ;", array($type));
        if (!$db->next_record()) {
            return "";
        }
        if ($db->Record["has_https_option"]) {
            $https=strtolower($https);
            if ($https!="http" && $https!="https" && $https!="both") {
                return "both";
            }
            return $https;
        } else return "";
    }

    
    /**
     * @param integer $type
     * @param string $value
     */
    function check_type_value($type, $value) {
        global $msg;

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
                    $msg->raise("ERROR", "dom", _("invalid url"));
                    return false;
                }
            }
            break;
        case 'DIRECTORY':
            if (substr($value, 0, 1) != "/") {
                $value = "/" . $value;
            }
            if (!checkuserpath($value)) {
                $msg->raise("ERROR", "dom", _("The folder you entered is incorrect or does not exist"));
                return false;
            }
            return true;
        case 'IP':
            if (checkip($value)) {
                return true;
            } else {
                $msg->raise("ERROR", "dom", _("The ip address is invalid"));
                return false;
            }
            break;
        case 'IPV6':
            if (checkipv6($value)) {
                return true;
            } else {
                $msg->raise("ERROR", "dom", _("The ip address is invalid"));
                return false;
            }
            break;
        case 'DOMAIN':
            if (checkcname($value)) {
                return true;
            } else {
                $msg->raise("ERROR", "dom", _("The name you entered is incorrect or not fully qualified (it must end with a DOT, like example.com<b>.</b>)"));
                return false;
            }
            break;
        case 'TXT':
            if ($value == strval($value)) {
                return true;
            } else {
                $msg->raise("ERROR", "dom", _("The TXT value you entered is incorrect"));
                return false;
            }
            break;
        default:
            $msg->raise("ERROR", "dom", _("Invalid domain type selected, please check"));
            return false;
        }
        return false;
    }


    /**
     * Check the compatibility of the POSTed parameters with the chosen
     * domain type
     *
     * @param string $dom FQDN of the domain name
     * @param string $sub SUBdomain 
     * @return boolean tell you if the subdomain can be installed there 
     */
    function can_create_subdomain($dom, $sub, $type, $sub_domain_id = 0) {
        global $db, $msg;

        $sub_domain_id=intval($sub_domain_id);
        $msg->log("dom", "can_create_subdomain", $dom . "/" . $sub . "/" .$type . "/" . $sub_domain_id);

        // Get the compatibility list for this domain type
        $db->query("select upper(compatibility) as compatibility from domaines_type where upper(name)=upper(?);", array($type));
        if (!$db->next_record()) {
            return false;
        }
        $compatibility_lst = explode(",", $db->f('compatibility'));

        // Get the list of type of subdomains already here who have the same name
        $db->query("select * from sub_domaines where sub= ? and domaine= ? and not id = ? and web_action != 'DELETE' and enable not in ('DISABLED', 'DISABLE') ", array($sub, $dom, $sub_domain_id));
        #$db->query("select * from sub_domaines where sub='$sub' and domaine='$dom';");
        while ($db->next_record()) {
            // And if there is a domain with a incompatible type, return false
            if (!in_array(strtoupper($db->f('type')), $compatibility_lst)) {
                return false;
            }
        }

        // Forbidden to create a CNAME RR on the domain APEX (RFC 1912)
        if ($type == 'cname' && $sub == '')    
            return false;  

        // All is right, go ! Create ur domain !
        return true;
    }


    /**
     * set the HTTPS preference for a subdomain.
     * @param integer the sub_domain_id (will be checked against the user ID identity)
     * @param string the provider (if not empty, will be checked against an existing certificate for this subdomain)
     * @return boolean true if the preference has been set
     */
    function set_subdomain_ssl_provider($sub_domain_id,$provider) { 
        global $db, $msg, $cuid, $ssl;
        $msg->log("dom", "set_sub_domain_ssl_provider", $sub_domain_id." / ".$provider);
        // Locked ?
        if (!$this->islocked) {
            $msg->raise("ERROR", "dom", _("--- Program error --- No lock on the domains!"));
            return false;
        }
        $db->query("SELECT * FROM sub_domaines WHERE id=?",array($sub_domain_id));
        if (!$db->next_record() || $db->Record["compte"]!=$cuid) {
            $msg->raise("ERROR", "dom", _("Subdomain not found"));
            return false;
        }
        $fqdn=$db->Record["sub"].(($db->Record["sub"])?".":"").$db->Record["domaine"];
        $certs = $ssl->get_valid_certs($fqdn);
        $provider=strtolower(trim($provider));
        if ($provider) {
            $found=false;
            foreach($certs as $cert) {
                if ($cert["provider"]==$provider) {
                    $found=true;
                }
            }
            if (!$found) {
                $msg->raise("ERROR", "dom", _("No certificate found for this provider and this subdomain"));
                return false;
            }
        }
        $db->query("UPDATE sub_domaines SET web_action=?, provider=? WHERE id=?",array("UPDATE",$provider,$sub_domain_id));
        return true;
    }

    
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
     * @param string $https the HTTPS behavior : HTTP(redirect https to http), 
     *  HTTPS(redirect http to https) or BOTH (both hosted at the same place)
     * @return boolean Retourne FALSE si une erreur s'est produite, TRUE sinon.
     */
    function set_sub_domain($dom, $sub, $type, $dest, $sub_domain_id = 0, $https) {
        global $db, $msg, $cuid, $bro;
        $msg->log("dom", "set_sub_domain", $dom . "/" . $sub . "/" . $type . "/" . $dest);
        // Locked ?
        if (!$this->islocked) {
            $msg->raise("ERROR", "dom", _("--- Program error --- No lock on the domains!"));
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
            $msg->raise("ALERT", "dom", _("There is some forbidden characters in the sub domain (only A-Z 0-9 and - are allowed)"));
            return false;
        }

        if (!$this->check_type_value($type, $dest)) {
            // error raised by check_type_value
            return false;
        }
        $https=$this->clean_https_value($type, $https);

        // On a épuré $dir des problémes eventuels ... On est en DESSOUS du dossier de l'utilisateur.
        if (($t = checkfqdn($dom))) {
            $msg->raise("ERROR", "dom", _("The domain name is syntaxically incorrect"));
            return false;
        }

        if (!$this->can_create_subdomain($dom, $sub, $type, $sub_domain_id)) {
            $msg->raise("ERROR", "dom", _("The parameters for this subdomain and domain type are invalid. Please check for subdomain entries incompatibility"));
            return false;
        }

        if ($sub_domain_id!=0) { // It's not a creation, it's an edit. Delete the old one
            $this->del_sub_domain($sub_domain_id);
        }

        // Re-create the one we want
        if (!$db->query("INSERT INTO sub_domaines (compte,domaine,sub,valeur,type,web_action,https) VALUES (?, ?, ?, ?, ?, 'UPDATE',?);", array( $cuid , $dom , $sub , $dest , $type, $https ))) {
            $msg->raise("ERROR", "dom", _("The parameters for this subdomain and domain type are invalid. Please check for subdomain entries incompatibility"));
            return false;
        }

        // Create TMP dir and TARGET dir if needed by the domains_type
        $dest_root = $bro->get_userid_root($cuid);
        //$domshort = $this->domshort($dom, $sub);
        $db->query("select create_tmpdir, create_targetdir from domaines_type where name = ?;", array($type));
        $db->next_record();
        if ($db->f('create_tmpdir')) {
            if (!is_dir($dest_root . "/tmp")) {
                if (!@mkdir($dest_root . "/tmp", 0777, true)) {
                    $msg->raise("ERROR", "dom", _("Cannot write to the destination folder"));
                }
            }
        }
        if ($db->f('create_targetdir')) {
            $dirr = $dest_root . $dest;
            $dirr = str_replace('//', '/', $dirr);

            if (!is_dir($dirr)) {
                $old = umask(0);
                if (!@mkdir($dirr, 0770, true)) {
                    $msg->raise("ERROR", "dom", _("Cannot write to the destination folder"));
                }
                umask($old);
            }
        }

        // Tell to update the DNS file
        $db->query("update domaines set dns_action='UPDATE' where domaine= ?;", array($dom));

        return true;
    }


    /**
     *  Supprime le sous-domaine demandé
     *
     * @return boolean Retourne FALSE si une erreur s'est produite, TRUE sinon.
     *
     */
    function del_sub_domain($sub_domain_id) {
        global $db, $msg;
        $msg->log("dom", "del_sub_domain", $sub_domain_id);
        // Locked ?
        if (!$this->islocked) {
            $msg->raise("ERROR", "dom", _("--- Program error --- No lock on the domains!"));
            return false;
        }
        if (!$r = $this->get_sub_domain_all($sub_domain_id)) {
            $msg->raise("ERROR", "dom", _("The sub-domain does not exist"));
            return false;
        } else {
            $db->query("update sub_domaines set web_action='DELETE' where id= ?; ", array($sub_domain_id));
            $db->query("update domaines set dns_action='UPDATE' where domaine= ?;", array($r['domain']));
        }
        return true;
    }


    /**
     * @param integer $dom_id
     */
    function set_ttl($dom_id, $ttl) {
        global $msg;
        $msg->log("dom", "set_ttl", "$dom_id / $ttl");
        $this->lock();
        $domaine = $this->get_domain_byid($dom_id);
        $d = $this->get_domain_all($domaine);

        $j = $this->edit_domain($domaine, $d['dns'], $d['mail'], false, $ttl);
        $this->unlock();
        return $j;
    }


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
    function edit_domain($dom, $dns, $gesmx, $force = false, $ttl = 3600) {
        global $db, $msg, $hooks;
        $msg->log("dom", "edit_domain", $dom . "/" . $dns . "/" . $gesmx);
        // Locked ?
        if (!$this->islocked && !$force) {
            $msg->raise("ERROR", "dom", _("--- Program error --- No lock on the domains!"));
            return false;
        }
        if ($dns == true && !$force) {
            $this->dns = $this->whois($dom);
            $v = checkhostallow($dom, $this->dns);
            if ($v == -1) {
                $msg->raise("ERROR", "dom", _("The last member of the domain name is incorrect or cannot be hosted in that server"));
                return false;
            }
            if ($dns && $v == -2) {
                $msg->raise("ALERT", "dom", _("The domain cannot be found in the Whois database"));
                return false;
            }
            if ($dns && $v == -3) {
                $msg->raise("ALERT", "dom", _("The DNS of this domain do not match the server's DNS. Please change your domain's DNS before you install it again"));
                return false;
            }
        }

        // Can't have ttl == 0. There is also a check in function_dns
        if ($ttl == 0) {
            $ttl = 3600;
        }

        $t = checkfqdn($dom);
        if ($t) {
            $msg->raise("ERROR", "dom", _("The domain name is syntaxically incorrect"));
            return false;
        }
        if (!$r = $this->get_domain_all($dom)) {
            // Le domaine n'existe pas, Failure
            $msg->raise("ERROR", "dom", _("The domain name %s does not exist"), $dom);
            return false;
        }
        if ($dns != "1") {
            $dns = "0";
        }
        // On vérifie que des modifications ont bien eu lieu :)
        if ($r["dns"] == $dns && $r["mail"] == $gesmx && $r["zonettl"] == $ttl) {
            $msg->raise("INFO", "dom", _("No change has been requested..."));
            return true;
        }

        // si gestion mx uniquement, vérification du dns externe
        if ($dns == "0" && $gesmx == "1" && !$force) {
            $vmx = $this->checkmx($dom);
            if ($vmx == 1) {
                $msg->raise("ALERT", "dom", _("There is no MX record pointing to this server, and you are asking us to host the mail here. Make sure to update your MX entries or no mail will be received"));
            }

            if ($vmx == 2) {
                // Serveur non spécifié parmi les champx mx
                $msg->raise("ALERT", "dom", _("There is no MX record pointing to this server, and you are asking us to host the mail here. Make sure to update your MX entries or no mail will be received"));
            }
        }

        if ($gesmx && !$r["mail"]) {
            $hooks->invoke("hook_dom_add_mx_domain", array($r["id"]));
        }

        if (!$gesmx && $r["mail"]) { // on a dissocié le MX : on détruit donc l'entree dans LDAP
            $hooks->invoke("hook_dom_del_mx_domain", array($r["id"]));
        }

        $db->query("UPDATE domaines SET gesdns= ?, gesmx= ?, zonettl= ? WHERE domaine= ?", array($dns, $gesmx, $ttl, $dom));
        $this->set_dns_action($dom, 'UPDATE');

        return true;
    }


    /*  Slave dns ip managment  */


    /** Return the list of ip addresses and classes that are allowed access to domain list
     * through AXFR Transfers from the bind server.
     */
    function enum_slave_ip() {
        global $db, $msg;
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


    /** 
     * Add an ip address (or a ip class) to the list of allowed slave ip access list.
     */
    function add_slave_ip($ip, $class = "32") {
        global $db, $msg;
        if (!checkip($ip)) {
            $msg->raise("ERROR", "dom", _("The IP address you entered is incorrect"));
            return false;
        }
        $class = intval($class);
        if ($class < 8 || $class > 32) {
            $class = 32;
        }
        $db->query("SELECT * FROM slaveip WHERE ip= ? AND class= ?;", array($ip, $class));
        if ($db->next_record()) {
            $msg->raise("ERROR", "err", _("The requested domain is forbidden in this server, please contact the administrator"));
            return false;
        }
        $db->query("INSERT INTO slaveip (ip,class) VALUES (?, ?);", array($ip, $class));
        $f = fopen(SLAVE_FLAG, "w");
        fputs($f, "yopla");
        fclose($f);
        return true;
    }


    /** 
     * Remove an ip address (or a ip class) from the list of allowed slave ip access list.
     */
    function del_slave_ip($ip) {
        global $db, $msg;
        if (!checkip($ip)) {
            $msg->raise("ERROR", "dom", _("The IP address you entered is incorrect"));
            return false;
        }
        $db->query("DELETE FROM slaveip WHERE ip= ?;", array($ip));
        $f = fopen(SLAVE_FLAG, "w");
        fputs($f, "yopla");
        fclose($f);
        return true;
    }


    /** 
     * Check for a slave account
     */
    function check_slave_account($login, $pass) {
        global $db;
        $db->query("SELECT * FROM slaveaccount WHERE login= ? AND pass= ?;", array($login, $pass));
        if ($db->next_record()) {
            return true;
        }
        return false;
    }

    /** 
     * Out (echo) the complete hosted domain list : 
     */
    function echo_domain_list($integrity = false) {
        global $db;
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


    /** 
     * Returns the complete hosted domain list : 
     */
    function get_domain_list($uid = -1) {
        global $db;
        $uid = intval($uid);
        $res = array();
        $sql = "";

        $query  =   "SELECT domaine FROM domaines WHERE gesdns=1 ";
        $query_args = array();
        if ($uid != -1) {
            $query .= " AND compte= ? ";
            array_push($query_args, $uid);
        }
        $query  .= " ORDER BY domaine;";
        $db->query($query, $query_args);
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
        global $db;
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


    /** Returns the name of a domain for the current user, from it's domain_id
     * @param $dom_id integer the domain_id to search for
     * @return string the domain name, or false with an error raised.
     */
    function get_domain_byid($dom_id) {
        global $db, $msg, $cuid;
        $dom_id = intval($dom_id);
        $db->query("SELECT domaine FROM domaines WHERE id= ? AND compte= ?;", array($dom_id, $cuid));
        if ($db->next_record()) {
            $domain = $db->f("domaine");
            if (!$domain) {
                $msg->raise("ERROR", "dom", _("This domain is not installed in your account"));
                return false;
            } else {
                return $domain;
            }
        } else {
            $msg->raise("ERROR", "dom", _("This domain is not installed in your account"));
            return false;
        }
    }


    /** Returns the id of a domain for the current user, from it's domain name
     * @param $domain string the domain name to search for
     * @return integer the domain id, or false with an error raised.
     */
    function get_domain_byname($domain) {
        global $db, $msg, $cuid;
        $domain = trim($domain);
        $db->query("SELECT id FROM domaines WHERE domaine= ? AND compte= ?;", array($domain, $cuid));
        if ($db->next_record()) {
            $id = $db->f("id");
            if (!$id) {
                $msg->raise("ERROR", "dom", _("This domain is not installed in your account"));
                return false;
            } else {
                return $id;
            }
        } else {
            $msg->raise("ERROR", "dom", _("This domain is not installed in your account"));
            return false;
        }
    }


    /** 
     * Count all domains, for all users
     */
    function count_domains_all() {
        global $db;
        $db->query("SELECT COUNT(*) AS count FROM domaines;");
        if ($db->next_record()) {
            return $db->f('count');
        } else {
            return 0;
        }
    }


    /** 
     * Return the list of allowed slave accounts 
     */
    function enum_slave_account() {
        global $db;
        $db->query("SELECT * FROM slaveaccount;");
        $res = array();
        while ($db->next_record()) {
            $res[] = $db->Record;
        }
        if (!count($res)) {
            return false;
        }
        return $res;
    }


    /** 
     * Add a slave account that will be allowed to access the domain list
     */
    function add_slave_account($login, $pass) {
        global $db, $msg;
        $db->query("SELECT * FROM slaveaccount WHERE login= ?", array($login));
        if ($db->next_record()) {
            $msg->raise("ERROR", "dom", _("The specified slave account already exists"));
            return false;
        }
        $db->query("INSERT INTO slaveaccount (login,pass) VALUES (?, ?)", array($login, $pass));
        return true;
    }


    /** 
     * Remove a slave account
     */
    function del_slave_account($login) {
        global $db, $msg;
        $db->query("DELETE FROM slaveaccount WHERE login= ?", array($login));
        return true;
    }

    /*  Private  */


    /** 
     * Try to lock a domain
     * @access private
     */
    function lock() {
        global $msg;
        $msg->debug("dom", "lock");
        if ($this->islocked) {
            $msg->raise("ERROR", "dom", _("--- Program error --- Lock already obtained!"));
        }
        // wait for the file to disappear, or at most 15min: 
        while (file_exists($this->fic_lock_cron) && filemtime($this->fic_lock_cron)>(time()-900)) {
            clearstatcache();
            sleep(2);
        }
        @touch($this->fic_lock_cron);
        $this->islocked = true;
        // extra safe : 
        register_shutdown_function(array("m_dom","unlock"),1);
        return true;
    }


    /** 
     * Unlock the cron for domain management
     * return true
     * @access private
     */
    function unlock($isshutdown=0) {
        global $msg;
        $msg->debug("dom", "unlock");
        if (!$isshutdown && !$this->islocked) {
            $msg->raise("ERROR", "dom", _("--- Program error --- No lock on the domains!"));
        }
        @unlink($this->fic_lock_cron);
        $this->islocked = false;
        return true;
    }


    /** 
     * Declare that a domain's emails are hosted in this server : 
     * This adds 2 MX entries in this domain (if required)
     */
    function hook_dom_add_mx_domain($dom_id) {
        global $msg;
        $domain = $this->get_domain_byid($dom_id);
        $msg->log("dom", "hook_dom_add_mx_domain");
        $this->set_sub_domain($domain, '', $this->type_defmx, '');
        if (!empty($GLOBALS['L_DEFAULT_SECONDARY_MX'])) {
            $this->set_sub_domain($domain, '', $this->type_defmx2, '');
        }
        return true;
    }


    /**
     * Delete an account (all his domains)
     */
    function admin_del_member() {
        global $msg;
        $msg->log("dom", "alternc_del_member");
        $li = $this->enum_domains();
        foreach ($li as $dom) {
            $this->del_domain($dom);
        }
        return true;
    }


    /** 
     * Returns the used quota for the $name service for the current user.
     * @param $name string name of the quota
     * @return integer the number of service used or false if an error occured
     * @access private
     */
    function hook_quota_get() {
        global $db, $msg, $cuid;
        $msg->debug("dom", "get_quota");
        $q = Array("name" => "dom", "description" => _("Domain name"), "used" => 0);
        $db->query("SELECT COUNT(*) AS cnt FROM domaines WHERE compte= ?", array($cuid));
        if ($db->next_record()) {
            $q['used'] = $db->f("cnt");
        }
        return $q;
    }


    /**
     * Returns the global domain(s) configuration(s) of a particular user
     * No parameters needed 
     */
    function alternc_export_conf() {
        global $msg;
        $msg->log("dom", "export");
        $this->enum_domains();
        $str = "";
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
     * complex process to manage domain and subdomain updates
     * Launched every minute by a cron as root 
     * should launch hooks for each domain or subdomain,
     * so that apache & bind could do their job
     */
    function update_domains() {
        global $db, $hooks;
        if (posix_getuid()!=0) {
            echo "FATAL: please lauch me as root\n";
            exit();
        }

        $this->lock();

        // fix in case we forgot to delete SUBDOMAINS before deleting a DOMAIN
        $db->query("UPDATE sub_domaines sd, domaines d SET sd.web_action = 'DELETE' WHERE sd.domaine = d.domaine AND sd.compte=d.compte AND d.dns_action = 'DELETE';");
        
        // Search for things to do on DOMAINS:
        $db->query("SELECT * FROM domaines WHERE dns_action!='OK';");
        $alldoms=array();
        while ($db->next_record()) {
            $alldoms[$db->Record["id"]]=$db->Record;
        }
        // now launch hooks
        if (count($alldoms)) {
            $hooks->invoke("hook_updatedomains_dns_pre");
            foreach($alldoms as $id=>$onedom) {
                if ($onedom["gesdns"]==0 || $onedom["dns_action"]=="DELETE") {
                    $ret = $hooks->invoke("hook_updatedomains_dns_del",array(array($onedom)));
                } else {
                    $ret = $hooks->invoke("hook_updatedomains_dns_add",array(array($onedom)));
                }

                if ($onedom["dns_action"]=="DELETE") {
                    $db->query("DELETE FROM domaines WHERE domaine=?;",array($onedom));
                } else {
                    // we keep the highest result returned by hooks...
                    rsort($ret,SORT_NUMERIC); $returncode=$ret[0];
                    $db->query("UPDATE domaines SET dns_result=?, dns_action='OK' WHERE domaine=?;",array($returncode,$onedom));
                }
            }
            $hooks->invoke("hook_updatedomains_dns_post");
        }


        // Search for things to do on SUB-DOMAINS:
        $db->query("SELECT sd.*, dt.only_dns FROM domaines_type dt, sub_domaines sd WHERE dt.name=sd.type AND sd.web_action!='OK';");
        $alldoms=array();
        $ignore=array();
        while ($db->next_record()) {
            // only_dns=1 => weird, we should not have web_action SET to something else than OK ... anyway, skip it
            if ($db->Record["only_dns"]) {
                $ignore[]=$db->Record["id"];
            } else {
                $alldoms[$db->Record["id"]]=$db->Record;
            }
        }
        foreach($ignore as $id) {
            // @FIXME (unsure it's useful) maybe we could check that no file exist for this subdomain ?
            $db->query("UPDATE sub_domaines SET web_action='OK' WHERE id=?;",array($id));
        }
        // now launch hooks
        if (count($alldoms)) {
            $hooks->invoke("hook_updatedomains_web_pre");
            foreach($alldoms as $id=>$subdom) {
                // is it a delete (DISABLED or DELETE)
                if ($subdom["web_action"]=="DELETE" || strtoupper(substr($subdom["enable"],0,7))=="DISABLE") {
                    $ret = $hooks->invoke("hook_updatedomains_web_del",array($subdom["id"]));
                } else {
                    $hooks->invoke("hook_updatedomains_web_before",array($subdom["id"])); // give a chance to get SSL cert before ;) 
                    $ret = $hooks->invoke("hook_updatedomains_web_add",array($subdom["id"]));
                    $hooks->invoke("hook_updatedomains_web_after",array($subdom["id"]));
                }

                if ($subdom["web_action"]=="DELETE") {
                    $db->query("DELETE FROM sub_domaines WHERE id=?;",array($id));
                } else {
                    // we keep the highest result returned by hooks...
                    rsort($ret,SORT_NUMERIC); $returncode=$ret[0];
                    $db->query("UPDATE sub_domaines SET web_result=?, web_action='OK' WHERE id=?;",array($returncode,$id));
                }
            }
            $hooks->invoke("hook_updatedomains_web_post");
        }
        
        $this->unlock();
    }

    
    /**
     * Return an array with all the needed parameters to generate conf 
     * of a vhost.
     * If no parameters, return the parameters for ALL the vhost.
     * Optionnal parameters: id of the sub_domaines
     * */
    function generation_parameters($id = null, $only_apache = true) {
        global $db, $msg;
        $msg->log("dom", "generation_parameters");
        $params = "";
        /** 2016_05_18 : this comments was here before escaping the request... is there still something to do here ?
         *   // BUG BUG BUG FIXME
         *   // Suppression de comptes -> membres existe pas -> domaines a supprimer ne sont pas lister
         */
        $query  = "
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
                  and lower(dt.name) = lower(sd.type)"; 
        $query_args =   array();

        if (!is_null($id) && intval($id) == $id) {
            $query .= " AND sd.id = ? ";
            array_push($query_args, intval($id));
        }
        if ($only_apache) {
            $query .=" and dt.only_dns is false ";
        }

        $query  .=  "
                order by 
                  m.login, 
                  sd.domaine, 
                  sd.sub;";

        
        $db->query($query, $query_args);

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
            if ($v['only_dns'] == true) {
                continue;
            }
            if (!$j = file_get_contents(ALTERNC_APACHE2_GEN_TMPL_DIR . '/' . strtolower($k) . '.conf')) {
                die("Error: missing file for $k");
            }
            $d[$k] = $v;
            $d[$k]['tpl'] = $j;
        }
        return $d;
    }


    /**
     *  Launch old fashionned hooks as there was in AlternC 1.0
     * @TODO: do we still need that?
     */
    function generate_conf_oldhook($action, $lst_sub, $sub_obj = null) {
        if (is_null($sub_obj)) {
            $sub_obj = $this->generation_parameters(null, false);
        }
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

        $ret = '';
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


    /**
     *  Return an array with the list of id of sub_domains waiting for an action
     */
    function generation_todo() {
        global $db, $msg;
        $msg->debug("dom", "generation_todo");
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
     * @param string $dns_action
     */
    function set_dns_action($domain, $dns_action) {
        global $db;
        $db->query("UPDATE domaines SET dns_action= ? WHERE domaine= ?; ", array($dns_action, $domain));
        return true;
    }


    function set_dns_result($domain, $dns_result) {
        global $db;
        $db->query("UPDATE domaines SET dns_result= ? WHERE domaine= ?; ", array($dns_result, $domain));
        return true;
    }


    /** 
     * List if there is problems in the domains.
     *  Problems can appear when editing domains type properties
     */
    function get_problems($domain) {
        $this->lock();
        $da = $this->get_domain_all($domain);
        $this->unlock();

        $errors = array();
        // Check if there is more than 1 apache conf
        // by subdomain
        $tmp = array();
        foreach ($da['sub'] as $sub) {
            if ($sub['web_action'] != 'OK') {
                continue;
            }
            if (!$sub['only_dns']) {
                if (!isset($tmp[$sub['fqdn']])) {
                    $tmp[$sub['fqdn']] = 0;
                }
                $tmp[$sub['fqdn']] ++;
                if ($tmp[$sub['fqdn']] >= 2) {
                    $errors[$sub['fqdn']] = sprintf(_("Problem on %s: there is more than 1 web configuration going to be generated for this sub-domain."), $sub['fqdn']);
                }
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

} /* Class m_domains */
