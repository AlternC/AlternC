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
 * Manage the AlternC's account administration (create/edit/delete)
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */
class m_admin {


    /** 
     * $enabled tells if the logged user is super-admin or not
     */
    var $enabled = 0;


    /** List of the controls made for each TLD
     *
     * $tldmode is used by the administration panel, while choosing
     * the authorized TLDs. It's an array of strings explaining the current state of the TLD.
     */
    public $tldmode = array();
    var $archive = '';


    /**
     * Constructor
     * 
     * @global	type $db
     * @global	type $cuid
     */
    function __construct() {
        global $db, $cuid;
        $db->query("SELECT su FROM membres WHERE uid=?;", array($cuid));
        $db->next_record();
        $this->enabled = $db->f("su");

        $this->tldmode = array(
            0 => _("This TLD is forbidden"),
            1 => _("primary DNS is checked in WHOIS db"),
            2 => _("primary & secondary DNS are checked in WHOIS db"),
            3 => _("Domain must exist, but don't do any DNS check"),
            4 => _("Domain can be installed, no check at all"),
            5 => _("Domain can be installed, force NO DNS hosting"),
        );
        $this->archive = variable_get('archive_del_data', '', 'If folder specified html folder of deleted user is archived, else it is deleted. ');
    }


    /**
     * Hook function called by the menu class to add menu to the left panel.
     * @global	type $mem
     * @global	type $cuid
     * @global	type $debug_alternc
     * @global	type $L_INOTIFY_UPDATE_DOMAIN
     * @return boolean|string
     */
    function hook_menu() {
        global $mem, $cuid, $debug_alternc, $L_INOTIFY_UPDATE_DOMAIN;
        if (!$mem->checkRight()) {
            return false;
        }
        $obj = array(
            'title' => _("Administration"),
            'link' => 'toggle',
            'class' => 'adminmenu',
            'pos' => 10,
            'links' =>
            array(
                array(
                    'txt' => _("Manage AlternC accounts"),
                    'url' => 'adm_list.php',
                    'class' => 'adminmenu'
                ),
                array(
                    'txt' => _("User Quotas"),
                    'url' => 'quotas_users.php?mode=4',
                    'class' => 'adminmenu'
                ),
            )
        );

        if ($cuid == 2000) { // only ADMIN, not available to subadmins
            $obj['links'][] = array(
                'txt' => _("Admin Control Panel"),
                'url' => 'adm_panel.php',
                'class' => 'adminmenu'
            );
            $obj['links'][] = array(
                'txt' => _("PhpMyAdmin"),
                'url' => 'sql_pma_sso.php',
                'class' => 'adminmenu',
                'target' => '_blank',
            );
            $obj['links'][] = array(
                'txt' => ($debug_alternc->status) ? _("Switch debug Off") : _("Switch debug On"),
                'url' => "alternc_debugme.php?enable=" . ($debug_alternc->status ? "0" : "1"),
                'class' => 'adminmenu'
            );
            if (empty($L_INOTIFY_UPDATE_DOMAIN) || file_exists("$L_INOTIFY_UPDATE_DOMAIN")) {
                $obj['links'][] = array(
                    'txt' => _("Applying..."),
                    'url' => 'javascript:alert(\'' . _("Domain changes are already applying") . '\');',
                    'class' => 'adminmenu',
                );
            } else {
                $obj['links'][] = array(
                    'txt' => _("Apply changes"),
                    'url' => 'adm_update_domains.php',
                    'class' => 'adminmenu',
                    'onclick' => 'return confirm("' . addslashes(_("Server configuration changes are applied every 5 minutes. Do you want to do it right now?")) . '");',
                );
            } // L_INOTIFY_UPDATE_DOMAIN
        } // cuid == 2000


        return $obj;
    }


    /**
     * Password kind used in this class (hook for admin class)
     *
     * @return array
     */
    function alternc_password_policy() {
        return array("adm" => "Administration section");
    }


    /**
     * 
     */
    function stop_if_jobs_locked() {
        if (file_exists(ALTERNC_LOCK_JOBS)) {
            echo "There is a file " . ALTERNC_LOCK_JOBS . "\n";
            echo "So no jobs are allowed\n";
            echo "Did you launch alternc.install ?\n";
            die();
        }
    }


    /**
     * return the uid of an alternc account
     * 
     * @global	type $db
     * @param type $login
     * @return null
     */
    function get_uid_by_login($login) {
        global $db;
        $db->query("SELECT uid FROM membres WHERE login= ?;", array($login));
        if (!$db->next_record()) {
            return null;
        }
        return $db->f('uid');
    }


    /**
     * return the name of an alternc account
     *
     * @global	type $db
     * @param type $uid
     * @return null if missing
     */
    function get_login_by_uid($uid) {
        global $db;
        $db->query("SELECT login FROM membres WHERE uid= ?;", array($uid));
        if (!$db->next_record()) {
            return null;
        }
        return $db->f('login');
    }


    /**
     * Returns the known information about a hosted account
     * 
     * Returns all what we know about an account (contents of the tables
     *  <code>membres</code> et <code>local</code>)
     * Ckecks if the account is super-admin
     * 
     * @global	   type $msg
     * @global	   type $db
     * @global	   string     $lst_users_properties
     * @param     int         $uid a unique integer identifying the account
     * @param     boolean     $recheck
     * @return array|boolean an associative array containing all the fields of the
     * table <code>membres</code> and <code>local</code> of the corresponding account.
     * Returns FALSE if an error occurs.
     */
    function get($uid, $recheck = false) {
        global $msg, $db, $lst_users_properties;
        $msg->debug("admin","get",$uid);
        if (!$this->enabled) {
            $msg->raise("ERROR", "admin", _("-- Only administrators can access this page! --"));
            return false;
        }

        if (!isset($lst_users_properties) || empty($lst_users_properties) || !is_array($lst_users_properties) || $recheck) {
            $lst_users_properties = array();
            $db->query("
	SELECT 
		m.uid as muid, 
		l.*, 
		m.*, 
		parent.login as parentlogin,
		dbs.name as db_server_name,
		m.renewed + INTERVAL m.duration MONTH as expiry,
		CASE 
			WHEN m.duration IS NULL THEN 0 
			WHEN m.renewed + INTERVAL m.duration MONTH <= NOW() THEN 3	
			WHEN m.renewed <= NOW() THEN 2
		ELSE 1 END 'status'
		
	FROM membres as m 
		LEFT JOIN membres as parent ON (parent.uid = m.creator) 
		LEFT JOIN db_servers as dbs ON (m.db_server_id = dbs.id)
		LEFT JOIN local as l ON (m.uid = l.uid) ;");
            while ($db->next_record()) {
                $lst_users_properties[$db->f('muid')] = $db->Record;
            }
        }

        if (!isset($lst_users_properties[$uid])) {
            if (!$recheck) {
                // don't exist, but is not a forced check. Do a forced check
                return $this->get($uid, true);
            }
            $msg->raise("ERROR", "admin", _("Account not found"));
            return false;
        }

        return $lst_users_properties[$uid];
    }


    /**
     * Returns the known information about a specific hosted account
     * 
     * Similar to get_list() but for creators/resellers.
     * 
     * @global	   type $msg
     * @global	   type $db
     * @param     int     $uid
     * @return    boolean
     */
    function get_creator($uid) {
        global $msg, $db;
        $msg->debug("admin","get_creator",$uid);
        if (!$this->enabled) {
            $msg->raise("ERROR", "admin", _("-- Only administrators can access this page! --"));
            return false;
        }

        $db->query("SELECT m.*, parent.login as parentlogin FROM membres as m LEFT JOIN membres as parent ON (parent.uid = m.creator) WHERE m.uid= ?;", array($uid));

        if ($db->num_rows()) {
            $db->next_record();
            $c = $db->Record;
        } else {
            $msg->raise("ERROR", "admin", _("Account not found"));
            return false;
        }

        $db->query("SELECT * FROM local WHERE uid= ?;", array($uid));
        if ($db->num_rows()) {
            $db->next_record();
            foreach($db->Record as $key => $val) {
                $c[$key] = $val;
            }
        }

        $db->query("SELECT count(*) as nbcreated FROM membres WHERE creator= ?;", array($uid));
        if ($db->num_rows()) {
            $db->next_record();
            foreach($db->Record as $key => $val) {
                $c[$key] = $val;
            }
        }

        return $c;
    }


    /**
     * 
     * @global	type $db
     * @return boolean TRUE if there is only one admin account
     * (allow the program to prevent the destruction of the last admin account)
     */
    function onesu() {
        global $db;
        $db->query("SELECT COUNT(*) AS cnt FROM membres WHERE su=1");
        $db->next_record();
        return ($db->f("cnt") == 1);
    }


    /**
     * @TODO :EM: those request should have been escaped
     * Returns the list of the hosted accounts
     * 
     * Returns all what we know about ALL the accounts (contents of the tables
     *  <code>membres</code> et <code>local</code>)
     * Check for super-admin accounts
     * @param
     * @return 
     * 
     * @global	   type $msg
     * @global	   type $mem
     * @global	   type $cuid
     * @param     integer $all
     * @param     integer $creator
     * @param     string $pattern
     * @param     string $pattern_type
     * @return    boolean | array an associative array containing all the fields of the
     * table <code>membres</code> and <code>local</code> of all the accounts.
     * Returns FALSE if an error occurs.
     */
    function get_list($all = 0, $creator = 0, $pattern = FALSE, $pattern_type = FALSE) {
        global $msg, $mem, $cuid;
        $msg->debug("admin", "get_list");
        if (!$this->enabled) {
            $msg->raise("ERROR", "admin", _("-- Only administrators can access this page! --"));
            return false;
        }
        $db = new DB_System();


        if ($pattern) {

            if ($pattern_type === 'domaine') {

                $request = 'SELECT compte AS uid FROM domaines WHERE 1';

                if ($pattern && preg_match('/[.a-zA-Z0-9]+/', $pattern)) {
                    $request .= sprintf(' AND domaine LIKE "%%%s%%"', $pattern);
                }
                if ($creator) {
                    $request .= sprintf(' AND compte in (select uid from membres where creator = "%s" ) ', $creator);
                }
                if ($mem->user['uid'] != 2000 && !$all) {
                    $request .= sprintf(' AND compte in (select uid from membres where creator = "%s") ', $cuid);
                }

                $request .= ' GROUP BY uid';
            } elseif ($pattern_type === 'login') {

                $request = 'SELECT uid FROM membres WHERE 1';

                if ($pattern && preg_match('/[a-zA-Z0-9]+/', $pattern)) {
                    $request .= sprintf(' AND login LIKE "%%%s%%"', $pattern);
                }
                if ($creator) {
                    $request .= sprintf(' AND creator = "%s"', $creator);
                }
                if ($mem->user['uid'] != 2000 && !$all) {
                    $request .= sprintf(' AND creator = "%s"', $cuid);
                }
                $request .= ' ORDER BY login;';
            } else {
                $msg->raise("ERROR", "admin", _("Invalid pattern type provided. Are you even performing a legitimate action?"));
                return FALSE;
            }
        } else {
            if ($creator) {
                // Limit listing to a specific reseller
                $request = "SELECT uid FROM membres WHERE creator='" . $creator . "' ORDER BY login;";
            } elseif ($mem->user['uid'] == 2000 || $all) {
                $request = "SELECT uid FROM membres ORDER BY login;";
            } else {
                $request = "SELECT uid FROM membres WHERE creator='" . $cuid . "' ORDER BY login;";
            }
        }

        $db->query($request);

        if ($db->num_rows()) {
            $c = array();
            while ($db->next_record()) {
                $c[$db->f("uid")] = $this->get($db->f("uid"));
            }
            return $c;
        } else {
            return false;
        }
    }


    /**
     * Send an email to all AlternC's accounts
     * 
     * @global	   type $msg
     * @global	   type $mem
     * @global	   type $cuid
     * @global	   type $db
     * @param     string  $subject    Subject of the email to send
     * @param     string  $message    Message to send
     * @param     string  $from       Expeditor of that email
     * @return    boolean
     */
    function mailallmembers($subject, $message, $from) {
        global $msg, $db;
        $msg->log("admin", "mailallmembers");
        if (!$this->enabled) {
            $msg->raise("ERROR", "admin", _("-- Only administrators can access this page! --"));
            return false;
        }
        $subject = trim($subject);
        $message = trim($message);
        $from = trim($from);

        if (empty($subject) || empty($message) || empty($from)) {
            $msg->raise("ERROR", "admin", _("Subject, message and sender are mandatory"));
            return false;
        }
        //@todo remove cf functions.php
        if (checkmail($from) != 0) {
            $msg->raise("ERROR", "admin", _("Sender is syntaxically incorrect"));
            return false;
        }

        @set_time_limit(1200);
        $db->query("SELECT DISTINCT mail FROM membres WHERE mail!='';");
        while ($db->next_record()) {
            // Can't do BCC due to postfix limitation
            // FIXME: use phpmailer, far better for mass-mailing than sendmail (reply-to issue among others)
            mail($db->f('mail'), $subject, $message, null, "-f$from");
        }
        return true;
    }


    /**
     * Returns an array with the known information about resellers (uid, login, number of accounts)
     * Does not include account 2000 in the list.
     * May only be called by the admin account (2000)
     * If there are no reseller accounts, returns an empty array.
     * 
     * @global    type $msg
     * @global    type $mem
     * @global    type $cuid
     * @return    boolean
     */
    function get_creator_list() {
        global $msg, $cuid;

        $creators = array();

        $msg->debug("admin", "get_creator_list");
        if (!$this->enabled) {
            $msg->raise("ERROR", "admin", _("-- Only administrators can access this page! --"));
            return false;
        }

        $db = new DB_System();
        $db->query("SELECT DISTINCT creator FROM membres WHERE creator <> 0 ORDER BY creator ASC;");
        if ($db->num_rows()) {
            while ($db->next_record()) {
                $creators[] = $this->get_creator($db->f("creator"));
            }
        }
        $creators2 = array();
        foreach ($creators as $cc) {
            $creators2[$cc['uid']] = $cc;
        }
        return $creators2;
    }


    /**
     * Check if I am the creator of the member $uid
     * 
     * @global    type $msg
     * @global    type $mem
     * @global    type $db
     * @global    type $cuid
     * @param     int     $uid   a unique integer identifying the account
     * @return    boolean         TRUE if I am the creator of that account. FALSE else.
     */
    function checkcreator($uid) {
        global $msg, $db, $cuid;
        if ($cuid == 2000) {
            return true;
        }
        $db->query("SELECT creator FROM membres WHERE uid= ?;", array($uid));
        $db->next_record();
        if ($db->Record["creator"] != $cuid) {
            $msg->raise("ERROR", "admin", _("-- Only administrators can access this page! --"));
            return false;
        }
        return true;
    }


    /**
     * When the admin want to delegate a subdomain to an account
     * 
     * @global    m_mysql $db
     * @global    m_messages   $msg
     * @global    m_dom   $dom
     * @global    m_mem   $mem
     * @global    int     $cuid
     * @param     string $u
     * @param     string $domain_name
     * @return boolean
     */
    function add_shared_domain($u, $domain_name) {
        global $msg, $dom, $mem;
        $msg->log("admin", "add_shared_domain", $u . "/" . $domain_name);

        if (!$mem->checkright()) {
            $msg->raise("ERROR", "admin", _("-- Only administrators can do that! --"));
            return false;
        }

        // Check if this domain exist on this admin account
        if ((!in_array($domain_name, $dom->enum_domains()))&&($domain_name!=variable_get("hosting_tld"))) {
            $msg->raise("ERROR", "admin", _("You don't seem to be allowed to delegate this domain"));
            $msg->log("admin", "add_shared_domain", "domain not allowed");
            return false;
        }

        // Clean the domain_name 
        $domain_name = preg_replace("/^\.\.*/", "", $domain_name);

        $mem->su($u);
        $dom->lock();
        // option : 1=hébergement dns + courriels, 1=noerase, empeche de modifier, 1=force
        // we do not allow DNS modification for hosting_tld
        if (variable_get("free_domain_enable_dns")) {
            $dns = 1;
        }
        else {
            $dns=($domain_name==variable_get("hosting_tld")) ? 0 : 1;
        }
        $dom->add_domain($mem->user['login'] . "." . $domain_name, $dns, 1, 1);
        $dom->unlock();
        $mem->unsu();
        return true;
    }


    /** Creates a new hosted account
     *  
     * Creates a new hosted account (in the tables <code>membres</code>
     * and <code>local</code>). Prevents any manipulation of the account if
     * the account $mid is not super-admin.
     *
     * 
     * @global    m_messages   $msg
     * @global    m_quota $quota
     * @global    array   $classes
     * @global    int     $cuid
     * @global    m_mem   $mem
     * @global    string  $L_MYSQL_DATABASE
     * @global    string  $L_MYSQL_LOGIN
     * @global    m_hooks $hooks
     * @global    m_action $action
     * @param     string  $login          Login name like [a-z][a-z0-9]*
     * @param     string  $pass           Password (max. 64 characters)
     * @param     string  $nom            Name of the account owner
     * @param     string  $prenom         First name of the account owner
     * @param     string  $mail           Email address of the account owner, useful to get
     *                                    one's lost password
     * @param     integer $canpass
     * @param     string  $type           Account type for quotas
     * @param     int     $duration
     * @param     string  $notes
     * @param     integer $force
     * @param     string  $create_dom
     * @param     int     $db_server_id
     * @return boolean Returns FALSE if an error occurs, TRUE if not.
     */
    function add_mem($login, $pass, $nom, $prenom, $mail, $canpass = 1, $type = 'default', $duration = 0, $notes = "", $force = 0, $create_dom = '', $db_server_id) {
        global $msg, $cuid, $mem, $L_MYSQL_DATABASE, $L_MYSQL_LOGIN, $hooks, $action;
        $msg->log("admin", "add_mem", $login . "/" . $mail);
        if (!$this->enabled) {
            $msg->raise("ERROR", "admin", _("-- Only administrators can access this page! --"));
            return false;
        }
        if (empty($db_server_id)) {
            $msg->raise("ERROR", "admin", _("Missing db_server field"));
            return false;
        }
        if (($login == "") || ($pass == "")) {
            $msg->raise("ERROR", "admin", _("Please fill all mandatory fields"));
            return false;
        }
        if (!$force) {
            if ($mail == "") {
                $msg->raise("ERROR", "admin", _("Please fill all mandatory fields"));
                return false;
            }
            //@todo remove cf functions.php
            if (checkmail($mail) != 0) {
                $msg->raise("ERROR", "admin", _("Please enter a valid email address"));
                return false;
            }
        }
        $login = strtolower($login);
        if (!preg_match("#^[a-z0-9]+$#", $login)) { //$
            $msg->raise("ERROR", "admin", _("Login can only contains characters a-z and 0-9"));
            return false;
        }
        if (strlen($login) > 14) {
            // Not an arbitrary value : MySQL user names can be up to 16 characters long
            // If we want to allow people to create a few mysql_user (and we want to!)
            // we have to limit the login lenght
            $msg->raise("ERROR", "admin", _("The login is too long (14 chars max)"));
            return false;
        }
        // Some login are not allowed...
        if ($login == $L_MYSQL_DATABASE || $login == $L_MYSQL_LOGIN || $login == "mysql" || $login == "root") {
            $msg->raise("ERROR", "admin", _("Login can only contains characters a-z, 0-9 and -"));
            return false;
        }
        // Additional checks before a user is created
        // Do not create the account if any hook has a return value
        // The returned value should provide additional information
        $before_add_hook_data = $hooks->invoke('hook_before_alternc_add_member', [$login]);
        foreach($before_add_hook_data as $create) {
            if($create !== null) {
                $msg->raise("ERROR", "admin", _("The account '%s' cannot be created. %s"), [$login, $create]);
                return false;
            }
        }

        $pass = password_hash($pass, PASSWORD_BCRYPT);
        $db = new DB_System();
        // Already exist?
        $db->query("SELECT count(*) AS cnt FROM membres WHERE login= ?;", array($login));
        $db->next_record();
        if (!$db->f("cnt")) {
            $db->query("SELECT max(m.uid)+1 as nextid FROM membres m");
            if (!$db->next_record()) {
                $uid = 2000;
            } else {
                $uid = $db->Record["nextid"];
                if ($uid <= 2000) {
                    $uid = 2000;
                }
            }
            $db->query("INSERT INTO membres (uid,login,pass,mail,creator,canpass,type,created,notes,db_server_id) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?);", array($uid, $login, $pass, $mail, $cuid, $canpass, $type, $notes, $db_server_id));
            $db->query("INSERT INTO local(uid,nom,prenom) VALUES(?, ?, ?);", array($uid, $nom, $prenom));
            $this->renew_update($uid, $duration);
            $action->create_dir(getuserpath("$login"));
            $action->fix_user($uid);

            // Triggering hooks
            $mem->su($uid);

            $hooks->invoke("alternc_add_member");
            // New hook way
            $hooks->invoke("hook_admin_add_member", array(), array('quota')); // First !!! The quota !!! Eitherway, we can't be sure to be able to create all
            $hooks->invoke("hook_admin_add_member");
            $mem->unsu();

            if (!empty($create_dom)) {
                $this->add_shared_domain($uid, $create_dom);
            }

            return $uid;
        } else {
            $msg->raise("ERROR", "admin", _("This login already exists"));
            return false;
        }
    }


    /**
     * AlternC's standard function called when a user is created
     * This sends an email if configured through the interface.
     * 
     * @global    m_messages   $msg
     * @global    int     $cuid
     * @global    string     $L_FQDN
     * @global    string     $L_HOSTING
     * @return    boolean
     */
    function hook_admin_add_member() {
        global $msg, $cuid, $L_FQDN, $L_HOSTING;
        $dest = variable_get('new_email', '0', 'An email will be sent to this address when new accounts are created if set.', array('desc' => 'Enabled', 'type' => 'boolean'));
        if (!$dest) {
            return false;
        }
        $db = new DB_System();
        if (!$db->query("SELECT m.*, parent.login as parentlogin FROM membres m LEFT JOIN membres parent ON parent.uid=m.creator WHERE m.uid= ?", array($cuid))) {
            $msg->raise("ERROR", "admin", sprintf(_("query failed: %s "), $db->Error));
            return false;
        }
        if ($db->next_record()) {
            // TODO: put that string into gettext !
            $mail = '
                A new AlternC account was created on %fqdn by %creator.

                Account details
                ---------------

                login: %login (%uid)
                email: %mail
                createor: %creator (%cuid)
                can change password: %canpass
                type: %type
                notes: %notes
                ';
            $mail = strtr($mail, array('%fqdn' => $L_FQDN,
            '%creator' => $db->Record['parentlogin'],
            '%uid' => $db->Record['uid'],
            '%login' => $db->Record['login'],
            '%mail' => $db->Record['mail'],
            '%cuid' => $db->Record['creator'],
            '%canpass' => $db->Record['canpass'],
            '%type' => $db->Record['type'],
            '%notes' => $db->Record['notes']));
            $subject = sprintf(_("New account %s from %s on %s"), $db->Record['login'], $db->Record['parentlogin'], $L_HOSTING);
            if (mail($dest, $subject, $mail, "From: postmaster@$L_FQDN")) {
                //sprintf(_("Email successfully sent to %s"), $dest);
                return true;
            } else {
                $msg->raise("ERROR", "admin", sprintf(_("Cannot send email to %s"), $dest));
                return false;
            }
        } else {
            $msg->raise("ERROR", "admin", sprintf(_("Query failed: %s"), $db->Error));
            return false;
        }
    }


    /**
     * Edit an account
     *  
     * Change an account (in the tables <code>membres</code>
     * and <code>local</code>). Prevents any manipulation of the account if
     * the account $mid is not super-admin.
     *  
     * @global    m_messages   $msg
     * @global    m_mysql $db
     * @global    int     $cuid
     * @global    m_quota $quota
     * @param     int     $uid        The uid number of the account we want to modify
     * @param     string  $mail       New email address of the account owner
     * @param     string  $nom        New name of the account owner
     * @param     string  $prenom     New first name of the account owner
     * @param     string  $pass       New password (max. 64 characters)
     * @param     string  $enabled    (value: 0 or 1) activates or desactivates the
     * @param     boolean $canpass
     * @param     int     $type       New type of account
     * @param     int     $duration   
     * @param     string  $notes
     * @param     boolean $reset_quotas
     * @return    boolean Returns     FALSE if an error occurs, TRUE if not
     */
    function update_mem($uid, $mail, $nom, $prenom, $pass, $enabled, $canpass, $type = 'default', $duration = 0, $notes = "", $reset_quotas = false) {
        global $msg, $db, $quota;

        $msg->log("admin", "update_mem", $uid);

        if (!$this->enabled) {
            $msg->raise("ERROR", "admin", _("-- Only administrators can access this page! --"));
            return false;
        }
        $db = new DB_System();

        if ($pass) {
            $pass = password_hash($pass, PASSWORD_BCRYPT);
            $second_query = "UPDATE membres SET mail= ?, canpass= ?, enabled= ?, `type`= ?, notes= ? , pass = ? WHERE uid= ?;";
            $second_query_args = array($mail, $canpass, $enabled, $type, $notes, $pass, $uid);
        } else {
            $second_query = "UPDATE membres SET mail= ?, canpass= ?, enabled= ?, `type`= ?, notes= ? WHERE uid= ?;";
            $second_query_args = array($mail, $canpass, $enabled, $type, $notes, $uid);
        }

        $old_mem = $this->get($uid);

        if(
            ($db->query("UPDATE local SET nom= ?, prenom= ? WHERE uid=?;", array($nom, $prenom, $uid))) && 
            ($db->query($second_query, $second_query_args))
        ){
            if ($reset_quotas == "on" || $type != $old_mem['type']) {
                $quota->addquotas();
                $quota->synchronise_user_profile();
            }
            $this->renew_update($uid, $duration);
            return true;
        } else {
            $msg->raise("ERROR", "admin", _("Account not found"));
            return false;
        }
    }


    /**
     * Lock an account
     * 
     * Lock an account and prevent the user to access its account.
     * 
     * @global    m_messages   $msg
     * @global    m_mysql $db
     * @param     int     $uid    The uid number of the account 
     * @return    boolean         Returns FALSE if an error occurs, TRUE if not.
     */
    function lock_mem($uid) {
        global $msg, $db;
        $msg->log("admin", "lock_mem", $uid);
        if (!$this->enabled) {
            $msg->raise("ERROR", "admin", _("-- Only administrators can access this page! --"));
            return false;
        }
        $db = new DB_System();
        if ($db->query("UPDATE membres SET enabled='0' WHERE uid= ?;", array($uid))) {
            return true;
        } else {
            $msg->raise("ERROR", "admin", _("Account not found"));
            return false;
        }
    }


    /**
     * UnLock an account
     * 
     * UnLock an account and prevent the user to access its account.
     * 
     * 
     * @global    m_messages   $msg
     * @global    m_mysql $db
     * @param     int     $uid    The uid number of the account 
     * @return    boolean         Returns FALSE if an error occurs, TRUE if not.
     */
    function unlock_mem($uid) {
        global $msg, $db;
        $msg->log("admin", "unlock_mem", $uid);
        if (!$this->enabled) {
            $msg->raise("ERROR", "admin", _("-- Only administrators can access this page! --"));
            return false;
        }
        $db = new DB_System();
        if ($db->query("UPDATE membres SET enabled='1' WHERE uid= ?;", array($uid))) {
            return true;
        } else {
            $msg->raise("ERROR", "admin", _("Account not found"));
            return false;
        }
    }


    /** Deletes an account
     * Deletes the specified account. Prevents any manipulation of the account if
     * the account $mid is not super-admin.
     * 
     * @global    m_messages   $msg
     * @global    m_quota $quota
     * @global    array   $classes
     * @global    int     $cuid
     * @global    m_mem   $mem
     * @global    m_dom   $dom
     * @global    m_hooks $hooks
     * @global    m_action $action
     * @param     int     $uid    The uid number of the account 
     * @return    boolean         Returns FALSE if an error occurs, TRUE if not.
     */
    function del_mem($uid) {
        global $msg, $mem, $dom, $hooks, $action;
        $msg->log("admin", "del_mem", $uid);

        if (!$this->enabled) {
            $msg->raise("ERROR", "admin", _("-- Only administrators can access this page! --"));
            return false;
        }
        $db = new DB_System();
        $tt = $this->get($uid);

        $mem->su($uid);
        // This script may take a long time on big accounts, let's give us some time ... Fixes 1132
        @set_time_limit(0);
        // WE MUST call m_dom before all others because of conflicts ...
        $dom->admin_del_member();

        # New way of deleting or backup delted user html folders using action class
        $path = getuserpath($tt['login']);
        $action->archive($path);

        $hooks->invoke("alternc_del_member");
        $hooks->invoke("hook_admin_del_member");

        if (($db->query("DELETE FROM membres WHERE uid= ?;", array($uid))) &&
        ($db->query("DELETE FROM local WHERE uid= ?;", array($uid)))) {
            $mem->unsu();
            // If this user was (one day) an administrator one, he may have a list of his own accounts. Let's associate those accounts to nobody as a creator.
            $db->query("UPDATE membres SET creator=2000 WHERE creator= ?;", array($uid));
            return true;
        } else {
            $msg->raise("ERROR", "admin", _("Account not found"));
            $mem->unsu();
            return false;
        }
    }


    /**
     * Renew an account
     * 
     * Renew an account for its duration
     * 
     * @global    m_messages   $msg
     * @global    m_mysql $db
     * @param     int     $uid        The uid number of the account 
     * @param     int     $periods    The new duration, in months, of the account
     * @return    boolean             Returns FALSE if an error occurs, TRUE if not.
     */
    function renew_mem($uid, $periods = 1) {
        global $msg, $db;

        $periods = intval($periods);
        if ($periods == 0) {
            return false;
        }
        if ($db->query("UPDATE membres SET renewed = renewed + INTERVAL (duration * ?) MONTH WHERE uid= ?;", array($periods, $uid))) {
            return true;
        } else {
            $msg->raise("ERROR", "admin", _("Account not found"));
            return false;
        }
    }


    /**
     * Update the duration information for an account
     * 
     * @global    m_messages   $msg
     * @global    m_mysql $db
     * @param     int     $uid        The uid number of the account 
     * @param     int     $duration   The new duration, in months, of the account
     * @return    boolean             Returns FALSE if an error occurs, TRUE if not.
     */
    function renew_update($uid, $duration) {
        global $msg, $db;

        if ($duration == 0) {
            if ($db->query("UPDATE membres SET duration = NULL, renewed = NULL WHERE uid= ?;", array($uid))) {
                return true;
            }
        } else {
            if ($db->query("UPDATE membres SET duration = ? WHERE uid= ?", array($duration, $uid)) &&
            $db->query("UPDATE membres SET renewed = NOW() WHERE uid= ? and renewed is null;", array($uid))) {
                return true;
            }
        }

        $msg->raise("ERROR", "admin", _("Account not found"));
        return false;
    }


    /**
     * Get the expiry date for an account
     * 
     * @param     int     $uid        The uid number of the account 
     * @return    string              The expiry date, a string as printed by MySQL
     */
    function renew_get_expiry($uid) {
        $jj = $this->get($uid);
        if (isset($jj) && isset($jj['expiry']) && !empty($jj['expiry'])) {
            return $jj['expiry'];
        }
        return '';
    }


    /**
     * Get the expiry status for an account
     * 
     * @param     int     $uid        The uid number of the account 
     * @return    integer             The expiry status:
     *  0: account does not expire
     *  1: expires in more than duration,
     *  2: expires within the duration
     *  3: has expired past the duration
     */
    function renew_get_status($uid) {
        $jj = $this->get($uid);

        if (isset($jj) && isset($jj['status']) && !empty($jj['status'])) {
            return $jj['status'];
        }

        return 0;
    }


    /**
     * Get the expired/about to expire accounts.
     * 
     * @global    m_mysql $db
     * @return    array               The recordset of the corresponding accounts
     */
    function renew_get_expiring_accounts() {
        global $db;

        if (!$db->query("SELECT *, m.renewed + INTERVAL duration MONTH 'expiry'," .
        " CASE WHEN m.duration IS NULL THEN 0" .
        " WHEN m.renewed + INTERVAL m.duration MONTH <= NOW() THEN 3" .
        " WHEN m.renewed <= NOW() THEN 2" .
        " ELSE 1 END 'status' FROM membres m, local l" .
        " WHERE m.uid = l.uid" .
        " HAVING status=2 or status=3 ORDER BY status DESC, expiry;")) {
            return false;
        } else {
            $res = array();
            while ($db->next_record()) {
                $res[] = $db->Record;
            }
            return $res;
        }
    }


    /**
     * Turns a common account into a super-admin account
     * 
     * @global    m_messages   $msg
     * @global    m_mysql $db
     * @param     int     $uid        The uid number of the account 
     * @return    boolean    
     */
    function normal2su($uid) {
        global $msg, $db;
        $db->query("SELECT su FROM membres WHERE uid= ?;", array($uid));
        if (!$db->next_record()) {
            $msg->raise("ERROR", "admin", _("Account not found"));
            return false;
        }
        if ($db->Record["su"] != 0) {
            $msg->raise("ERROR", "admin", _("This account is ALREADY an administrator account"));
            return false;
        }
        $db->query("UPDATE membres SET su=1 WHERE uid= ?;", array($uid));
        return true;
    }


    /**
     * Turns a super-admin account into a common account
     * 
     * @global    m_messages   $msg
     * @global    m_mysql $db
     * @param     int     $uid        The uid number of the account 
     * @return boolean                Returns FALSE if an error occurs, TRUE if not.
     */
    function su2normal($uid) {
        global $msg, $db;
        $db->query("SELECT su FROM membres WHERE uid= ?;", array($uid));
        if (!$db->next_record()) {
            $msg->raise("ERROR", "admin", _("Account not found"));
            return false;
        }
        if ($db->Record["su"] != 1) {
            $msg->raise("ERROR", "admin", _("This account is NOT an administrator account!"));
            return false;
        }
        $db->query("UPDATE membres SET su=0 WHERE uid= ?;", array($uid));
        return true;
    }


    /**
     * List of the authorized TLDs
     * Returns the list of the authorized TLDs and also the way they are
     * authorized. A TLD is the last members (or the last two) of a
     * domain. For example, "com", "org" etc... AlternC keeps a table
     * containing the list of the TLDs authorized to be installed on the
     * server with the instructions to validate the installation of a
     * domain for each TLD (if necessary).
     * 
     * @global    m_mysql $db
     * @return    array   An associative array like $r["tld"], $r["mode"] where tld
     * is the tld and mode is the authorized mode.
     */
    function listtld() {
        global $db;
        $db->query("SELECT tld,mode FROM tld ORDER BY tld;");
        $c = array();
        while ($db->next_record()) {
            $c[] = $db->Record;
        }
        return $c;
    }


    /**
     * List the hosted domains on this server
     * 
     * Return the list of hosted domains on this server, (an array of associative arrays)
     * 
     * @global    m_mysql $db
     * @param     boolean     $alsocheck      Returns also errstr and errno telling the domains dig checks
     * @param     boolean     $forcecheck     Force the check of dig domain even if a cache exists.
     * @return array $r[$i] / [domaine][member][noerase][gesdns][gesmx]
     */
    function dom_list($alsocheck = false, $forcecheck = false) {
        global $db;
        $cachefile = "/tmp/alternc_dig_check_cache";
        $cachetime = 3600; // The dns cache file can be up to 1H old
        if ($alsocheck) {
            if (!$forcecheck && file_exists($cachefile) && filemtime($cachefile) + $cachetime > time()) {
                $checked = unserialize(file_get_contents($cachefile));
            } else {
                // TODO : do the check here (cf checkdom.php) and store it in $checked
                $checked = $this->checkalldom();
                file_put_contents($cachefile, serialize($checked));
            }
        }

        $query = "SELECT m.uid,m.login,d.domaine,d.gesdns,d.gesmx,d.noerase FROM domaines d LEFT JOIN membres m ON m.uid=d.compte ";
        $query_args = array();
        if($hosting_tld = variable_get("hosting_tld")){
            $query .= " WHERE domaine not like ?";
            array_push($query_args, "%.".$hosting_tld);
        }
        $query .= " ORDER BY domaine;";
        $db->query($query, $query_args);
        $c = array();
        while ($db->next_record()) {
            $tmp = $db->Record;
            if ($alsocheck) {
                $tmp["errstr"] = $checked[$tmp["domaine"]]["errstr"];
                $tmp["errno"] = $checked[$tmp["domaine"]]["errno"];
            }
            $c[] = $tmp;
        }
        return $c;
    }


    /**
     * Check all the domains for their NS MX and IPs
     * 
     * @global    m_mysql $db
     * @global    string  $L_NS1
     * @global    string  $L_NS2
     * @global    string  $L_MX
     * @global    string  $L_PUBLIC_IP
     * @return    int
     */
    function checkalldom() {
        global $db, $L_NS1, $L_NS2, $L_MX, $L_PUBLIC_IP;
        $checked = array();

        $query = "SELECT * FROM domaines ";
        $query_args = array();
        if($hosting_tld = variable_get("hosting_tld")){
            $query .= " WHERE domaine not like ?";
            array_push($query_args, "%.".$hosting_tld);
        }
        $query .= " ORDER BY domaine";
        $db->query($query, $query_args);
        $dl = array();
        while ($db->next_record()) {
            $dl[$db->Record["domaine"]] = $db->Record;
        }

        // won't search for MX and subdomains record if DNS is hosted here
        $lazycheck=1;

        sort($dl);
        foreach ($dl as $c) {
            // For each domain check its type:
            $errno = 0;
            $errstr = "";
            $dontexist = false;
            // Check the domain.
            if ($c["gesdns"] == 1) {
                // Check the NS pointing to us
                $out = array();
                exec("dig +short NS " . escapeshellarg($c["domaine"]), $out);
                if (count($out) == 0) {
                    $dontexist = true;
                } else {
                    if (!in_array($L_NS1 . ".", $out) || !in_array($L_NS2 . ".", $out)) {
                        $errno = 1;
                        $errstr.=sprintf(_("NS for this domain are not %s and %s BUT %s"),
                        $L_NS1, $L_NS2, implode(",", $out)) . "\n";
                    }
                }
            }

            if (!$dontexist&&(!$lazycheck||!$c["gesdns"])) {
                if ($c["gesmx"] == 1) {
                    $out = array();
                    exec("dig +short MX " . escapeshellarg($c["domaine"]), $out);
                    $out2 = array();
                    foreach ($out as $o) {
                        list($t, $out2[]) = explode(" ", $o);
                    }
                    if (!in_array($L_MX . ".", $out2)) {
                        $errno = 1;
                        $errstr.=sprintf(_("MX is not %s BUT %s"), $L_MX, implode(",", $out2))."\n";
                    }
                }

                // We list all subdomains and check they are pointing to us.
                $db->query("SELECT * FROM sub_domaines WHERE domaine=? ORDER BY sub;", array($c["domaine"]));
                while ($db->next_record()) {
                    $d = $db->Record;
                    if ($d["type"] == 'VHOST') {
                        // Check the IP: 
                        $out = array();
                        exec("dig +short A " . escapeshellarg($d["sub"] . (($d["sub"] != "") ? "." : "") . $c["domaine"]), $out);
                        if (!is_array($out)) { // exec dig can fail
                            $errno = 1;
                            $errstr.=_("Fail to get the DNS information. Try again.")."\n";
                        } else {
                            if (!in_array($L_PUBLIC_IP, $out)) {
                                $errstr.=sprintf(_("subdomain '%s' doesn't point to %s but to '%s'"), $d["sub"], $L_PUBLIC_IP, implode(",", $out))."\n" ;
                                $errno = 1;
                            }
                        }
                    }
                }
            }
            if ($dontexist) {
                $errno = 2;
                $errstr = _("Domain doesn't exist anymore !");
            }
            if ($errno == 0)
                $errstr = "OK";
            $checked[$c["domaine"]] = array("errno" => $errno, "errstr" => $errstr);
        }
        return $checked;
    }


    /**
     * Lock / Unlock a domain 
     * 
     * Lock (or unlock) a domain, so that the member will be (not be) able to delete it
     * from its account
     * 
     * @global    m_mysql $db
     * @global    m_messages   $msg
     * @param     string  $domain     Domain name to lock / unlock
     * @return    boolean             TRUE if the domain has been locked/unlocked or FALSE if it does not exist.
     */
    function dom_lock($domain) {
        global $db, $msg;
        $db->query("SELECT compte FROM domaines WHERE domaine= ?;", array($domain));
        if (!$db->next_record()) {
            $msg->raise("ERROR", "dom", _("Domain '%s' not found."), $domain);
            return false;
        }
        $db->query("UPDATE domaines SET noerase=1-noerase WHERE domaine= ?;", array($domain));
        return true;
    }


    /**
     * Add a new TLD to the list of the authorized TLDs 
     * 
     * @global    m_mysql $db
     * @global    m_messages   $msg
     * @param     string      $tld    top-level domain to add (org, com...)
     * @return    boolean             TRUE if the tld has been successfully added, FALSE if not.
     */
    function gettld($tld) {
        global $db, $msg;
        $db->query("SELECT mode FROM tld WHERE tld= ?;", array($tld));
        if (!$db->next_record()) {
            $msg->raise("ERROR", "admin", _("This TLD does not exist"));
            return false;
        }
        return $db->Record["mode"];
    }


    /**
     * Prints the list of the actually authorized TLDs
     * 
     * @param     boolean $current   Value to select in the list
     */
    function selecttldmode($current = false) {
        for ($i = 0; $i < count($this->tldmode); $i++) {
            echo "<option value=\"$i\"";
            if ($current == $i) {
                echo " selected=\"selected\"";
            }
            echo ">" . _($this->tldmode[$i]) . "</option>\n";
        }
    }


    /**
     * Deletes the specified tld in the list of the authorized TLDs
     * <b>Note</b> : This function does not delete the domains depending
     * on this TLD
     * 
     * @global    m_mysql $db
     * @global    m_messages   $msg
     * @param     string  $tld   The TLD you want to delete
     * @return    boolean         returns true if the TLD has been deleted, or
     * false if an error occured.
     */
    function deltld($tld) {
        global $db, $msg;
        $db->query("SELECT tld FROM tld WHERE tld= ?;", array($tld));
        if (!$db->next_record()) {
            $msg->raise("ERROR", "admin", _("This TLD does not exist"));
            return false;
        }
        $db->query("DELETE FROM tld WHERE tld= ?;", array($tld));
        return true;
    }


    /** Add a TLD to the list of the authorized TLDs during the installation
     * 
     * <b>Note: </b> If you check in the whois, be sure that
     *  <code>m_domains</code> knows how to name the whois of the specified
     *  domain!
     * 
     * @global    m_mysql $db
     * @global    m_messages   $msg
     * @param     string  $tld        string TLD we want to authorize
     * @param     boolean $mode       Controls to make on this TLD.
     * @return    boolean             TRUE if the TLD has been successfully
     *  added. FALSE if not.
     */
    function addtld($tld, $mode) {
        global $db, $msg;
        if (!$tld) {
            $msg->raise("ERROR", "admin", _("The TLD name is mandatory"));
            return false;
        }
        $tld = trim($tld);

        $db->query("SELECT tld FROM tld WHERE tld= ?;", array($tld));
        if ($db->next_record()) {
            $msg->raise("ERROR", "admin", _("This TLD already exist"));
            return false;
        }
        if (substr($tld, 0, 1) == ".") {
            $tld = substr($tld, 1);
        }
        $mode = intval($mode);
        if ($mode == 0) {
            $mode = "0";
        }
        $db->query("INSERT INTO tld (tld,mode) VALUES (?,?);", array($tld, $mode));
        return true;
    }


    /**
     * Modify a TLD of the list of the authorized TLDs 
     * 
     * @global    m_mysql $db
     * @global    m_messages   $msg
     * @param     string  $tld    TLD we want to modify
     * @param     int     $mode   Controls to make on this TLD.
     * @return    boolean         TRUE if the TLD has been successfully
     * modified. FALSE if not.

     */
    function edittld($tld, $mode) {
        global $db, $msg;
        $db->query("SELECT tld FROM tld WHERE tld= ?;", array($tld));
        if (!$db->next_record()) {
            $msg->raise("ERROR", "admin", _("This TLD does not exist"));
            return false;
        }
        $mode = intval($mode);
        if ($mode == 0) {
            $mode = "0";
        }
        $db->query("UPDATE tld SET mode= ? WHERE tld= ?;", array($mode, $tld));
        return true;
    }


    /**
     * Get the login name of the main administrator account
     * 
     * @global    m_mysql $db
     * @return string     the login name of admin, like 'root' for older alterncs
     */
    function getadmin() {
        global $db;
        $db->query("SELECT login FROM membres WHERE uid=2000;");
        $db->next_record();
        return $db->f("login");
    }


    /**
     * List the password policies currently installed in the policy table
     * 
     * @global    m_mysql $db
     * @global    array   $classes
     * @global    m_hooks $hooks
     * @return array              an indexed array of associative array from the MySQL "policy" table
     */
    function listPasswordPolicies() {
        global $db, $hooks;
        $tmp1 = array();
        $tmp2 = array();
        $policies = array();
        $db->query("SELECT * FROM policy;");
        while ($db->next_record()) {
            $tmp1[$db->Record["name"]] = $db->Record;
        }
        $tmp3 = $hooks->invoke("alternc_password_policy");
        foreach ($tmp3 as $v) {
            foreach ($v as $l => $m) {
                $tmp2[$l] = $m;
            }
        }
        foreach ($tmp2 as $k => $v) {
            if (!isset($tmp1[$k])) {
                // Default policy : 
                $db->query("INSERT INTO policy SET name= ?, minsize=0, maxsize=64, classcount=0, allowlogin=0;", array($k));
                $tmp1[$k] = array(
                    "minsize" => 0, "maxsize" => 64, "classcount" => 0, "allowlogin" => 0
                );
            }
            $policies[$k] = $tmp1[$k];
            $policies[$k]["description"] = _($v);
            unset($tmp1[$k]);
        }
        foreach ($tmp1 as $k => $v) {
            // Delete disabled modules :
            $db->query("DELETE FROM policy WHERE name= ?;", array($k));
        }
        return $policies;
    }


    /**
     * Change a password policy for one kind of password
     * 
     * @global    m_mysql $db
     * @param     string  $policy     Name of the policy to edit
     * @param     int     $minsize    Minimum Password size
     * @param     int     $maxsize    Maximum Password size
     * @param     int     $classcount How many class of characters must this password have
     * @param     boolean $allowlogin Do we allow the password to be like the login ? 
     * @return    boolean if the policy has been edited, or FALSE if an error occured.
     */
    function editPolicy($policy, $minsize, $maxsize, $classcount, $allowlogin) {
        global $db;
        $minsize = intval($minsize);
        $maxsize = intval($maxsize);
        $classcount = intval($classcount);
        $allowlogin = intval($allowlogin);

        $db->query("SELECT * FROM policy WHERE name= ?;", array($policy));
        if (!$db->next_record()) {
            return false; // Policy not found
        }
        if ($minsize < 0 || $minsize > 64 || $maxsize < 0 || $maxsize > 64 || $maxsize < $minsize || $classcount < 0 || $classcount > 4) {
            return false; // Incorrect policy ...
        }
        $allowlogin = ($allowlogin) ? 1 : 0;
        $db->query("UPDATE policy SET minsize= ?, maxsize= ?, classcount= ?, allowlogin= ? WHERE name= ?;", array($minsize, $maxsize, $classcount, $allowlogin, $policy));
        return true;
    }

    
    /**
     * 
     * @global    m_mysql $db
     * @global    m_messages   $msg
     * @param     string  $policy     Name of the policy to check for
     * @param     string  $login      The login that will be set
     * @param     string  $password   The password we have to check
     * @return    boolean             TRUE if the password if OK for this login and this policy, FALSE if it is not.
     */
    function checkPolicy($policy, $login, $password, $canbeempty = false) {
        global $msg;

        if (empty($login)) {
            $msg->raise("ALERT", "admin", _("Please enter a login"));
            return false;
        }

        if (empty($password)) {
            if ($canbeempty) {
                return true; // when empty password are allowed, no policy check then.
            } else {
                $msg->raise("ALERT", "admin", _("Please enter a password"));
                return false;
            }
        }

        $pol = $this->listPasswordPolicies();
        if (!$pol[$policy]) {
            $msg->raise("ERROR", "admin", _("-- Program error -- The requested password policy does not exist!"));
            return false;
        }
        $pol = $pol[$policy];
        // Ok, now let's check it : 
        $plen = strlen($password);

        if ($plen < $pol["minsize"] && !($canbeempty && empty($password))) {
            $msg->raise("ERROR", "admin", _("The password length is too short according to the password policy"));
            return false;
        }

        if ($plen > $pol["maxsize"] && !($canbeempty && empty($password))) {
            $msg->raise("ERROR", "admin", _("The password is too long according to the password policy"));
            return false;
        }

        if (!$pol["allowlogin"]) {
            // We do misc check on password versus login : 
            $logins = preg_split("/[@_-]/", $login);
            $logins[] = $login;
            foreach ($logins as $l) {
                if (!$l) {
                    continue;
                }
                if (strpos($password, $l) !== false || strpos($l, $password) !== false) {
                    $msg->raise("ERROR", "admin", _("The password policy prevents you to use your login name inside your password or the other way around"));
                    return false;
                }
            }
        }

        if ($pol["classcount"] > 0 && !($canbeempty && empty($password))) {
            $cls = array(0, 0, 0, 0, 0);
            for ($i = 0; $i < strlen($password); $i++) {
                $p = substr($password, $i, 1);
                if (strpos("abcdefghijklmnopqrstuvwxyz", $p) !== false) {
                    $cls[0] = 1;
                } elseif (strpos("ABCDEFGHIJKLMNOPQRSTUVWXYZ", $p) !== false) {
                    $cls[1] = 1;
                } elseif (strpos("0123456789", $p) !== false) {
                    $cls[2] = 1;
                } elseif (strpos('!"#$%&\'()*+,-./:;<=>?@[\\]^_`', $p) !== false) {
                    $cls[3] = 1;
                } else {
                    $cls[4] = 1;
                }
            } // foreach
            $clc = array_sum($cls);
            if ($clc < $pol["classcount"]) {
                $msg->raise("ERROR", "admin", _("Your password contains not enough different classes of character, between low-case, up-case, figures and special characters."));
                return false;
            }
        }
        return true; // congratulations ! 
    }


} /* Class m_admin */


