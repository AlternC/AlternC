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
 * This class manage user sessions in the web desktop.
 *
 * This class manage user sessions and administration in AlternC.
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */
class m_mem {

    /** Original uid for the temporary uid swapping (for administrators) */
    var $olduid = 0;

    /**
     * This array contains the Tableau contenant les champs de la table "membres" du membre courant
     */
    var $user;

    /** 
     * contains all the fields of the "local" table for an account in AlternC.
     * they are specific to the hosting provider
     */
    var $local;

    /**
     * Password kind used in this class (hook for admin class)
     */
    function alternc_password_policy() {
        return array("mem" => "AlternC's account password");
    }


    /**
     * hook called by the m_menu class to add menu to the left of the panel
     */
    function hook_menu() {
        $obj = array(
            'title' => _("Settings"),
            'link' => 'mem_param.php',
            'pos' => 160,
        );

        return $obj;
    }


    /** 
     * Check that the current user is an admnistrator.
     * @return boolean TRUE if we are super user, or FALSE if we are not.
     */
    function checkright() {
        return ($this->user["su"] == "1");
    }


    /** 
     * Start a session in the web desktop. Check username and password.
     * <b>Note : </b>If the user entered a bas password, the failure will be logged
     * and told to the corresponding user on next successfull login.
     * @param $username string Username that want to get connected.
     * @param $password string User Password.
     * @return boolean TRUE if the user has been successfully connected, or FALSE if an error occured.
     */
    function login($username, $password, $restrictip = 0, $authip_token = false) {
        global $db, $msg, $cuid, $authip;
        $msg->log("mem", "login", $username);
	if ($msg->has_msgs("ERROR")) return false;

        $db->query("select * from membres where login= ? ;", array($username));
        if ($db->num_rows() == 0) {
            $msg->raise("ERROR", "mem", _("User or password incorrect"));
            return false;
        }
        $db->next_record();
        if (!password_verify($password, $db->f('pass'))) {
            $db->query("UPDATE membres SET lastfail=lastfail+1 WHERE uid= ? ;", array($db->f("uid")));
            $msg->raise("ERROR", "mem", _("User or password incorrect"));
            return false;
        }
        if (!$db->f("enabled")) {
            $msg->raise("ERROR", "mem", _("This account is locked, contact the administrator."));
            return false;
        }
        $this->user = $db->Record;
        $cuid = $db->f("uid");
        // Transitional code to update md5 hashed passwords to those created
        // with password_hash().
        if (strncmp($db->f('pass'), '$1$', 3) == 0) {
            $db->query("update membres set pass = ? where uid = ?",
                       array(password_hash($password, PASSWORD_BCRYPT), $cuid));
        }

        if (panel_islocked() && $cuid != 2000) {
            $msg->raise("ALERT", "mem", _("This website is currently under maintenance, login is currently disabled."));
            return false;
        }

        // AuthIP
        $allowed_ip = false;
        if ($authip_token) {
            $allowed_ip = $this->authip_tokencheck($authip_token);
        }

        $aga = $authip->get_allowed('panel');
        foreach ($aga as $k => $v) {
            if ($authip->is_in_subnet(get_remote_ip(), $v['ip'], $v['subnet'])) {
                $allowed = true;
            }
        }

        // Error if there is rules, the IP is not allowed and it's not in the whitelisted IP
        if (sizeof($aga) > 1 && !$allowed_ip && !$authip->is_wl(get_remote_ip())) {
            $msg->raise("ERROR", "mem", _("Your IP isn't allowed to connect"));
            return false;
        }
        // End AuthIP

        if ($restrictip) {
            $ip = get_remote_ip();
        } else {
            $ip = "";
        }
        /* Close sessions that are more than 2 days old. */
        $db->query("DELETE FROM sessions WHERE DATE_ADD(ts,INTERVAL 2 DAY)<NOW();");
        /* Delete old impersonation */
        if (isset($_COOKIE["oldid"])) {
            setcookie('oldid', '', 0, '/');
        }
        /* Open the session : */
        $sess = md5(mt_rand().mt_rand().mt_rand());
        $_REQUEST["session"] = $sess;
        $db->query("insert into sessions (sid,ip,uid) values (?, ?, ?);", array($sess, $ip, $cuid));
        setcookie("session", $sess, 0, "/");
        $msg->init_msgs();
        /* Fill in $local */
        $db->query("SELECT * FROM local WHERE uid= ? ;", array($cuid));
        if ($db->num_rows()) {
            $db->next_record();
            $this->local = $db->Record;
        }
        $this->resetlast();
        return true;
    }


    /** 
     * Start a session as another user from an administrator account.
     * This function is not the same as su. setid connect the current user in the destination
     * account (for good), and su allow any user to become another account for some commands only.
     * (del_user, add_user ...) and allow to bring back admin rights with unsu
     * 
     * @param $id integer User id where we will connect to.
     * @return boolean TRUE if the user has been successfully connected, FALSE else.
     */
    function setid($id) {
        global $db, $msg, $cuid, $mysql, $quota;
        $msg->log("mem", "setid", $id);
        $db->query("select * from membres where uid= ? ;", array($id));
        if ($db->num_rows() == 0) {
            $msg->raise("ERROR", "mem", _("User or password incorrect"));
            return false;
        }
        $db->next_record();
        $this->user = $db->Record;
        $cuid = $db->f("uid");
        // And recreate the $db->dbus 
        $mysql->reload_dbus();

        $ip = get_remote_ip();
        $sess = md5(mt_rand().mt_rand().mt_rand());
        $_REQUEST["session"] = $sess;
        $db->query("insert into sessions (sid,ip,uid) values (?, ?, ?);", array($sess, $ip, $cuid));
        setcookie("session", $sess, 0, "/");
        $msg->init_msgs();
        /* Fill in $local */
        $db->query("SELECT * FROM local WHERE uid= ? ;", array($cuid));
        if ($db->num_rows()) {
            $db->next_record();
            $this->local = $db->Record;
        }
        $quota->getquota('', true);
        return true;
    }


    /** 
     * After a successful connection, reset the user's last connection date
     */
    function resetlast() {
        global $db, $cuid;
        $ip = getenv("REMOTE_HOST");
        if (!$ip) {
            $ip = get_remote_ip();
        }
        $db->query("UPDATE membres SET lastlogin=NOW(), lastfail=0, lastip= ? WHERE uid= ?;", array($ip, $cuid));
    }


    function authip_token($bis = false) {
        global $db, $cuid;
        $db->query("select pass from membres where uid= ?;", array($cuid));
        $db->next_record();
        $i = intval(time() / 3600);
        if ($bis) {
            ++$i;
        }
        return md5("$i--" . $db->f('pass'));
    }


    /**
     * @param boolean $t
     */
    function authip_tokencheck($t) {
        return ($t == $this->authip_token() || $t == $this->authip_token(true));
    }

    /* Faut finir de l'implementer :) * /
       function authip_class() {
       global $cuid;
       $c = Array();
       $c['name']="Panel access";
       $c['protocol']="mem";
       $c['values']=Array($cuid=>'');

       return $c;
       }
       /* */


    /** 
     * Check that the current session is correct (valid cookie)
     * If necessary, and if we received username & password fields, 
     * create a new session for the user.
     * This function MUST be called by each page to authenticate the user.
     * and BEFORE sending any data (since a cookie can be sent)
     * @global string $session the session cookie
     * @global string $username & $password the login / pass of the user
     * @return boolean TRUE if the session is OK, FALSE if it is not.
     */
    function checkid($show_msg = true) {
        global $db, $msg, $cuid;

	// We may go here *twice* when login fails. We prevent this with a static variable;
	static $already=false;
	if ($already) return false;
	$already=true;

        if (isset($_REQUEST["username"])) {
            if (empty($_REQUEST['password'])) {
                $msg->raise("ERROR", "mem", _("Missing password"));
                return false;
            }
            if ($_REQUEST["username"] && $_REQUEST["password"]) {
                return $this->login($_REQUEST["username"], $_REQUEST["password"], (isset($_REQUEST["restrictip"]) ? $_REQUEST["restrictip"] : 0));
            }
        } // end isset

        $_COOKIE["session"] = isset($_COOKIE["session"]) ? $_COOKIE["session"] : "";

        if (strlen($_COOKIE["session"]) != 32) {
            if ($show_msg)
                $msg->raise("ERROR", "mem", _("Identity lost or unknown, please login"));
            return false;
        }

        $ip = get_remote_ip();
        $db->query("select uid, ? as me,ip from sessions where sid= ?;", array($ip, $_COOKIE["session"]));
        if ($db->num_rows() == 0) {
            if ($show_msg)
                $msg->raise("ERROR", "mem", _("Identity lost or unknown, please login"));
            return false;
        }
        $db->next_record();
        $cuid = $db->f("uid");

        if (panel_islocked() && $cuid != 2000) {
            $msg->raise("ALERT", "mem", _("This website is currently under maintenance, login is currently disabled."));
            return false;
        }

        $db->query("select * from membres where uid= ? ;", array($cuid));
        $db->next_record();
        $this->user = $db->Record;

        /* Fills $local */
        $db->query("SELECT * FROM local WHERE uid= ? ;", array($cuid));
        if ($db->num_rows()) {
            $db->next_record();
            $this->local = $db->Record;
        }
        return true;
    }


    /** 
     * Change the identity of the user temporarily (SUDO)
     * @global string $uid User that we want to impersonate
     * @return boolean TRUE if it's okay, FALSE if it's not.
     */
    function su($uid) {
        global $cuid, $db, $msg, $mysql;
        if (!$this->olduid) {
            $this->olduid = $cuid;
        }
        $db->query("select * from membres where uid= ? ;", array($uid));
        if ($db->num_rows() == 0) {
            $msg->raise("ERROR", "mem", _("User or password incorrect"));
            return false;
        }
        $db->next_record();
        $this->user = $db->Record;
        $cuid = $db->f("uid");

        // And recreate the $db->dbus 
        $mysql->reload_dbus();
        return true;
    }


    /** 
     * Goes back to the original identity (of an admin, usually)
     * @return boolean TRUE if it's okay, FALSE if it's not.
     */
    function unsu() {
        global $mysql;
        if (!$this->olduid) {
            return false;
        }
        $this->su($this->olduid);
        $this->olduid = 0;
        // And recreate the $db->dbus 
        $mysql->reload_dbus();
        return true;
    }


    /** 
     * Ends a session on the panel (logout)
     * @return boolean TRUE if it's okay, FALSE if it's not.  
     */
    function del_session() {
        global $db, $user, $msg, $cuid, $hooks;
        $_COOKIE["session"] = isset($_COOKIE["session"]) ? $_COOKIE["session"] : '';
        setcookie("session", "", 0, "/");
        setcookie("oldid", "", 0, "/");
        if ($_COOKIE["session"] == "") {
            return true;
        }
        if (strlen($_COOKIE["session"]) != 32) {
            return false;
        }
        $ip = get_remote_ip();
        $db->query("select uid, ? as me,ip from sessions where sid= ? ;", array($ip, $_COOKIE["session"]));
        if ($db->num_rows() == 0) {
            return false;
        }
        $db->next_record();
        $cuid = $db->f("uid");
        $db->query("delete from sessions where sid= ? ;", array($_COOKIE["session"]));

        $hooks->invoke("alternc_del_session");

        session_unset();
        @session_destroy();
        return true;
    }


    /** 
     * Change the password of the current user
     * @param string $oldpass Old password
     * @param string $newpass New password
     * @param string $newpass2 New password (again)
     * @return boolean TRUE if the password has been change, FALSE if not.
     */
    function passwd($oldpass, $newpass, $newpass2) {
        global $db, $msg, $cuid, $admin;
        $msg->log("mem", "passwd");
        if (!$this->user["canpass"]) {
            $msg->raise("ERROR", "mem", _("You are not allowed to change your password."));
            return false;
        }

        if ($this->requires_old_password_for_change()) {
            if (!password_verify($oldpass, $this->user['pass'])) {
                $msg->raise("ERROR", "mem", _("The old password is incorrect"));
                return false;
            }
        }

        if ($newpass != $newpass2) {
            $msg->raise("ERROR", "mem", _("The new passwords are differents, please retry"));
            return false;
        }
        $db->query("SELECT login FROM membres WHERE uid= ? ;", array($cuid));
        $db->next_record();
        $login = $db->Record["login"];
        if (!$admin->checkPolicy("mem", $login, $newpass)) {
            return false; // The error has been raised by checkPolicy()
        }
        $newpass = password_hash($newpass, PASSWORD_BCRYPT);
        $db->query("UPDATE membres SET pass= ? WHERE uid= ?;", array($newpass, $cuid));
        $msg->init_msgs();
        setcookie('require_old_password', '', 1);
        return true;
    }


    /**
     * Change the administrator preferences of an admin account
     * @param integer $admlist visualisation mode of the account list (0=large 1=short)
     * @return boolean TRUE if the preferences has been changed, FALSE if not.
     */
    function adminpref($admlist) {
        global $db, $msg, $cuid;
        $msg->log("mem", "admlist");
        if (!$this->user["su"]) {
            $msg->raise("ERROR", "mem", _("You must be a system administrator to do this."));
            return false;
        }
        $db->query("UPDATE membres SET admlist= ? WHERE uid= ?;", array($admlist, $cuid));
        $msg->init_msgs();
        return true;
    }


    /** 
     * Send a mail with a password to an account
     * <b>Note : </b>We can ask for a password only once a day
     * TODO : Translate this mail into the localization program.
     * TODO : Check this function's !
     * @return boolean TRUE if the password has been sent, FALSE if not.
     */
    function send_pass($login) {
        global $msg, $db, $L_HOSTING, $L_FQDN;
        $msg->log("mem", "send_pass");
        $db->query("SELECT * FROM membres WHERE login= ? ;", array($login));
        if (!$db->num_rows()) {
            $msg->raise("ERROR", "mem", _("This account is locked, contact the administrator."));
            return false;
        }
        $db->next_record();
        if (time() - $db->f("lastaskpass") < 86400) {
            $msg->raise("ERROR", "mem", _("The new passwords are differents, please retry"));
            return false;
        }
        $txt = sprintf(_("Hello,

You requested the modification of your password for your
account %s on %s
Here are your username and password to access the panel :

--------------------------------------

Username : %s
Password : %s

--------------------------------------

Note : if you didn't requested that modification, it means that
someone did it instead of you. You can choose to ignore this message.
If it happens again, please contact your server's Administrator. 

Cordially.
"), $login, $L_HOSTING, $db->f("login"), $db->f("pass"));
        mail($db->f("mail"), "Your password on $L_HOSTING", $txt, "From: postmaster@$L_FQDN\nReply-to: postmaster@$L_FQDN");
        $db->query("UPDATE membres SET lastaskpass= ? WHERE login= ? ;", array(time(), $login));
        return true;
    }


    /** 
     * Change the email of an account (first step: sending of a Cookie)
     * TODO : insert this mail string into the localization system
     * @param string $newmail New mail we want to set for this account
     * @return boolean TRUE if the email with a link has been sent, FALSE if not
     */
    function ChangeMail1($newmail) {
        global $msg, $db, $L_HOSTING, $L_FQDN, $cuid;
        $msg->log("mem", "changemail1", $newmail);
        $db->query("SELECT * FROM membres WHERE uid= ? ;", array($cuid));
        if (!$db->num_rows()) {
            $msg->raise("ERROR", "mem", _("This account is locked, contact the administrator."));
            return false;
        }
        $db->next_record();

        // un cookie de 20 caract�res pour le mail
        $COOKIE = substr(md5(mt_rand().mt_rand()), 0, 20);
        // et de 6 pour la cl� � entrer. ca me semble suffisant...
        $KEY = substr(md5(mt_rand().mt_rand()), 0, 6);
        $link = "https://$L_FQDN/mem_cm.php?usr=$cuid&cookie=$COOKIE&cle=$KEY";
        $txt = sprintf(_("Hello,

Someone (maybe you) requested an email's address modification of the account
%s on %s
To confirm your request, go to this url :

%s

(Warning : if this address is displayed on 2 lines, don't forgot to
take it on one line).
The panel will ask you the key given when the email address
modification was requested.

If you didn't asked for this modification, it means that someone
did it instead of you. You can choose to ignore this message. If it happens
again, please contact your server's administrator.

Cordially.
"), $db->f("login"), $L_HOSTING, $link);
        mail($newmail, "Email modification request on $L_HOSTING", $txt, "From: postmaster@$L_FQDN\nReply-to: postmaster@$L_FQDN");

        $db->query("DELETE FROM chgmail WHERE uid= ? ;", array($cuid));
        $db->query("INSERT INTO chgmail (cookie,ckey,uid,mail,ts) VALUES ( ?, ?, ?, ?, ?);", array($COOKIE, $KEY, $cuid, $newmail, time()));

        $lts = time() - 86400;
        $db->query("DELETE FROM chgmail WHERE ts< ? ;", array($lts));
        return $KEY;
    }


    /** 
     * Change the email of a member (second step, Cookie + key change)
     * @param string $COOKIE Cookie sent by mail
     * @param string $KEY cle shown on the screen
     * @param integer $uid User id (we may not be connected)
     * @return boolean TRUE if the email has been changed, FALSE if not.
     */
    function ChangeMail2($COOKIE, $KEY, $uid) {
        global $msg, $db;
        $msg->log("mem", "changemail2", $uid);
        $db->query("SELECT * FROM chgmail WHERE cookie= ? and ckey= ? and uid= ?;", array($COOKIE, $KEY, $uid));
        if (!$db->num_rows()) {
            $msg->raise("ERROR", "mem", _("The information you entered is incorrect."));
            return false;
        }
        $db->next_record();

        // met a jour le compte :
        $db->query("UPDATE membres SET mail= ? WHERE uid = ? ;", array($db->f("mail"), $uid));

        $db->query("DELETE FROM chgmail WHERE uid= ? ;", array($uid));
        // Supprime les cookies de la veille :)
        $lts = time() - 86400;
        $db->query("DELETE FROM chgmail WHERE ts< ? ;", array($lts));
        return true;
    }


    /** 
     * Change the help parameter
     * @param integer $show Shall we (1) or not (0) show the online help
     */
    function set_help_param($show) {
        global $db, $msg, $cuid;
        $msg->log("mem", "set_help_param", $show);
        $db->query("UPDATE membres SET show_help= ? WHERE uid= ? ;", array(intval($show), $cuid));
    }


    /** 
     * tell if the help parameter is set
     * @return boolean TRUE if the account want online help, FALSE if not.
     */
    function get_help_param() {
        return $this->user["show_help"];
    }


    /** 
     * show (echo) a contextual help
     * @param integer $file File number in the help system to show
     * @return boolean TRUE if the help has been shown, FALSE if not.
     */
    function show_help($file, $force = false) {
        if ((isset($this->user["show_help"]) && $this->user["show_help"]) || $force) {
            $hlp = _("hlp_$file");
            if ($hlp != "hlp_$file") {
                $hlp = preg_replace(
                    "#HELPID_([0-9]*)#", "<a href=\"javascript:help(\\1);\"><img src=\"/aide/help.png\" width=\"17\" height=\"17\" style=\"vertical-align: middle;\" alt=\"" . _("Help") . "\" /></a>", $hlp);
                echo "<p class=\"hlp\">" . $hlp . "</p>";
                return true;
            }
            return false;
        } else {
            return true;
        }
    }


    /**
     * @param integer $uid
     */
    function get_creator_by_uid($uid) {
        global $db, $msg;
        $msg->debug("dom", "get_creator_by_uid");
        $db->query("select creator from membres where uid = ? ;", array($uid));
        if (!$db->next_record()) {
            return false;
        }
        return intval($db->f('creator'));
    }


    /**
     * Exports all the personal user related information for an account.
     * @access private
     */
    function alternc_export_conf() {
        global $db, $msg;
        $msg->log("mem", "export");
        $str = "  <member>\n";
        $users = $this->user;
        $str.="   <uid>" . $users["uid"] . "</uid>\n";
        $str.="   <login>" . $users["login"] . "</login>\n";
        $str.="   <enabled>" . $users["enabled"] . "</enabled>\n";
        $str.="   <su>" . $users["su"] . "</su>\n";
        $str.="   <password>" . $users["pass"] . "</password>\n";
        $str.="   <mail>" . $users["mail"] . "</mail>\n";
        $str.="   <created>" . $users["created"] . "</created>\n";
        $str.="   <lastip>" . $users["lastip"] . "</lastip>\n";
        $str.="   <lastlogin>" . $users["lastlogin"] . "</lastlogin>\n";
        $str.="   <lastfail>" . $users["lastfail"] . "</lastfail>\n";
        $str.=" </member>\n";
        return $str;
    }

    function session_tempo_params_get($v) {
        global $uid;
        if (empty($_COOKIE['session'])) {
            return false;
        }
        $sid = $_COOKIE['session'];
        if (empty($_SESSION[$sid . '-' . $uid])) { // si pas de session de params tempo
            return false;
        }
        $j = $_SESSION[$sid . '-' . $uid];
        $j = json_decode($j, true);
        if (!empty($j[$v])) { // si on a bien qque chose a retourner :)
            return $j[$v];
        }
        return false;
    }

    function session_tempo_params_set($k, $v, $ecrase = false) {
        global $uid;
        if (empty($_COOKIE['session'])) {
            return false;
        }
        $sid = $_COOKIE['session'];
        $p = Array();
        if (!empty($_SESSION[$sid . '-' . $uid])) {
            $p = json_decode($_SESSION[$sid . '-' . $uid], true);
        }
        if (!$ecrase && (isset($p[$k]) && is_array($p[$k])) && is_array($v)) {
            $v = array_merge($p[$k], $v); // overwrite entry with the same name
        }

        $p[$k] = $v;
        $_SESSION[$sid . '-' . $uid] = json_encode($p);
        return true;
    }

    /**
     * Sends a password-reset URL.
     */
    public function send_reset_url($login) {
        global $msg, $L_FQDN, $L_HOSTING, $db;
        // Look up user by login.
        // 'mail' is not a unique key so we can't rely on it.
        $db->query("SELECT * FROM membres WHERE login = ? ;", array($login));

        $msg->log('mem', 'send_reset_url', 'Password reset requested for: ' . $login);
        // Give user feedback, even if we don't have an account stored.
        $msg->raise('INFO', 'mem', _('An e-mail with information on how to connect has been sent to the owner of the account if one exists'));

        // Get the corresponding account.
        if (!$db->num_rows()) {
            $msg->log('mem', 'send_reset_url', 'No member found with login ' . $login);
            return FALSE;
        }
        if ($db->num_rows()) {
            $db->next_record();
            // Get a reset URL for the current timestamp.
            $url = $this->generate_reset_url($db->f('uid'));
            $mail = $db->f('mail');
        }
        if (!$url || !$mail) {
            return FALSE;
        }
        $duration = variable_get('password_reset_expiration', 86400, 'The number of seconds for which a password reset link is valid');
        $duration_hours = ($duration / 3600.0) . ' ' . _('hours');
        $message = sprintf(_('
Hi,

someone requested a password reset for your account at %s (%s).

You may connect to your account and change your account by clicking on the following URL or copying it into your browser :

%s

This link may only be used once. You should change your password in your account settings once connected. This link will only be valid for %s, and no changes will be made if it is not used.
'), $L_HOSTING, $L_FQDN, $url, $duration_hours);
        mail($mail, "Password reset request on {$L_HOSTING}", $message, "From: postmaster@{$L_FQDN}\nReply-to: postmaster@{$L_FQDN}");
        $msg->log('mem', 'send_reset_url', "Password reset e-mail sent for account {$uid} at {$mail}");
    }

    /**
     * Generate a reset URL for an account given its login.
     *
     * @param $login
     *   A string with the login.
     *
     * @returns string|boolean
     *   A reset URL or FALSE in case of error.
     */
    function generate_reset_url($uid) {
        global $db;
        $db->query("SELECT * FROM membres WHERE uid = ? ;", array($uid));
        if (!$db->num_rows()) {
            return FALSE;
        }
        if ($db->num_rows()) {
            $db->next_record();
            // Get a reset URL for the current timestamp.
            return $this->_get_reset_url(time(), $db->f('uid'), $db->f('login'), $db->f('pass'));
        }
        return FALSE;
    }

    /**
     * Builds a full reset URL from the uid, login, password and timestamp.
     *
     * @returns string
     *   A full URL.
     */
    function _get_reset_url($timestamp, $uid, $login, $password) {
        global $db, $L_FQDN;
        $salt = variable_get('salt_password_reset', base64_encode(random_bytes(128)), 'The salt used when hasing password resets - change to invalidate all existing reset tokens') . $password;
        $data = $timestamp . $uid . $login;
        $token = hash_hmac('sha512', $data, $salt);
        // @TODO: Not sure where the bureau's preferred protocol is stored, but
        // since 3.5.0 https seems to be the default.
        return 'https://' . $L_FQDN . '/reset.php?' . http_build_query(array(
            'uid' => $uid,
            'timestamp' => $timestamp,
            'token' => $token,
        ));
    }

    /**
     * Logs a user in from a one-time login link.
     */
    function temporary_login($uid, $timestamp, $token, $restrictip = 0, $authip_token = false) {
        global $db, $msg, $cuid, $authip;
        if (!$this->validate_reset_url($uid, $timestamp, $token)) {
            return FALSE;
        }
        $msg->log("mem", "temporary_login", $username);
        if ($msg->has_msgs("ERROR")) {
            return FALSE;
        }

        $db->query("select * from membres where uid= ? ;", array($uid));
        if ($db->num_rows() == 0) {
            return FALSE;
        }
        $db->next_record();

        // No password verification for temporary logins, the validation
        // is in validate_reset_url instead.
        if (!$db->f("enabled")) {
            $msg->raise("ERROR", "mem", _("This account is locked, contact the administrator."));
            return FALSE;
        }

        $this->user = $db->Record;
        $cuid = $db->f("uid");
        if (panel_islocked() && $cuid != 2000) {
            $msg->raise("ALERT", "mem", _("This website is currently under maintenance, login is currently disabled."));
            return FALSE;
        }

        // AuthIP
        $allowed_ip = FALSE;
        if ($authip_token) {
            $allowed_ip = $this->authip_tokencheck($authip_token);
        }

        $aga = $authip->get_allowed('panel');
        foreach ($aga as $k => $v) {
            if ($authip->is_in_subnet(get_remote_ip(), $v['ip'], $v['subnet'])) {
                $allowed = TRUE;
            }
        }

        // Error if there is rules, the IP is not allowed and it's not in the whitelisted IP
        if (sizeof($aga) > 1 && !$allowed_ip && !$authip->is_wl(get_remote_ip())) {
            $msg->raise("ERROR", "mem", _("Your IP isn't allowed to connect"));
            return FALSE;
        }
        // End AuthIP

        if ($restrictip) {
            $ip = get_remote_ip();
        } else {
            $ip = "";
        }

        // Close sessions that are more than 2 days old.
        $db->query("DELETE FROM sessions WHERE DATE_ADD(ts,INTERVAL 2 DAY)<NOW();");

        // Delete old impersonations.
        if (isset($_COOKIE["oldid"])) {
            setcookie('oldid', '', 0, '/');
        }

        // Open the session
        $sess = md5(mt_rand().mt_rand().mt_rand());
        $_REQUEST["session"] = $sess;
        $db->query("insert into sessions (sid,ip,uid) values (?, ?, ?);", array($sess, $ip, $cuid));
        setcookie("session", $sess, 0, "/");
        $msg->init_msgs();

        // Fill in $local.
        $db->query("SELECT * FROM local WHERE uid= ? ;", array($cuid));
        if ($db->num_rows()) {
            $db->next_record();
            $this->local = $db->Record;
        }
        $this->resetlast();

        // Set a cookie parameter to allow password change without requiring
        // previous one.
        $db->query('select lastlogin, pass from membres where uid = ?;', array($uid));
        if ($db->num_rows()) {
            $db->next_record();
            $cookie_data = $cuid . $db->f('lastlogin');
            $salt = variable_get('salt_password_reset', base64_encode(random_bytes(128))) . $db->f('pass');
            $c = setcookie('require_old_password', hash_hmac('sha512', $cookie_data, $salt), 0, '/');
            if (!$c) {
                $msg->log('mem', 'temporary_login', 'Failed to set cookie require_old_password');
            }
        }
        return TRUE;
    }

    /** TODO : explains this */
    function requires_old_password_for_change() {
        global $cuid, $db;
        if (!isset($_COOKIE['require_old_password'])) return true;
        $db->query('select lastlogin, pass from membres where uid = ?;', array($cuid));
        if ($db->num_rows()) {
            $db->next_record();
            $cookie_data = $cuid . $db->f('lastlogin');
            $salt = variable_get('salt_password_reset', base64_encode(random_bytes(128))) . $db->f('pass');
            if ($cookie == hash_hmac('sha512', $cookie_data, $salt)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validates a reset URL that has been received.
     */
    function validate_reset_url($uid, $timestamp, $token) {
        global $cuid, $db, $msg;
        // Do not log a person in if they are logged in already.
        if ($this->checkid(false)) {
            $msg->raise('ERROR', 'mem', _('You are already logged in, you may not use a one-time login link'));
            $msg->log('mem', 'validate_reset_url', 'Refused one-time log-in since the user is already connected');
            return FALSE;
        }

        // The timestamp is older than the age limit - invalid.
        $fail_message = _('The login-link has already been used or is expired');
        $duration = variable_get('password_reset_expiration', 86400, 'The number of seconds for which a password reset link is valid');
        if (time() - $timestamp >= $duration) {
            $msg->raise('ERROR', 'mem', $fail_message);
            $msg->log('mem', 'validate_reset_url', 'Refused one-time log-in since the time elapsed is greater than limit of ' . $duration);
            return FALSE;
        }

        $db->query("SELECT * FROM membres WHERE uid = ? ;", array($uid));
        if (!$db->num_rows()) {
            $msg->raise('ERROR', 'mem', $fail_message);
            $msg->log('mem', 'validate_reset_url', 'Refused one-time log-in since a user with ID ' . $uid. ' does not exist');
            return FALSE;
        }
        $db->next_record();
        $last_login = strtotime($db->f('lastlogin'));
        // The timestamp is older than the most recent login - invalid.
        if ($last_login >= time() || $last_login >= $timestamp) {
            $msg->raise('ERROR', 'mem', $fail_message);
            $msg->log('mem', 'validate_reset_url', "Refused one-time log-in since the most recent login was more recent than the timestamp in the log-in link. Last: {$last_login}, Timestamp: {$timestamp}");
            return FALSE;
        }

        // The account is locked or cannot change pass - invalid.
        if (!$db->f('enabled') || !$db->f('canpass')) {
            $msg->raise('ERROR', 'mem', $fail_message);
            $msg->log('mem', 'validate_reset_url', 'Refused one-time log-in since the user account is disabled or cannot change it\'s password.');
            return FALSE;
        }

        // Using the current user info and timestamp the tokens generated
        // do not match - invalid. (Eg. user password changed, salt changed).
        $salt = variable_get('salt_password_reset', base64_encode(random_bytes(128))) . $db->f('pass');
        $data = $timestamp . $uid . $db->f('login');
        $ref_token = hash_hmac('sha512', $data, $salt);
        if ($token != $ref_token) {
            $msg->raise('ERROR', 'mem', $fail_message);
            return FALSE;
        }

        $msg->raise('INFO', 'mem', _('You have used a one-time login link. Please set a new password now.'));
        return TRUE;
    }

} /* Class m_mem */
