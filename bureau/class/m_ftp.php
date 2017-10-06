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
 * FTP account management class
 */
class m_ftp {

    var $srv_name;


    /**
     * Constructor
     */
    function m_ftp() {
        global $L_FQDN;
        $this->srv_name = variable_get('ftp_human_name', $L_FQDN, 'Human name for FTP server', array('desc' => 'Name', 'type' => 'string'));
    }


    /**
     * Password kind used in this class (hook for admin class)
     */
    function alternc_password_policy() {
        return array("ftp" => "FTP accounts");
    }


    /**
     * hook function called by menu class
     * to add menu to the left panel
     */
    function hook_menu() {
        global $quota;
        $q = $quota->getquota("ftp");

        $obj = array(
            'title' => _("FTP accounts"),
            'ico' => 'images/ftp.png',
            'link' => 'toggle',
            'pos' => 100,
            'links' => array(),
        );

        if ($quota->cancreate("ftp")) {
            $obj['links'][] = array(
                'ico' => 'images/new.png',
                'txt' => _("Create a new ftp account"),
                'url' => "ftp_edit.php?create=1",
                'class' => '',
            );
        }

        if ($q['u'] > 0) { // if there are some FTP accounts
            $obj['links'][] = array(
                'txt' => _("FTP accounts list"),
                'url' => "ftp_list.php"
            );
        }

        return $obj;
    }


    /**
     * Return the values needed to activate security access. See get_auth_class()
     * in authip for more informations
     */
    function authip_class() {
        $c = Array();
        $c['name'] = "FTP";
        $c['protocol'] = "ftp";
        $c['values'] = Array();

        $tt = $this->get_list();
        if (empty($tt) || !is_array($tt)) {
            return $c;
        }
        foreach ($this->get_list() as $v) {
            $c['values'][$v['id']] = $v['login'];
        }

        return $c;
    }


    /**
     * Switch enabled status of an account
     */
    function switch_enabled($id, $status = null) {
        global $cuid, $db, $msg;
        if (!$jj = $this->get_ftp_details($id)) {
            $msg->raise("ERROR", 'ftp', _("This account do not exist or is not of this account"));
            return false;
        }
        if ($status == null) {
            if ($jj[0]['enabled'] == true) {
                $status = 0;
            } else {
                $status = 1;
            }
        }
        // Be sure what is in $status, in case of it was a parameter
        $status = ($status ? 1 : 0);

        if (!$db->query("UPDATE ftpusers SET enabled = ? WHERE uid = ? AND id = ? ;", array($status, $cuid, $id))) {
            $msg->raise("ERROR", 'ftp', _("Error during update"));
            return false;
        } else {
            return true;
        }
    }


    /** 
     * Retourne la liste des comptes FTP du compte h�berg�
     * Retourne la liste des comptes FTP sous forme de tableau index� de
     * tableaus associatifs comme suit :
     * $a["id"]= ID du compte ftp
     * $a["login"]= Nom de login du compte
     * $a["dir"]= Dossier relatif � la racine du compte de l'utilisateur
     * @return array Retourne le tableau des comptes 
     */
    function get_list() {
        global $db, $msg, $cuid;
        $msg->log("ftp", "get_list");
        $r = array();
        $db->query("SELECT id, name, homedir, enabled FROM ftpusers WHERE uid= ? ORDER BY name;", array($cuid));
        if ($db->num_rows()) {
            while ($db->next_record()) {
                $r[] = array(
                    "id" => $db->f("id"),
                    "login" => $db->f("name"),
                    "enabled" => $db->f("enabled"),
                    //"dir"=>$match[1]
                    "dir" => $db->f("homedir")
                );
            }
            return $r;
        } else {
            $msg->raise("INFO", "ftp", _("No FTP account found"));
            return array();
        }
    }


    /** 
     * Retourne les details d'un compte FTP (voir get_list)
     * Le tableau est celui du compte d'id specifie
     * @param integer $id Numero du compte dont on souhaite obtenir les d�tails
     * @return array Tableau associatif contenant les infos du comptes ftp
     */
    function get_ftp_details($id) {
        global $db, $msg, $cuid;
        $msg->log("ftp", "get_ftp_details", $id);
        $r = array();
        $db->query("SELECT id, name, homedir, enabled FROM ftpusers WHERE uid= ? AND id= ?;", array($cuid, $id));
        if ($db->num_rows()) {
            $db->next_record();

            $regexp = "/^" . preg_quote(getuserpath(), "/") . "\/(.*)$/";
            $match = array();
            preg_match($regexp, $db->f("homedir"), $match);

            $lg = explode("_", $db->f("name"));
            if ((!is_array($lg)) || (count($lg) != 2)) {
                $lg[0] = $db->f("name");
                $lg[1] = "";
            }
            $r[] = array(
                "id" => $db->f("id"),
                "prefixe" => $lg[0],
                "login" => $lg[1],
                "dir" => $match[1],
                "enabled" => $db->f("enabled")
            );
            return $r;
        } else {
            $msg->raise("ERROR", "ftp", _("This FTP account does not exist"));
            return false;
        }
    }


    /** 
     * Retourne la liste des prefixes utilisables par le compte courant
     * @return array tableau contenant la liste des prefixes (domaines + login)
     *  du compte actuel.
     */
    function prefix_list() {
        global $db, $mem, $cuid;
        $r = array();
        $r[] = $mem->user["login"];
        $db->query("SELECT domaine FROM domaines WHERE compte= ? ORDER BY domaine;", array($cuid));
        while ($db->next_record()) {
            $r[] = $db->f("domaine");
        }
        return $r;
    }


    /**
     * Check if the login is fine (syntax)
     * @param string $l
     */
    function check_login($l) {
        global $msg;
        // special chars and the max numbers of them allowed
        // to be able to give a specific error
        $vv = array('_' => '1', ' ' => 0);
        foreach ($vv as $k => $n) {
            if (substr_count($l, $k) > $n) { // if there is more than $n $k
                $msg->raise("ERROR", 'ftp', sprintf(_("FTP login is incorrect: too many '%s'"), $k));
                return false;
            }
        }
        // Explicitly look for only allowed chars
        if (!preg_match("/^[A-Za-z0-9]+[A-Za-z0-9_\.\-]*$/", $l)) {
            $msg->raise("ERROR", 'ftp', _("FTP login is incorrect"));
            return false;
        }
        return true;
    }


    /** 
     * Affiche (ECHO) la liste des prefixes disponibles sous forme de champs d'option
     * Les champs sont affich�s sous la forme <option>prefixe</option>...
     * La valeur $current se voit affubl�e de la balise SELECTED.
     * @param string $current Prefixe s�lectionn� par d�faut
     * @return boolean TRUE.
     */
    function select_prefix_list($current) {
        $r = $this->prefix_list();
        reset($r);
        while (list($key, $val) = each($r)) {
            if ($current == $val) {
                $c = " selected=\"selected\"";
            } else {
                $c = "";
            }
            echo "<option$c>$val</option>";
        }
        return true;
    }


    /** 
     * Modifie les param�tres du comptes FTP $id.
     * @param integer $id Num�ro du compte dont on veut modifier les param�tres
     * @param string $prefixe Prefixe du compte FTP
     * @param string $login login ajout� au pr�fixe ($prefixe_$login)
     * @param string $pass mot de passe
     * @param string $dir R�pertoire racine du compte
     * @return boolean TRUE si le compte a �t� modifi�, FALSE si une erreur est survenue.
     */
    function put_ftp_details($id, $prefixe, $login, $pass, $dir) {
        global $db, $msg, $bro, $cuid, $admin;
        $msg->log("ftp", "put_ftp_details", $id);
        $db->query("SELECT count(*) AS cnt FROM ftpusers WHERE id= ? and uid= ?;", array($id, $cuid));
        $db->next_record();
        if (!$db->f("cnt")) {
            $msg->raise("ERROR", "ftp", _("This FTP account does not exist"));
            return false;
        }
        $dir = $bro->convertabsolute($dir);
        if (substr($dir, 0, 1) == "/") {
            $dir = substr($dir, 1);
        }
        $r = $this->prefix_list();
        if (!in_array($prefixe, $r)) {
            $msg->raise("ERROR", "ftp", _("The chosen prefix is not allowed"));
            return false;
        }

        $full_login = $prefixe;
        if ($login) {
            $full_login.="_" . $login;
        }
        if (!$this->check_login($full_login)) {
            return false;
        }
        $db->query("SELECT COUNT(*) AS cnt FROM ftpusers WHERE id!= ? AND name= ?;", array($id, $full_login));
        $db->next_record();
        if ($db->f("cnt")) {
            $msg->raise("ERROR", "ftp", _("This FTP account already exists"));
            return false;
        }
        $absolute = getuserpath() . "/$dir";
        if (!file_exists($absolute)) {
            system("/bin/mkdir -p $absolute");
        }
        if (!is_dir($absolute)) {
            $msg->raise("ERROR", "ftp", _("The directory cannot be created"));
            return false;
        }
        if (trim($pass)) {

            // Check this password against the password policy using common API : 
            if (is_callable(array($admin, "checkPolicy"))) {
                if (!$admin->checkPolicy("ftp", $full_login, $pass)) {
                    return false; // The error has been raised by checkPolicy()
                }
            }
            $encrypted_password = _md5cr($pass, strrev(microtime(true)));
            $db->query("UPDATE ftpusers SET name= ? , password='', encrypted_password= ?, homedir= ?, uid= ? WHERE id= ?;", array($full_login, $encrypted_password, $absolute, $cuid, $id));
        } else {
            $db->query("UPDATE ftpusers SET name= ? , homedir= ? , uid= ? WHERE id= ? ;", array($full_login, $absolute, $cuid, $id));
        }
        return true;
    }


    /** 
     * Efface le compte ftp specifie
     * @param integer $id Numero du compte FTP a supprimer.
     * @return boolean TRUE si le compte a ete efface, FALSE sinon.
     */
    function delete_ftp($id) {
        global $db, $msg, $cuid;
        $msg->log("ftp", "delete_ftp", $id);
        $db->query("SELECT name FROM ftpusers WHERE id= ? and uid= ? ;", array($id, $cuid));
        $db->next_record();
        $name = $db->f("name");
        if (!$name) {
            $msg->raise("ERROR", "ftp", _("This FTP account does not exist"));
            return false;
        }
        $db->query("DELETE FROM ftpusers WHERE id= ? ;", array($id));
        return $name;
    }


    /** 
     * Cree un nouveau compte FTP.
     * @param string $prefixe Prefixe au login
     * @param string $login Login ftp (login=prefixe_login)
     * @param string $pass Mot de passe FTP
     * @param string $dir Repertoire racine du compte relatif à la racine du membre
     * @return boolean TRUE si le compte a ete cree, FALSE sinon.
     */
    function add_ftp($prefixe, $login, $pass, $dir) {
        global $db, $msg, $quota, $bro, $cuid, $admin;
        $msg->log("ftp", "add_ftp", $prefixe . "_" . $login);
        $dir = $bro->convertabsolute($dir);
        if (substr($dir, 0, 1) == "/") {
            $dir = substr($dir, 1);
        }
        $r = $this->prefix_list();
        if (empty($pass)) {
            $msg->raise("ERROR", "ftp", _("Password can't be empty"));
            return false;
        }
        if (!in_array($prefixe, $r) || $prefixe == "") {
            $msg->raise("ERROR", "ftp", _("The chosen prefix is not allowed"));
            return false;
        }
        $full_login = $prefixe;
        if ($login) {
            $full_login.="_" . $login;
        }
        if (!$this->check_login($full_login)) {
            return false;
        }
        $db->query("SELECT count(*) AS cnt FROM ftpusers WHERE name= ? ;", array($full_login));
        $db->next_record();
        if ($db->f("cnt")) {
            $msg->raise("ERROR", "ftp", _("This FTP account already exists"));
            return false;
        }
        $db->query("SELECT login FROM membres WHERE uid= ? ;", array($cuid));
        $db->next_record();
        $absolute = getuserpath() . "/$dir";
        if (!file_exists($absolute)) {
            system("/bin/mkdir -p $absolute"); // FIXME replace with action
        }
        if (!is_dir($absolute)) {
            $msg->raise("ERROR", "ftp", _("The directory cannot be created"));
            return false;
        }

        // Check this password against the password policy using common API : 
        if (is_callable(array($admin, "checkPolicy"))) {
            if (!$admin->checkPolicy("ftp", $full_login, $pass)) {
                return false; // The error has been raised by checkPolicy()
            }
        }

        if ($quota->cancreate("ftp")) {
            $encrypted_password = _md5cr($pass, strrev(microtime(true)));
            $db->query("INSERT INTO ftpusers (name,password, encrypted_password,homedir,uid) VALUES ( ?, '', ?, ?, ?)", array($full_login, $encrypted_password, $absolute, $cuid));
            return true;
        } else {
            $msg->raise("ERROR", "ftp", _("Your FTP account quota is over. You cannot create more ftp accounts"));
            return false;
        }
    }


    /** 
     * Retourne TRUE si $dir possee un compte FTP
     * @param string $dir Dossier a tester, relatif a la racine du compte courant
     * @return boolean retourne TRUE si $dir a un compte FTP, FALSE sinon.
     */
    function is_ftp($dir) {
        global $db, $msg;
        $msg->log("ftp", "is_ftp", $dir);
        if (substr($dir, 0, 1) == "/") {
            $dir = substr($dir, 1);
        }
        $db->query("SELECT id FROM ftpusers WHERE homedir= ? ;", array( getuserpath() . "/" .$dir ));
        if ($db->num_rows()) {
            $db->next_record();
            return $db->f("id");
        } else {
            return false;
        }
    }


    /** 
     * Fonction appellee par domains quand un domaine est supprime pour le membre
     * @param string $dom Domaine à detruire.
     * @access private
     */
    function alternc_del_domain($dom) {
        global $db, $msg, $cuid;
        $msg->log("ftp", "alternc_del_domain", $dom);
        $db->query("DELETE FROM ftpusers WHERE uid= ? AND ( name LIKE ? OR name LIKE ?) ", array($cuid, $dom."\_%", $dom));
        return true;
    }


    /** 
     * Fonction appellee par membres quand un membre est efface
     * @access private
     */
    function alternc_del_member() {
        global $db, $msg, $cuid;
        $msg->log("ftp", "alternc_del_member");
        $db->query("DELETE FROM ftpusers WHERE uid= ?", array($cuid));
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
        $msg->log("ftp", "getquota");
        $q = Array("name" => "ftp", "description" => _("FTP accounts"), "used" => 0);
        $db->query("SELECT COUNT(*) AS cnt FROM ftpusers WHERE uid= ? ", array($cuid));
        if ($db->next_record()) {
            $q['used'] = $db->f("cnt");
        }
        return $q;
    }


    /**
     * Exporte toutes les informations ftp du compte AlternC
     * @access private
     * EXPERIMENTAL 'sid' function ;) 
     */
    function alternc_export_conf() {
        global $db, $msg;
        $msg->log("ftp", "export");
        $f = $this->get_list();
        $str = "  <ftp>";
        foreach ($f as $d => $v) {
            $str.="   <login>" . ($v["login"]) . "</login>\n";
            $str.="   <password>" . ($v["encrypted_password"]) . "</password>\n";
            $str.="   <directory>" . ($v["dir"]) . "<directory>\n";
        }
        $str.=" </ftp>\n";
        return $str;
    }


    /** 
     * hook function called by AlternC-upnp to know which open 
     * tcp or udp ports this class requires or suggests
     * @return array a key => value list of port protocol name mandatory values
     * @access private
     */
    function hook_upnp_list() {
        return array(
            "ftp" => array("port" => 21, "protocol" => "tcp", "mandatory" => 1),
        );
    }

} /* Class m_ftp */

