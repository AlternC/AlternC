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

/**
 * This class handle emails (pop and/or aliases and even wrapper for internal
 * classes) of hosted users.
 *
 * This class is directly using the following alternc MySQL tables:
 * address = any used email address will be defined here, mailbox = pop/imap mailboxes, recipient = redirection from an email to another
 * and indirectly the domain class, to know domain names from their id in the DB.
 * This class is also defining a few hooks, search ->invoke in the code.
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */
class m_mail {

    /** 
     * domain list for this account
     * @access private
     */
    var $domains;


    /** 
     * If an email has those chars, 'not nice in shell env' ;) 
     * we don't store the email in $mail/u/{user}_domain, but in $mail/_/{address_id}_domain
     * @access private
     */
    var $specialchars = array('"', "'", '\\', '/');


    /** 
     * If an email has those chars, we will ONLY allow RECIPIENTS, NOT POP/IMAP for DOVECOT !
     * Since Dovecot doesn't allow those characters
     * @access private
     */
    var $forbiddenchars = array('"', "'", '\\', '/', '?', '!', '*', '$', '|', '#', '+');


    /** 
     * Number of results for a pager display
     * @access public
     */
    var $total;

    // Human server name for help
    var $srv_postfix;
    var $srv_dovecot;
    var $cache_domain_mail_size = array();
    var $enum_domains = array();


    /**
     * Constructeur
     */
    function m_mail() {
        global $L_FQDN;
        $this->srv_postfix = variable_get('fqdn_postfix', $L_FQDN, 'FQDN name for humans for smtp services', array('desc' => 'Name', 'type' => 'string'));
        $this->srv_dovecot = variable_get('fqdn_dovecot', $L_FQDN, 'FQDN name for humans for pop/imap services', array('desc' => 'Name', 'type' => 'string'));
    }


    /**
     * Hook called by menu class to add the email menu to the left pane 
     */
    function hook_menu() {
        $obj = array(
            'title' => _("Email Addresses"),
            'link' => 'toggle',
            'pos' => 30,
            'links' => array(),
        );

        foreach ($this->enum_domains() as $d) {
            $obj['links'][] = array(
                'txt' => htmlentities($d["domaine"]) . '&nbsp;' . htmlentities("(" . $d["nb_mail"] . ")"),
                'url' => "mail_list.php?domain_id=" . urlencode($d['id']),
            );
        }

        return $obj;
    }


    function get_total_size_for_domain($domain) {
        global $db;
        if (empty($this->cache_domain_mail_size)) {
            $db->query("SELECT SUBSTRING_INDEX(user,'@', -1) as domain, SUM(quota_dovecot) AS sum FROM dovecot_quota group by domain ;");
            while ($db->next_record()) {
                $dd = $db->f('domain');
                $this->cache_domain_mail_size[$dd] = $db->f('sum');
            }
        }
        if (isset($this->cache_domain_mail_size[$domain])) {
            return $this->cache_domain_mail_size[$domain];
        }
        return 0;
    }


    /**
     * @param string $domain_id
     */
    function catchall_getinfos($domain_id) {
        global $dom, $db;
        $rr = array(
            'mail_id' => '',
            'domain' => $dom->get_domain_byid($domain_id),
            'target' => '',
            'type' => '',
        );

        $db->query("select r.recipients as dst, a.id mail_id from address a, recipient r where a.domain_id = ? and r.address_id = a.id and a.address='';", array($domain_id));
        if ($db->next_record()) {
            $rr['target'] = $db->f('dst');
            $rr['mail_id'] = $db->f('mail_id');
        }

        // Does it redirect to a specific mail or to a domain
        if (empty($rr['target'])) {
            $rr['type'] = 'none';
        } elseif (substr($rr['target'], 0, 1) == '@') {
            $rr['type'] = 'domain';
        } else {
            $rr['type'] = 'mail';
        }

        return $rr;
    }


    /**
     * @param string $domain_id
     */
    function catchall_del($domain_id) {
        $catch = $this->catchall_getinfos($domain_id);
        if (empty($catch['mail_id'])) {
            return false;
        }
        return $this->delete($catch['mail_id']);
    }


    /**
     * @param string $domain_id
     * @param string $target
     */
    function catchall_set($domain_id, $target) {
        global $msg;
        $target = rtrim($target);
        if (strlen($target) > 0 && substr_count($target, '@') == 0) { // Pas de @
            $target = '@' . $target;
        }

        if (substr($target, 0, 1) == '@') { // the first character is @
            // FIXME validate domain
        } else { // it MUST be an email
            if (!filter_var($target, FILTER_VALIDATE_EMAIL)) {
                $msg->raise("ERROR", "mail", _("The email you entered is syntaxically incorrect"));
                return false;
            }
        }
        $this->catchall_del($domain_id);
        return $this->create_alias($domain_id, '', $target, "catchall", true);
    }


    /** 
     * get_quota (hook for quota class), returns the number of used 
     * service for a quota-bound service
     * @param $name string the named quota we want
     * @return the number of used service for the specified quota, 
     * or false if I'm not the one for the named quota
     */
    function hook_quota_get() {
        global $db, $msg, $cuid, $quota;
        $msg->log("mail", "getquota");
        $q = Array("name" => "mail", "description" => _("Email addresses"), "used" => 0);
        $db->query("SELECT COUNT(*) AS cnt FROM address a, domaines d WHERE a.domain_id=d.id AND d.compte= ? AND a.type='';", array($cuid));
        if ($db->next_record()) {
            $q['used'] = $db->f("cnt");
            $q['sizeondisk'] =  $quota->get_size_mail_sum_user($cuid)/1024;
        }
        return $q;
    }


    /** 
     * Password policy kind used in this class (hook for admin class)
     * @return array an array of policykey => "policy name (for humans)"
     */
    function alternc_password_policy() {
        return array("pop" => _("Email account password"));
    }


    /** 
     * Returns the list of mail-hosting domains for a user
     * @return array indexed array of hosted domains
     */
    function enum_domains($uid = -1) {
        global $db, $msg, $cuid;
        $msg->log("mail", "enum_domains");
        if ($uid == -1) {
            $uid = $cuid;
        }
        $db->query("
SELECT
  d.id,
  d.domaine,
  IFNULL( COUNT(a.id), 0) as nb_mail
FROM
  domaines d LEFT JOIN address a ON (d.id=a.domain_id AND a.type='')
WHERE
  d.compte = ? 
  and d.gesmx = 1
GROUP BY
  d.id
ORDER BY
  d.domaine
;
", array($uid));
        $this->enum_domains = array();
        while ($db->next_record()) {
            $this->enum_domains[] = $db->Record;
        }
        return $this->enum_domains;
    }


    /** 
     * available: tells if an email address can be installed in the server
     * check the domain part (is it mine too), the syntax, and the availability.
     * @param $mail string email to check
     * @return boolean true if the email can be installed on the server 
     */
    function available($mail) {
        global $db, $msg, $dom;
        $msg->log("mail", "available");
        list($login, $domain) = explode("@", $mail, 2);
        // Validate the domain ownership & syntax
        if (!($dom_id = $dom->get_domain_byname($domain))) {
            return false;
        }
        // Validate the email syntax:
        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $msg->raise("ERROR", "mail", _("The email you entered is syntaxically incorrect"));
            return false;
        }
        // Check the availability
        $db->query("SELECT a.id FROM address a WHERE a.domain_id= ? AND a.address= ?;", array($dom_id, $login));
        if ($db->next_record()) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * function used to list every mail address hosted on a domain.
     * @param $dom_id integer the domain id.
     * @param $search string search that string in recipients or address.
     * @param $offset integer skip THAT much emails in the result.
     * @param $count integer return no more than THAT much emails. -1 for ALL. Offset is ignored then.
     * @result an array of each mail hosted under the domain.
     */
    function enum_domain_mails($dom_id = null, $search = "", $offset = 0, $count = 30, $show_systemmails = false) {
        global $db, $msg, $hooks;
        $msg->log("mail", "enum_domains_mail");

        $query_args = array($dom_id);
        $search     = trim($search);
        $where      = " a.domain_id = ? ";

        if ($search) {
            $where .= " AND (a.address LIKE ? OR r.recipients LIKE ? )";
            array_push($query_args, "%" . $search . "%", "%" . $search . "%");
        }
        if (!$show_systemmails) {
            $where .= " AND type='' ";
        }
        $db->query("SELECT count(a.id) AS total FROM address a LEFT JOIN recipient r ON r.address_id=a.id WHERE " .  $where . ";", $query_args);
        $db->next_record();
        $this->total = $db->f("total");
        if ($count != -1) {
            $offset = intval($offset);
            $count = intval($count);
            $limit = " LIMIT $offset, $count "; 
        } else {
            $limit = "";
        }
        $db->query("SELECT a.id, a.address, a.password, a.`enabled`, a.mail_action, d.domaine AS domain, m.quota, m.quota*1024*1024 AS quotabytes, q.quota_dovecot as used, NOT ISNULL(m.id) AS islocal, a.type, r.recipients, m.lastlogin, a.domain_id
         FROM ((domaines d, address a LEFT JOIN mailbox m ON m.address_id=a.id) LEFT JOIN dovecot_quota q ON CONCAT(a.address,'@',d.domaine)  = q.user) LEFT JOIN recipient r ON r.address_id=a.id
         WHERE " . $where . " AND d.id=a.domain_id " . $limit . " ;", $query_args);
        if (!$db->next_record()) {
            $msg->raise("ERROR", "mail", _("No email found for this query"));
            return array();
        }
        $res = array();
        do {
            $details = $db->Record;
            // if necessary, fill the typedata with data from hooks ...
            if ($details["type"]) {
                $result = $hooks->invoke("hook_mail_get_details", array($details)); // Will fill typedata if necessary
                $details["typedata"] = implode("<br />", $result);
            }
            $res[] = $details;
        } while ($db->next_record());
        return $res;
    }

    function hook_mail_get_details($detail) {
        if ($detail['type'] == 'catchall') {
            return _(sprintf("Special mail address for catch-all. <a href='mail_manage_catchall.php?domain_id=%s'>Click here to manage it.</a>", $detail['domain_id']));
        }
    }


    /** 
     * Function used to insert a new mail into the db
     * should be used by the web interface, not by third-party programs.
     *
     * This function calls the hook "hooks_mail_cancreate"
     * which must return FALSE if the user can't create this email, and raise and error accordingly
     * 
     * @param $dom_id integer A domain_id (owned by the user) 
     * (will be the part at the right of the @ in the email)
     * @param $mail string the left part of the email to create (something@dom_id)
     * @return an hashtable containing the database id of the newly created mail, 
     * or false if an error occured ($msg is filled accordingly)
     */
    function create($dom_id, $mail, $type = "", $dontcheck = false) {
        global $msg, $db, $quota, $dom, $hooks;
        $msg->log("mail", "create", $mail);

        // Validate the domain id
        if (!($domain = $dom->get_domain_byid($dom_id))) {
            return false;
        }

        // Validate the email syntax:
        $m = $mail . "@" . $domain;
        if (!filter_var($m, FILTER_VALIDATE_EMAIL) && !$dontcheck) {
            $msg->raise("ERROR", "mail", _("The email you entered is syntaxically incorrect"));
            return false;
        }

        // Call other classes to check we can create it:
        $cancreate = $hooks->invoke("hook_mail_cancreate", array($dom_id, $mail));
        if (in_array(false, $cancreate, true)) {
            return false;
        }

        // Check the quota:
        if (($type=="")&&!$quota->cancreate("mail")) {
            $msg->raise("ALERT", "mail", _("You cannot create email addresses: your quota is over"));
            return false;
        }
        // Already exists?
        $db->query("SELECT * FROM address WHERE domain_id= ? AND address= ? ;", array($dom_id, $mail));
        if ($db->next_record()) {
            if ($db->f("type") == "mailman")
                $msg->raise("ERROR", "mail", _("This email address already exists in mailman"));
            else
                $msg->raise("ERROR", "mail", _("This email address already exists"));

            return false;
        }
        // Create it now
        $db->query("INSERT INTO address (domain_id, address,type) VALUES (?, ?, ?);", array($dom_id, $mail, $type));
        if (!($id = $db->lastid())) {
            $msg->raise("ERROR", "mail", _("An unexpected error occured when creating the email"));
            return false;
        }
        return $id;
    }


    /** 
     * function used to get every information we can on a mail 
     * @param $mail_id integer
     * @return array a hashtable with all the informations for that email
     */
    function get_details($mail_id) {
        global $db, $msg, $hooks;
        $msg->log("mail", "get_details");

        $mail_id = intval($mail_id);
        // Validate that this email is owned by me...
        if (!($mail = $this->is_it_my_mail($mail_id))) {
            return false;
        }

        // We fetch all the informations for that email: these will fill the hastable : 
        $db->query("SELECT a.id, a.address, a.password, a.enabled, d.domaine AS domain, m.path, m.quota, m.quota*1024*1024 AS quotabytes, q.quota_dovecot AS used, NOT ISNULL(m.id) AS islocal, a.type, r.recipients, m.lastlogin, a.mail_action, m.mail_action AS mailbox_action FROM ((domaines d, address a LEFT JOIN mailbox m ON m.address_id=a.id) LEFT JOIN dovecot_quota q ON CONCAT(a.address,'@',d.domaine)  = q.user) LEFT JOIN recipient r ON r.address_id=a.id WHERE a.id= ? AND d.id=a.domain_id;", array($mail_id));
        if (!$db->next_record()) {
            return false;
        }
        $details = $db->Record;
        // if necessary, fill the typedata with data from hooks ...
        if ($details["type"]) {
            $result = $hooks->invoke("hook_mail_get_details", array($mail_id)); // Will fill typedata if necessary
            $details["typedata"] = implode("<br />", $result);
        }
        return $details;
    }

    private $isitmy_cache = array();


    /** 
     * Check if an email is mine ...
     *
     * @param $mail_id integer the number of the email to check
     * @return string the complete email address if that's mine, false if not
     * ($msg is filled accordingly)
     */
    function is_it_my_mail($mail_id) {
        global $msg, $db, $cuid;
        $mail_id = intval($mail_id);
        // cache it (may be called more than one time in the same page).
        if (isset($this->isitmy_cache[$mail_id])) {
            return $this->isitmy_cache[$mail_id];
        }
        $db->query("SELECT concat(a.address,'@',d.domaine) AS email FROM address a, domaines d WHERE d.id=a.domain_id AND a.id= ? AND d.compte= ?;", array($mail_id, $cuid));
        if ($db->next_record()) {
            return $this->isitmy_cache[$mail_id] = $db->f("email");
        } else {
            $msg->raise("ERROR", "mail", _("This email is not yours, you can't change anything on it"));
            return $this->isitmy_cache[$mail_id] = false;
        }
    }


    /** 
     * Hook called when the DOMAIN class will delete a domain.
     * OR when the DOMAIN class tells us we don't host the emails of this domain anymore.
     * @param $dom the ID of the domain to delete
     * @return boolean if the email has been properly deleted
     * or false if an error occured ($msg is filled accordingly)
     */
    function hook_dom_del_mx_domain($dom_id) {
        global $db;
        $list = $this->enum_domain_mails($dom_id, "", 0, -1);
        if (is_array($list)) {
            foreach ($list as $one) {
                $this->delete($one["id"]);
            }
        }
        $db->query("SELECT domaine FROM domaines WHERE id= ? ;", array($dom_id));
        if ($db->next_record()) {
            $db->query("UPDATE sub_domaines SET web_action='DELETE' WHERE domaine= ? AND type='txt' AND (sub='' AND valeur LIKE 'v=spf1 %') OR (sub='_dmarc' AND valeur LIKE 'v=dmarc1;%');", array($db->Record["domaine"]));
            $db->query("UPDATE sub_domaines SET web_action='DELETE' WHERE domaine= ? AND (type='defmx' OR type='defmx2');", array($db->Record["domaine"]));
            $db->query("UPDATE domaines SET dns_action='UPDATE' WHERE id= ? ;", array($dom_id));
        }

        return true;
    }


    /**
     * return the alternc account's ID of the mail_id
     */
    function get_account_by_mail_id($mail_id) {
        global $db;
        $db->query("select compte as uid from domaines d, address a where a.domain_id = d.id and a.id = ? ;", array($mail_id));
        if (!$db->next_record()) {
            return false;
        }
        return $db->f('uid');
    }


    /** 
     * Function used to delete a mail from the db
     * should be used by the web interface, not by third-party programs.
     *
     * @param $mail_id integer the number of the email to delete
     * @return boolean if the email has been properly deleted 
     * or false if an error occured ($msg is filled accordingly)
     */
    function delete($mail_id) {
        global $msg, $db, $hooks;
        $msg->log("mail", "delete");

        $mail_id = intval($mail_id);

        if (!$mail_id) {
            $msg->raise("ERROR", "mail", _("The email you entered is syntaxically incorrect"));
            return false;
        }
        // Validate that this email is owned by me...
        if (!($mail = $this->is_it_my_mail($mail_id))) {
            return false;
        }

        $mailinfos = $this->get_details($mail_id);
        $hooks->invoke('hook_mail_delete', array($mail_id, $mailinfos['address'] . '@' . $mailinfos['domain']));

        // Search for that address:
        $db->query("SELECT a.id, a.type, a.mail_action, m.mail_action AS mailbox_action, NOT ISNULL(m.id) AS islocal FROM address a LEFT JOIN mailbox m ON m.address_id=a.id WHERE a.id= ? ;", array($mail_id));
        if (!$db->next_record()) {
            $msg->raise("ERROR", "mail", _("The email %s does not exist, it can't be deleted"), $mail);
            return false;
        }
        if ($db->f("mail_action") != "OK" || ($db->f("islocal") && $db->f("mailbox_action") != "OK")) { // will be deleted soon ...
            $msg->raise("ERROR", "mail", _("The email %s is already marked for deletion, it can't be deleted"), $mail);
            return false;
        }
        $mail_id = $db->f("id");

        if ($db->f("islocal")) {
            // If it's a pop/imap mailbox, mark it for deletion
            $db->query("UPDATE address SET mail_action='DELETE', enabled=0 WHERE id= ?;", array($mail_id));
            $db->query("UPDATE mailbox SET mail_action='DELETE' WHERE address_id= ?;", array($mail_id));
        } else {
            // If it's only aliases, delete it NOW.
            $db->query("DELETE FROM address WHERE id= ? ;", array($mail_id));
            $db->query("DELETE FROM mailbox WHERE address_id= ? ;", array($mail_id));
            $db->query("DELETE FROM recipient WHERE address_id= ? ;", array($mail_id));
        }
        return true;
    }


    /** 
     * Function used to undelete a pending deletion mail from the db
     * should be used by the web interface, not by third-party programs.
     *
     * @param $mail_id integer the email id
     * @return boolean if the email has been properly undeleted 
     * or false if an error occured ($msg is filled accordingly)
     */
    function undelete($mail_id) {
        global $msg, $db;
        $msg->log("mail", "undelete");

        $mail_id = intval($mail_id);

        if (!$mail_id) {
            $msg->raise("ERROR", "mail", _("The email you entered does not exist"));
            return false;
        }
        // Validate that this email is owned by me...
        if (!($mail = $this->is_it_my_mail($mail_id))) {
            return false;
        }

        // Search for that address:
        $db->query("SELECT a.id, a.type, a.mail_action, m.mail_action AS mailbox_action, NOT ISNULL(m.id) AS islocal FROM address a LEFT JOIN mailbox m ON m.address_id=a.id WHERE a.id= ? ;", array($mail_id));
        if (!$db->next_record()) {
            $msg->raise("ERROR", "mail", _("The email %s does not exist, it can't be undeleted"), $mail);
            return false;
        }
        if ($db->f("type") != "") { // Technically special : mailman, sympa ... 
            $msg->raise("ERROR", "mail", _("The email %s is special, it can't be undeleted"), $mail);
            return false;
        }
        if ($db->f("mailbox_action") != "DELETE" || $db->f("mail_action") != "DELETE") { // will be deleted soon ...
            $msg->raise("ALERT", "mail", _("Sorry, deletion of email %s is already in progress, or not marked for deletion, it can't be undeleted"), $mail);
            return false;
        }
        $mail_id = $db->f("id");

        if ($db->f("islocal")) {
            // If it's a pop/imap mailbox, mark it for deletion
            $db->query("UPDATE address SET mail_action='OK', `enabled`=1 WHERE id= ?;", array($mail_id));
            $db->query("UPDATE mailbox SET mail_action='OK' WHERE address_id= ? ;", array($mail_id));
            return true;
        } else {
            $msg->raise("ERROR", "mail", _("-- Program Error -- The email %s can't be undeleted"), $mail);
            return false;
        }
    }


    /** 
     * set the password of an email address.
     * @param $mail_id integer email ID 
     * @param $pass string the new password.
     * @return boolean true if the password has been set, false else, raise an error.
     */
    function set_passwd($mail_id, $pass, $canbeempty = false) {
        global $db, $msg, $admin;
        $msg->log("mail", "setpasswd");

        if (!($email = $this->is_it_my_mail($mail_id))) {
            return false;
        }
        if (!$admin->checkPolicy("pop", $email, $pass, $canbeempty)) {
            return false;
        }
        if ($canbeempty && empty($pass)) {
            return $db->query("UPDATE address SET password= ? where id = ? ;",
                              array(null, $mail_id ));
        } else if (!$db->query("UPDATE address SET password= ? where id = ? ;",
                               array(_dovecot_hash($pass), $mail_id ))) {
            return false;
        }
        return true;
    }


    /** 
     * Enables an email address.
     * @param $mail_id integer Email ID
     * @return boolean true if the email has been enabled.
     */
    function enable($mail_id) {
        global $db, $msg;
        $msg->log("mail", "enable");
        if (!($email = $this->is_it_my_mail($mail_id))) {
            return false;
        }
        if (!$db->query("UPDATE address SET `enabled`=1 where id= ? ;", array($mail_id))) {
            return false;
        }
        return true;
    }


    /** 
     * Disables an email address.
     * @param $mail_id integer Email ID
     * @return boolean true if the email has been enabled.
     */
    function disable($mail_id) {
        global $db, $msg;
        $msg->log("mail", "disable");
        if (!($email = $this->is_it_my_mail($mail_id))) {
            return false;
        }
        if (!$db->query("UPDATE address SET `enabled`=0 where id= ? ;", array($mail_id))) {
            return false;
        }
        return true;
    }


    /** 
     * Function used to update an email settings
     * should be used by the web interface, not by third-party programs.
     *
     * @param $mail_id integer the number of the email to delete
     * @param integer $islocal boolean is it a POP/IMAP mailbox ?
     * @param integer $quotamb integer if islocal=1, quota in MB
     * @param string $recipients string recipients, one mail per line.
     * @return boolean if the email has been properly edited
     * or false if an error occured ($msg is filled accordingly)
     */
    function set_details($mail_id, $islocal, $quotamb, $recipients, $delivery = "dovecot", $dontcheck = false) {
        global $msg, $db;
        $msg->log("mail", "set_details");
        if (!($me = $this->get_details($mail_id))) {
            return false;
        }
        if ($me["islocal"] && !$islocal) {
            // delete pop
            $db->query("UPDATE mailbox SET mail_action='DELETE' WHERE address_id= ? ;", array($mail_id));
        }
        if (!$me["islocal"] && $islocal) {
            // create pop
            $path = "";
            if ($delivery == "dovecot") {
                $path = ALTERNC_MAIL . "/" . substr($me["address"] . "_", 0, 1) . "/" . $me["address"] . "_" . $me["domain"];
            }
            foreach ($this->forbiddenchars as $str) {
                if (strpos($me["address"], $str) !== false) {
                    $msg->raise("ERROR", "mail", _("There is forbidden characters in your email address. You can't make it a POP/IMAP account, you can only use it as redirection to other emails"));
                    return false;
                }
            }
            foreach ($this->specialchars as $str) {
                if (strpos($me["address"], $str) !== false) {
                    $path = ALTERNC_MAIL . "/_/" . $me["id"] . "_" . $me["domain"];
                    break;
                }
            }
            $db->query("INSERT INTO mailbox SET address_id= ? , delivery= ?, path= ? ;", array($mail_id, $delivery, $path));
        }
        if ($me["islocal"] && $islocal && $me["mailbox_action"] == "DELETE") {
            $db->query("UPDATE mailbox SET mail_action='OK' WHERE mail_action='DELETE' AND address_id= ? ;", array($mail_id));
        }

        if ($islocal) {
            if ($quotamb != 0 && $quotamb < (intval($me["used"] / 1024 / 1024) + 1)) {
                $quotamb = intval($me["used"] / 1024 / 1024) + 1;
                $msg->raise("ALERT", "mail", _("You set a quota smaller than the current mailbox size. Since it's not allowed, we set the quota to the current mailbox size"));
            }
            $db->query("UPDATE mailbox SET quota= ? WHERE address_id= ? ;", array($quotamb, $mail_id));
        }

        $recipients = preg_replace('/[\r\t\s]/', "\n", $recipients); // Handle space AND new line
        $r = explode("\n", $recipients);
        $red = "";
        foreach ($r as $m) {
            $m = trim($m);
            if ($m && ( filter_var($m, FILTER_VALIDATE_EMAIL) || $dontcheck)  // Recipient Email is valid
            && $m != ($me["address"] . "@" . $me["domain"])) {  // And not myself (no loop allowed easily ;) )
                $red.=$m . "\n";
            }
        }
        $db->query("DELETE FROM recipient WHERE address_id= ? ;", array($mail_id));
        if (isset($red) && $red) {
            $db->query("INSERT INTO recipient SET address_id= ?, recipients= ? ;", array($mail_id, $red));
        }
        if (!$islocal && !$red) {
            $msg->raise("ALERT", "mail", _("Warning: you created an email which is not an alias, and not a POP/IMAP mailbox. This is certainly NOT what you want to do. To fix this, edit the email address and check 'Yes' in POP/IMAP account, or set some recipients in the redirection field."));
        }
        return true;
    }


    /** 
     * A wrapper used by mailman class to create it's needed addresses 
     * @ param : $dom_id , the domain id associated to a given address
     * @ param : $m , the left part of the  mail address being created
     * @ param : $delivery , the delivery used to deliver the mail
     */
    function add_wrapper($dom_id, $m, $delivery) {
        global $msg, $mail;
        $msg->log("mail", "add_wrapper", "creating $delivery $m address");

        $mail_id = $mail->create($dom_id, $m, $delivery);
        $this->set_details($mail_id, 1, 0, '', $delivery);
        // FIXME return error code
    }


    /** 
     * A function used to create an alias for a specific address
     * @ param : $dom_id , the domain sql identifier
     * @ param : $m , the alias we want to create
     * @ param : $alias , the already existing aliased address
     * @ param : $type, the type of the alias created
     * @param string $m
     * @param string $alias
     * @param string $dom_id
     */
    function create_alias($dom_id, $m, $alias, $type = "", $dontcheck = false) {
        global $msg, $mail;
        $msg->log("mail", "create_alias", "creating $m alias for $alias type $type");

        $mail_id = $mail->create($dom_id, $m, $type, $dontcheck);
        if (!$mail_id) {
            return false;
        }
        $this->set_details($mail_id, 0, 0, $alias, "dovecot", $dontcheck);
        return true;
    }


    /** 
     * A wrapper used by mailman class to create it's needed addresses 
     * @ param : $mail_id , the mysql id of the mail address we want to delete
     * of the email for the current acccount.
     */
    function del_wrapper($mail_id) {
        global $msg;
        $msg->log("mail", "del_wrapper");
        $this->delete($mail_id);
    }


    /** 
     * Export the mail information of an account 
     * @return: str, string containing the complete configuration 
     * of the email for the current acccount.
     */
    function alternc_export_conf() {
        global $msg;
        $msg->log("mail", "export");
        $domain = $this->enum_domains();
        $str = "<mail>\n";
        foreach ($domain as $d) {
            $str.="  <domain>\n    <name>" . xml_entities($d["domain"]) . "</name>\n";
            $s = $this->enum_domain_mails($d["id"]);
            if (count($s)) {
                while (list($key, $val) = each($s)) {
                    $str.="    <address>\n";
                    $str.="      <name>" . xml_entities($val["address"]) . "</name>\n";
                    $str.="      <enabled>" . xml_entities($val["enabled"]) . "</enabled>\n";
                    if (is_array($val["islocal"])) {
                        $str.="      <islocal>1</islocal>\n";
                        $str.="      <quota>" . $val["quota"] . "</quota>\n";
                        $str.="      <path>" . $val["path"] . "</path>\n";
                    } else {
                        $str.="      <islocal>0</islocal>\n";
                    }
                    if (!empty($val["recipients"])) {
                        $r = explode("\n", $val["recipients"]);
                        foreach ($r as $recip) {
                            $str.="      <recipients>" . $recip . "<recipients>\n";
                        }
                    }
                    $str.="    </address>\n";
                }
            }
            $str.="  </domain>\n";
        }
        $str.="</mail>\n";
        return $str;
    }


    /**
     * Return the list of allowed slave accounts (secondary-mx)
     * @return array
     */
    function enum_slave_account() {
        global $db;
        $db->query("SELECT login,pass FROM mxaccount;");
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
     * Check for a slave account (secondary mx)
     * @param string $login the login to check
     * @param string $pass the password to check
     * @return boolean TRUE if the password is correct, or FALSE if an error occurred.
     */
    function check_slave_account($login, $pass) {
        global $db;
        $db->query("SELECT * FROM mxaccount WHERE login= ? AND pass= ?;", array($login, $pass));
        if ($db->next_record()) {
            return true;
        }
        return false;
    }


    /** 
     * Out (echo) the complete hosted domain list : 
     */
    function echo_domain_list($format = null) {
        global $db;
        $db->query("SELECT domaine FROM domaines WHERE gesmx=1 ORDER BY domaine");
        $lst = array();
        $tt = "";
        while ($db->next_record()) {
            $lst[] = $db->f("domaine");
            $tt.=$db->f("domaine");
        }

        // Generate an integrity check 
        $obj = array('integrity' => md5($tt), 'items' => $lst);

        switch ($format) {
        case "json":
            return json_encode($obj);
        default:
            foreach ($lst as $l) {
                echo $l . "\n";
            }
            return true;
        } // switch
    }


    /**
     * Add a slave account that will be allowed to access the mxdomain list
     * @param string $login the login to add
     * @param string $pass the password to add
     * @return boolean TRUE if the account has been created, or FALSE if an error occurred.
     */
    function add_slave_account($login, $pass) {
        global $db, $msg;
        $db->query("SELECT * FROM mxaccount WHERE login= ? ;", array($login));
        if ($db->next_record()) {
            $msg->raise("ERROR", "mail", _("The slave MX account was not found"));
            return false;
        }
        $db->query("INSERT INTO mxaccount (login,pass) VALUES (?, ?);", array($login, $pass));
        return true;
    }


    /**
     * Remove a slave account
     * @param string $login the login to delete
     */
    function del_slave_account($login) {
        global $db;
        $db->query("DELETE FROM mxaccount WHERE login= ? ;", array($login));
        return true;
    }


    /** 
     * hook function called by AlternC when a domain is created for
     * the current user account using the SLAVE DOMAIN feature
     * This function create a CATCHALL to the master domain
     * @param string $domain_id Domain that has just been created
     * @param string $target_domain Master domain 
     * @access private
     */
    function hook_dom_add_slave_domain($domain_id, $target_domain) {
        global $msg;
        $msg->log("mail", "hook_dom_add_slave_domain", $domain_id);
        $this->catchall_set($domain_id, '@' . $target_domain);
        return true;
    }


    /** 
     * hook function called by AlternC when a domain is created for
     * the current user account 
     * This function create a postmaster mail which is an alias to LOGIN @ FQDN
     * wich is a dynamic alias to the alternc's account mail
     * @param string $domain_id Domain that has just been created
     * @access private
     */
    function hook_dom_add_mx_domain($domain_id) {
        global $msg, $mem, $db;
        $msg->log("mail", "hook_dom_add_mx_domain", $domain_id);

        $db->query("SELECT value FROM variable where name='mailname_bounce';");
        if (!$db->next_record()) {
            $msg->raise("ERROR", "mail", _("Problem: can't create default bounce mail"));
            return false;
        }
        $mailname = $db->f("value");
        // set spf & dmarc for this domain
        $db->query("SELECT domaine FROM domaines WHERE id= ?;", array($domain_id));
        if ($db->next_record()) {
            if ($spf = variable_get("default_spf_value")) {
                $this->set_dns_spf($db->Record["domaine"], $spf);
            }
            if ($dmarc = variable_get("default_dmarc_value")) {
                $this->set_dns_dmarc($db->Record["domaine"], $dmarc);
            }
        }
        return $this->create_alias($domain_id, 'postmaster', $mem->user['login'] . '@' . $mailname);
    }


    /** 
     * hook function called by variables when a variable is changed
     * @access private
     */
    function hook_variable_set($name, $old, $new) {
        global $msg, $db;
        $msg->log("mail", "hook_variable_set($name,$old,$new)");

        if ($name == "default_spf_value") {
            $new = trim($new);
            $old = trim($old);
            $db->query("SELECT domaine,login,compte FROM domaines, membres WHERE gesdns=1 AND gesmx=1 and membres.uid=domaines.compte;");
            $res=array();
            while ($db->next_record()) $res[]=$db->Record;
            foreach ($res as $record) {
                $this->set_dns_spf($record["domaine"], $new, $old, $record["compte"], $record["login"]);
            }
        }

        if ($name == "default_dmarc_value") {
            $new = trim($new);
            $old = trim($old);
            $db->query("SELECT domaine,login,compte FROM domaines, membres WHERE gesdns=1 AND gesmx=1 and membres.uid=domaines.compte;");
            $res=array();
            while ($db->next_record()) $res[]=$db->Record;
            foreach ($res as $record) {
                $this->set_dns_dmarc($record["domaine"], $new, $old, $record["compte"], $record["login"]);
            }
        }
    }


    /** 
     * Set or UPDATE the DNS record for the domain $dom(str) to be $spf
     * account's login is current and if not it's $login.
     * don't change spf if current value is not $old
     * @access private
     */
    function set_dns_spf($domain, $spf, $previous = -1, $uid = -1, $login = -1) {
        global $db, $cuid, $mem;
        // defaults
        if ($uid === -1) {
            $uid = intval($cuid);
        } else {
            $uid = intval($uid);
        }
        if ($login === -1) {
            $login = $mem->user["login"];
        }
        // Search for the record in sub_domaines table
        $db->query("SELECT * FROM sub_domaines WHERE compte= ? AND domaine= ? AND sub='' AND type='txt' AND valeur LIKE 'v=spf1 %' AND web_action!='DELETE';", array($uid, $domain));
        if ($db->next_record()) {
            if ($previous !== -1 && $db->Record["valeur"] == "v=spf1 " . $spf) {
                return; // skip, no change asked.
            }
            $db->query("UPDATE sub_domaines SET web_action='DELETE' WHERE id= ? ;",array($db->Record["id"]));
        }
        $db->query("INSERT INTO sub_domaines SET compte= ?, domaine= ?, sub='', type='txt', valeur= ? , web_action='UPDATE';", array($uid, $domain, "v=spf1 " . $spf));
        $db->query("UPDATE domaines SET dns_action='UPDATE' WHERE domaine= ?;", array($domain));
    }


    /** 
     * Set or UPDATE the DNS record for the domain $dom(str) to be $dmarc
     * account's login is current and if not it's $login.
     * don't change dmarc if current value is not $old
     * @access private
     */
    function set_dns_dmarc($domain, $dmarc, $previous = -1, $uid = -1, $login = -1) {
        global $db, $cuid, $mem, $L_FQDN;
        // defaults
        if ($uid === -1) {
            $uid = intval($cuid);
        } else {
            $uid = intval($uid);
        }
        if ($login === -1) {
            $login = $mem->user["login"];
        }
        $dmarc = str_replace("%%ADMINMAIL%%", "admin@" . $L_FQDN, $dmarc);
        $dmarc = str_replace("%%USERMAIL%%", $login . "@" . $L_FQDN, $dmarc);

        // Search for the record in sub_domaines table
        $db->query("SELECT * FROM sub_domaines WHERE compte= ? AND domaine= ? AND sub='_dmarc' AND type='txt' AND valeur LIKE 'v=dmarc1;%' AND web_action!='DELETE';", array($uid, $domain));
        if ($db->next_record()) {
            if ($previous !== -1 && $db->Record["valeur"] == "v=dmarc1;" . $dmarc) {
                return; // skip, no change asked.
            }
            $db->query("UPDATE sub_domaines SET web_action='DELETE' WHERE id= ?;", array($db->Record["id"]));
        }
        $db->query("INSERT INTO sub_domaines SET compte= ?, domaine= ?, sub='_dmarc', type='txt', valeur= ?, web_action='UPDATE';", array($uid, $domain, "v=dmarc1;" . $dmarc));
        $db->query("UPDATE domaines SET dns_action='UPDATE' WHERE domaine= ?;", array($domain));
    }



} /* Class m_mail */
