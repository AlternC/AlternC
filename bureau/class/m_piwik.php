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
 * This class manage piwik statistics management through AlternC, using piwik's "API".
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */
class m_piwik {
    var $piwik_server_uri;
    var $piwik_admin_token;
    var $alternc_users;
    var $alternc_sites;

    function hook_menu() {
        global $quota;
        if ( empty($this->piwik_server_uri) || empty($this->piwik_admin_token)) return false;

        $obj = array(
            'title'       => _("Piwik statistics"),
            'link'        => 'toggle',
            'pos'         => 115,
            'links'       => array(
                array( 'txt' => _("Piwik Users"), 'url' => 'piwik_userlist.php'),
                array( 'txt' => _("Piwik Sites"), 'url' => 'piwik_sitelist.php'),
            ),
        ) ;

        return $obj;
    }


    /** 
     * Constructor
     */
    function __construct() {
        $this->piwik_server_uri=variable_get('piwik_server_uri','','Remote Piwik server uri');
        $this->piwik_admin_token=variable_get('piwik_admin_token','','Remote Piwik super-admin token');
        $this->alternc_users=array();
        $this->alternc_sites=array();
    }


    /** 
     * hook called when an AlternC account is deleted
     */
    function hook_admin_del_member() {
        //FIXME : implement the hook_admin_del_member for piwik
        return true;
    }


    /** 
     * Returns the used quota for the $name service for the current user.
     * @param $name string name of the quota 
     * @return integer the number of service used or false if an error occured
     * @access private
     */
    function hook_quota_get() {
        global $db, $cuid;
        $db->query("SELECT COUNT(id) AS nb FROM piwik_sites WHERE uid= ? ;", array($cuid));
        $q=Array("name"=>"piwik", "description"=>_("Statistics through Piwik accounts"), "used"=>0);
        if ($db->next_record()) {
            $q['used']=$db->f('nb');
        }
        return $q;
    }


    function url() {
        return $this->piwik_server_uri;
    }


    /***********************/
    /* User-related tasks */
    /***********************/

    function user_add($user_login, $user_mail) {
        global $db, $mem, $cuid, $msg;

        $msg->log("piwik","user_add");

        if (empty($user_login) || is_null($user_login) || empty($user_mail) || is_null($user_mail)) {
            $msg->raise("ERROR", "piwik", _("All fields are mandatory"));
            return false;
        }

        // Validate the email syntax:
        if (!filter_var($user_mail, FILTER_VALIDATE_EMAIL)) {
            $msg->raise("ERROR", "piwik", _("The email you entered is syntaxically incorrect"));
            return false;
        }

        $user_login = $this->clean_user_name($user_login);
        $user_pass  = create_pass();
        $user_alias = $user_login;

        $api_data = $this->call_privileged_page('API', 'UsersManager.addUser', array('userLogin' => $user_login, 'password' => $user_pass, 'email' => $user_mail, 'alias' => $user_alias), 'JSON'); 
        if ($api_data) {
            if ($api_data->result === 'success') {
                $user = $this->get_user($user_login);
                $user_creation_date = $user->date_registered;
                $ret_value = $db->query("INSERT INTO piwik_users (uid, passwd, login, created_date) VALUES ( ?, ?, ?, ?);", array($cuid, md5($user_pass), $user_login, $user_creation_date));
                return $ret_value;
            } else {
                $msg->raise("ERROR", "piwik", $api_data->message);
                return FALSE;
            }
        } else { // api_data = false -> error is already filled
            return FALSE;
        }
    }


    /**
     *  Change a user @TODO: code this 
     */
    function user_edit() {
        return true;
    }

    function get_site_access($user_login) {
        return $this->call_privileged_page('API', 'UsersManager.getSitesAccessFromUser', array('userLogin' => $user_login));
    }

    function get_users_access_from_site($site_id) {
        global $msg, $cuid;

        $msg->debug("piwik","get_users_access_from_site");

        $this->get_alternc_sites();
        $this->get_alternc_users();
        if (!is_numeric($site_id)) {
            $msg->raise("ERROR", 'piwik', 'site_id must be numeric');
            return FALSE;
        }
        if (!in_array($site_id, $this->alternc_sites)) {
            $msg->raise("ERROR", 'piwik', "you don't own this piwik website");
            return FALSE;
        }

        $api_data = $this->call_privileged_page('API', 'UsersManager.getUsersAccessFromSite', array('idSite' => $site_id));
        if ($api_data !== FALSE) {
            $api_data = $api_data[0]; // Data is in the first column
            foreach ($this->alternc_users AS $key=>$user) {
                if (!array_key_exists($user, $api_data)) {
                    $api_data->$user = 'noaccess';
                }
            }
            # Hide users who have access, but are not in AlternC
            foreach (get_object_vars($api_data) as $user => $access) {
                if (!in_array($user, $this->alternc_users)) {
                    unset($api_data->$user);
                }
            }
            return $api_data;
        }
        else return FALSE;
    }


    /**
     * @param string $user_login
     */
    function get_user($user_login) {
        $api_data = $this->call_privileged_page('API', 'UsersManager.getUser', array('userLogin' => $user_login));

        if ($api_data)
            return $api_data[0];
        else
            return FALSE;
    }


    function get_alternc_sites($force=false) {
        global $db, $cuid, $msg;
        
        if (!count($this->alternc_sites) || $force) {
            $db->query("SELECT piwik_id AS site_id FROM piwik_sites WHERE uid= ? ;", array($cuid));
            while ($db->next_record())
                $this->alternc_sites[]=$db->f('site_id');
        }
        return $this->alternc_sites;
    }

    function get_alternc_users() {
        global $db, $cuid, $msg;

        if (!count($this->alternc_users)) {
            $db->query("SELECT login FROM piwik_users WHERE uid= ?;", array($cuid));
            while ($db->next_record())
                $this->alternc_users[]=$db->f('login');
        }
        return $this->alternc_users;
    }


    function get_users_url_infos() {
        global $db,$cuid, $msg;
        $infos_user = array();
        $api_calls = array();


        $db->query("SELECT login, passwd, s.piwik_id as id FROM piwik_users as u INNER JOIN piwik_sites as s on u.uid = s.uid WHERE u.uid = $cuid");
        while ($db->next_record()) {
            $id = $db->f('id');
            $login = $db->f('login');

            if (!isset($infos_user[$id]))
                $infos_user[$id] = array();

            if (!isset($api_calls[$id]))
                $api_calls[$id] = $this->get_users_access_from_site($id);

            foreach ($api_calls[$id] as $l => $cred) {
                if ($l == $login)
                    $infos_user[$id][] = array('login' => $login, 'password' => $db->f('passwd'), 'cred' => $cred);
            }
        }

        return $infos_user;
    }


    /** 
     * does this user has piwik websites configured in AlternC ?
     */
    function user_has_sites() {
        global $db, $cuid, $msg;

        $msg->debug("piwik","user_has_sites");

        $db->query("SELECT id FROM piwik_users WHERE uid='$cuid'");
        if ($db->num_rows() <= 1) {
            $db->query("SELECT id FROM piwik_sites WHERE uid='$cuid'");
            if ($db->num_rows() > 0)
                return true;
        }

        return false;
    }


    /** 
     * Delete a piwik user
     * don't delete it locally unless it has been remotely deleted.
     */
    function user_delete($piwik_user_login) {
        global $db, $cuid, $msg;

        $msg->log("piwik","user_delete");
    
        $db->query("SELECT created_date, COUNT(id) AS cnt FROM piwik_users WHERE uid= ? AND login= ? ", array($cuid, $piwik_user_login));
        $db->next_record();

        if ($db->f('cnt') == 1) {
            $api_data = $this->call_privileged_page('API', 'UsersManager.deleteUser', array('userLogin' => $piwik_user_login));
            if ($api_data->result == 'success') {
                return $db->query("DELETE FROM piwik_users WHERE uid= ? AND login= ? ;", array($cuid, $piwik_user_login));
            }
            else {
                return FALSE;
            }
        } else {
            $msg->raise("ERROR", "piwik", _("You are not allowed to delete the statistics of this website"));
            return FALSE;
        }
    }
 

    function users_list() { 
        global $db, $cuid, $msg;

        $msg->debug("piwik","users_list");

        $db->query("SELECT login FROM piwik_users WHERE uid = ?;", array($cuid));
        if ($db->num_rows() == 0)
            return array();
        $users = '';
        while ($db->next_record())
            $users .= ($users !== '') ? ',' . $db->f('login') : $db->f('login');
        return $this->call_privileged_page('API', 'UsersManager.getUsers', array('userLogins' => $users)); 
    }


    // Verifie que l'utilisateur existe bien dans piwik
    function user_checkremote($puser_id) {
        //FIXME
        return true;
    }


    // Récupére un token pour le SSO avec piwik pour l'user
    function user_remoteauth() {
        //FIXME
        return true;
    }

    // Montre la liste des site pour lesques un user à accés
    function user_access() {
        // FIXME
        return true;
    }



    /***********************/
    /* Site-related tasks */
    /***********************/

    function site_list() {
        global $msg;

        $msg->debug("piwik","site_list");

        $this->get_alternc_sites();
        $api_data = $this->call_privileged_page('API', 'SitesManager.getAllSites',
                                                array('filter_limit' => -1));
        $data = array();

        if($api_data) {
            foreach ($api_data AS $site) {

                if (!in_array($site->idsite, $this->alternc_sites)) 
                    continue;

                $item = new stdClass();

                $item->id       = $site->idsite;
                $item->name     = $site->name;
                $item->main_url = $site->main_url;

                $user_data = $this->call_privileged_page('API', 'UsersManager.getUsersAccessFromSite', array('idSite' => $site->idsite));

                //if (is_array($user_data)) {
                // printvar($user_data);
                //} else if(is_object($user_data)) {
                $item->rights = $user_data[0];
                //}

                $data[] = $item;
            }
            return $data;
        } else
            return FALSE;
    }


    function site_js_tag($site_id) {
        return $this->call_privileged_page('API', 'SitesManager.getJavascriptTag', array('idSite' => $site_id, 'piwikUrl' => $this->piwik_server_uri))->value;
    }


    function get_site_list()
    {
        return $this->call_privileged_page('API', 'SitesManager.getAllSites',
                                           array('filter_limit' => -1));
    }
    // Ajoute un site à Piwik
    // can't figure out how to pass multiple url through the API
    function site_add($siteName, $urls, $ecommerce = FALSE) {
        global $db, $cuid, $piwik, $msg;

        $msg->log("piwik","site_add");

        $this->get_alternc_sites();
        $urls = is_array($urls) ? implode(',', $urls) : $urls;
        $api_data = $this->call_privileged_page('API', 'SitesManager.addSite', array('siteName' => $siteName, 'urls' => $urls));

        if ($api_data->value) {
            $id_site = $api_data->value;

            // Ajout de donner auto les droits de lecture à ce nouvel utilisateur pour le site qu'il a ajouté
            $userslist = $piwik->users_list();
            $api_data = $this->call_privileged_page('API', 'UsersManager.setUserAccess', array('userLogin' => $userslist[0]->login, 'idSites' => $id_site, 'access' => 'view'));

            if ($api_data->result == 'success') {
                // On enregistre le site dans alternC
                $db->query("INSERT INTO piwik_sites set uid= ? , piwik_id= ? ", array($cuid, $id_site));

                // Permet de prendre en compte le site qu'on vient de créer dans la page quis'affiche
                $this->alternc_sites = $this->get_alternc_sites(true);
                return TRUE;
            }
            return TRUE;
        } else
            return FALSE;
    }


    //SitesManager.deleteSite (idSite)
    // Supprime un site de Piwik
    function site_delete($site_id) {
        global $db, $cuid, $msg;

        $msg->log("piwik","site_delete");
    
        $db->query("SELECT COUNT(id) AS cnt FROM piwik_sites WHERE uid= ? AND piwik_id= ? ;", array($cuid, $site_id));
        $db->next_record();

        if ($db->f('cnt') == 1) {
            $api_data = $this->call_privileged_page('API', 'SitesManager.deleteSite', array('idSite' => $site_id));
            if ($api_data->result == 'success') {
                return $db->query("DELETE FROM piwik_sites where uid= ? AND piwik_id= ? LIMIT 1", array($cuid, $site_id));
            } else {
                return FALSE;
            }
        } else {
            $msg->raise("ERROR", "piwik", _("You are not allowed to delete the statistics of this website"));
            return FALSE;
        }

        return true;
    }
 

    function site_set_user_right($site_id, $login, $right)
    {
        global $msg;

        $msg->log("piwik","site_set_user_right");

        if (!in_array($right, array('noaccess', 'view', 'admin')))
            return FALSE;
        $api_data = $this->call_privileged_page('API', 'UsersManager.setUserAccess', array('userLogin' => $login, 'access' => $right, 'idSites' => $site_id));
        if ($api_data->result == 'success') {
            return TRUE;
        } else {
            $msg->raise("ERROR", 'piwik', $api_data->messsage);
            return FALSE;
        }
    }


    // Ajoute un alias sur un site existant
    function site_alias_add() {
        // FIXME
        return true;
    }


    /**
     *  return a clean username with a unique prefix per account 
     */
    function clean_user_name($username) {
        global $admin, $cuid, $db;
        $escaped_name=$db->quote(trim($username));
        $escaped_name=preg_replace("/^'(.*)'/", "\\1", $escaped_name);
        return 'alternc_' . $admin->get_login_by_uid($cuid) . '_' . $escaped_name;
    }


    function dev() {
        // $this->call_page('module', 'method', array('user' => 'fser', 'pass' => 'toto'));
        // return $this->users_list();
    }


    /**
     * @param string $module
     * @param string $method
     */
    function call_page($module, $method, $arguments=array(), $output = 'JSON') {
        global $msg;

        $msg->debug("piwik","call_page");

        $url = sprintf('%s/?module=%s&method=%s&format=%s', $this->piwik_server_uri, $module, $method, $output);
        foreach ($arguments AS $k=>$v)
            $url .= sprintf('&%s=%s', urlencode($k), $v); //  urlencode($v));

        $page_content = file_get_contents($url);
        if ($page_content === FALSE) {
            $msg->raise("ERROR", "piwik", _("Unable to reach the API"));
            return FALSE;
        }

        if ($output == 'JSON') {
            $api_data = json_decode($page_content);
            if ($api_data === FALSE) {
                $msg->raise("ERROR", "piwik", _("Error while decoding response from the API"));
                return FALSE;
            }

            return $api_data;
        } else {
            $msg->raise("ERROR", "piwik", _("Other format than JSON is not implemented yet"));
            return FALSE;
        }
    }


    /**
     * @param string $module
     * @param string $method
     */
    function call_privileged_page($module, $method, $arguments=array(), $output = 'JSON') {
        global $msg;

        $msg->debug("piwik","call_privileged_page");

        $arguments['token_auth'] = $this->piwik_admin_token;
        return $this->call_page($module, $method, $arguments, $output);
    }


} /* Class piwik */
