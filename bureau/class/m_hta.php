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
 * This class handle folder web restricted access through .htaccess/.htpassword
 * files.
 * 
 */
class m_hta {


    /**
     * Password kind used in this class (hook for admin class)
     * @return array
     */
    function alternc_password_policy() {
        return array("hta" => "Protected folders passwords");
    }


    /**
     * hook called by menu class to add a menu 
     * to the left panel
     * @return array
     */
    function hook_menu() {
        $obj = array(
            'title' => _("Protected folders"),
            'ico' => 'images/password.png',
            'link' => 'hta_list.php',
            'pos' => 50,
        );

        return $obj;
    }


    /**
     * Create a protected folder (.htaccess et .htpasswd)
     * @param     string $dir Folder to protect (relative to user root)
     * @return    boolean TRUE if the folder has been protected, or FALSE if an error occurred
     * 
     * @global    m_mem   $mem
     * @global    m_bro   $bro
     * @global    m_messages   $msg
     * @param     string  $dir
     * @return    boolean
     */
    function CreateDir($dir) {
        global $bro, $msg;
        $msg->log("hta", "createdir", $dir);
        $absolute = $bro->convertabsolute($dir, 0);
        if (!is_dir($absolute)) {
            $msg->raise("ERROR", "hta", _("The folder '%s' does not exist"), $dir);
            return false;
        }
        if (!file_exists("$absolute/.htaccess")) {
            if (!@touch("$absolute/.htaccess")) {
                $msg->raise("ERROR", "hta", _("File already exist"));
                return false;
            }
            $file = @fopen("$absolute/.htaccess", "r+");
            if (!$file) {
                $msg->raise("ERROR", "hta", _("File already exist"));
                return false;
            }
            fseek($file, 0);
            $param = "AuthUserFile \"$absolute/.htpasswd\"\nAuthName \"" . _("Restricted area") . "\"\nAuthType Basic\nrequire valid-user\n";
            fwrite($file, $param);
            fclose($file);
        }
        if (!file_exists("$absolute/.htpasswd")) {
            if (!touch("$absolute/.htpasswd")) {
                $msg->raise("ERROR", "hta", _("File already exist"));
                return false;
            }
            return true;
        }
        return true;
    }


    /**
     * Returns the list of all user folder currently protected by a .htpasswd file
     * 
     * @global    m_messages   $msg
     * @global    m_mem   $mem
     * @return    array           Array containing user folder list
     */
    function ListDir() {
        global$msg, $mem;
        $msg->log("hta", "listdir");
        $sortie = array();
        $absolute = ALTERNC_HTML . "/" . substr($mem->user["login"], 0, 1) . "/" . $mem->user["login"];
        exec("find " . escapeshellarg($absolute) . " -name .htpasswd|sort", $sortie);
        if (!count($sortie)) {
            $msg->raise("INFO", "hta", _("No protected folder"));
            return false;
        }
        $pattern = "/^" . preg_quote(ALTERNC_HTML, "/") . "\/.\/[^\/]*\/(.*)\/\.htpasswd/";

        $r = array();
        for ($i = 0; $i < count($sortie); $i++) {
            $matches = array();
            preg_match($pattern, $sortie[$i], $matches);
            $tmpm = isset($matches[1]) ? '/' . $matches[1] : '';
            $r[$i] = $tmpm . "/";
        }
        return $r;
    }


    /**
     * Tells if a folder is protected.
     * 
     * @global    m_mem   $mem
     * @global    m_messages   $msg
     * @param     string  $dir    Folder to check
     * @return    boolean         If the folder is protected, or FALSE if it is not
     */
    function is_protected($dir) {
        global $mem, $msg;
        $msg->log("hta", "is_protected", $dir);
        $absolute = ALTERNC_HTML . "/" . substr($mem->user["login"], 0, 1) . "/" . $mem->user["login"] . "/$dir";
        if (file_exists("$absolute/.htpasswd")) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Returns the list of login for a protected folder.
     * 
     * @global    m_mem   $mem
     * @global    m_messages   $msg
     * @param     string  $dir    The folder to lookup (relative to user root)
     * @return    array           An array containing the list of logins from the .htpasswd file, or FALSE
     */
    function get_hta_detail($dir) {
        global $mem, $msg;
        $msg->log("hta", "get_hta_detail");
        $absolute = ALTERNC_HTML . "/" . substr($mem->user["login"], 0, 1) . "/" . $mem->user["login"] . "/$dir";
        if (file_exists("$absolute/.htaccess")) {
            /* 		if (!_reading_htaccess($absolute)) {
                    return false;
                    }
            */
        }
        $file = @fopen("$absolute/.htpasswd", "r");
        $i = 0;
        $res = array();
        if (!$file) {
            return false;
        }
        // TODO: Test the validity of a .htpasswd
        while (!feof($file)) {
            $s = fgets($file, 1024);
            $t = explode(":", $s);
            if ($t[0] != $s) {
                $res[$i] = $t[0];
                $i = $i + 1;
            }
        }
        fclose($file);
        return $res;
    }


    /**
     * Unprotect a folder
     * 
     * @global    m_mem   $mem
     * @global    m_bro   $bro
     * @global    m_messages   $msg
     * @param     string  $dir    Folder to unprotect, relative to user root
     * @param     boolean $skip   For testing purpose mainly, skips the full user path search
     * @return    boolean         TRUE if the folder has been unprotected, or FALSE if an error occurred
     */
    function DelDir($dir, $skip = false) {
        global $bro, $msg;
        $msg->log("hta", "deldir", $dir);
        $dir = $bro->convertabsolute($dir, $skip);
        if (!$dir) {
            $msg->raise("ERROR", "hta", ("The folder '%s' does not exist"), $dir);
            return false;
        }
        $htaccess_file = "$dir/.htaccess";
        if (!is_readable($htaccess_file)) {
            $msg->raise("ERROR", "hta", _("I cannot read the file '%s'"), $htaccess_file);
        }
        $fileLines = file($htaccess_file);
        $patternList = array(
            "AuthUserFile.*$",
            "AuthName.*$",
            "AuthType Basic.*$",
            "require valid-user.*$"
        );
        $count_lines = 0;
        foreach ($fileLines as $key => $line) {
            foreach ($patternList as $pattern) {
                if (preg_match("/" . $pattern . "/", $line)) {
                    $count_lines++;
                    unset($fileLines[$key]);
                }
            }
        }
        // If no changes 
        if (!$count_lines) {
            $msg->raise("ALERT", "hta", _("Unexpected: No changes made to '%s'"), $htaccess_file);
        }
        // If file is empty, remove it
        if (!count($fileLines)) {
            if (!unlink($htaccess_file)) {
                $msg->raise("ERROR", "hta", _("I could not delete the file '%s'"), $htaccess_file);
            }
        } else {
            file_put_contents($htaccess_file, implode("\n", $fileLines));
        }
        $htpasswd_file = "$dir/.htpasswd";
        if (!is_writable($htpasswd_file)) {
            $msg->raise("ERROR", "hta", _("I cannot read the file '%s'"), $htpasswd_file);
        } else if (!unlink($htpasswd_file)) {
            $msg->raise("ERROR", "hta", _("I cannot delete the file '%s/.htpasswd'"), $dir);
            return false;
        }

        return true;
    }


    /**
     * Add a user to a protected folder
     * 
     * @global    m_messages   $msg
     * @global    m_bro   $bro
     * @global    m_admin $admin
     * @param     string  $user
     * @param     string  $password
     * @param     string  $dir
     * @param     string  $password   The password to add (cleartext)
     * @param     string  $dir        The folder we add it to (relative to user root).
     * @return    boolean             TRUE if the user has been added, or FALSE if an error occurred
     */
    function add_user($user, $password, $dir) {
        global $msg, $bro, $admin;
        $msg->log("hta", "add_user", $user . "/" . $dir);
        if (empty($user)) {
            $msg->raise("ERROR", 'hta', _("Please enter a user"));
            return false;
        }
        if (empty($password)) {
            $msg->raise("ERROR", 'hta', _("Please enter a password"));
            return false;
        }
        $absolute = $bro->convertabsolute($dir, 0);
        if (!file_exists($absolute)) {
            $msg->raise("ERROR", "hta", _("The folder '%s' does not exist"), $dir);
            return false;
        }
        // @todo delete cf!. functions.php checkloginemail definition
        if (checkloginmail($user)) {
            // Check this password against the password policy using common API : 
            if (is_callable(array($admin, "checkPolicy"))) {
                if (!$admin->checkPolicy("hta", $user, $password)) {
                    return false; // The error has been raised by checkPolicy()
                }
            }

            $file = @fopen("$absolute/.htpasswd", "a+");
            if (!$file) {
                $msg->raise("ERROR", "hta", _("File already exist"));
                return false;
            }
            fseek($file, 0);
            while (!feof($file)) {
                $s = fgets($file, 1024);
                $t = explode(":", $s);
                if ($t[0] == $user) {
                    $msg->raise("ERROR", "hta", _("The user '%s' already exist for this folder"), $user);
                    return false;
                }
            }
            fseek($file, SEEK_END);
            if (empty($t[1]) || substr($t[1], -1) != "\n") {
                fwrite($file, "\n");
            }
            fwrite($file, "$user:" . _md5cr($password) . "\n");
            fclose($file);
            return true;
        } else {
            $msg->raise("ERROR", "hta", _("Please enter a valid username"));
            return false;
        }
    }


    /**
     * Delete a user from a protected folder.
     * 
     * @global    m_bro   $bro
     * @global    m_messages   $msg
     * @param     array   $lst    An array with login to delete.
     * @param     string  $dir    The folder, relative to user root, where we want to delete users.
     * @return    boolean         TRUE if users has been deleted, or FALSE if an error occurred.
     */
    function del_user($lst, $dir) {
        global $bro, $msg;
        $msg->log("hta", "del_user", $lst . "/" . $dir);
        $absolute = $bro->convertabsolute($dir, 0);
        if (!file_exists($absolute)) {
            $msg->raise("ERROR", "hta", _("The folder '%s' does not exist"), $dir);
            return false;
        }
        touch("$absolute/.htpasswd.new");
        $file = fopen("$absolute/.htpasswd", "r");
        $newf = fopen("$absolute/.htpasswd.new", "a");
        if (!$file || !$newf) {
            $msg->raise("ERROR", "hta", _("File already exist"));
            return false;
        }
        reset($lst);
        fseek($file, 0);
        while (!feof($file)) {
            $s = fgets($file, 1024);
            $t = explode(":", $s);
            if (!in_array($t[0], $lst) && ($t[0] != "\n")) {
                fseek($newf, 0);
                fwrite($newf, "$s");
            }
        }
        fclose($file);
        fclose($newf);
        unlink("$absolute/.htpasswd");
        rename("$absolute/.htpasswd.new", "$absolute/.htpasswd");
        return true;
    }


    /**
     * Change the password of a user in a protected folder
     * @param string $user The users whose password should be changed
     * @param string $newpass The new password of this user
     * @param string $dir The folder, relative to user root, in which we will change a password
     * @return boolean TRUE if the password has been changed, or FALSE if an error occurred
     */
    function change_pass($user, $newpass, $dir) {
        global $bro, $msg, $admin;
        $msg->log("hta", "change_pass", $user . "/" . $dir);
        $absolute = $bro->convertabsolute($dir, 0);
        if (!file_exists($absolute)) {
            $msg->raise("ERROR", "hta", _("The folder '%s' does not exist"), $dir);
            return false;
        }

        // Check this password against the password policy using common API : 
        if (is_callable(array($admin, "checkPolicy"))) {
            if (!$admin->checkPolicy("hta", $user, $newpass)) {
                return false; // The error has been raised by checkPolicy()
            }
        }

        touch("$absolute/.htpasswd.new");
        $file = fopen("$absolute/.htpasswd", "r");
        $newf = fopen("$absolute/.htpasswd.new", "a");
        if (!$file || !$newf) {
            $msg->raise("ERROR", "hta", _("File already exist"));
            return false;
        }
        while (!feof($file)) {
            $s = fgets($file, 1024);
            $t = explode(":", $s);
            if ($t[0] != $user) {
                fwrite($newf, "$s");
            }
        }
        fwrite($newf, "$user:" . _md5cr($newpass) . "\n");
        fclose($file);
        fclose($newf);
        unlink("$absolute/.htpasswd");
        rename("$absolute/.htpasswd.new", "$absolute/.htpasswd");
        return true;
    }


    /**
     * Check that a .htaccess file is valid (for authentication)
     * 
     * @global    m_messages   $msg
     * @param     type    $absolute
     * @param     string  $absolute   Folder we want to check (relative to user root)
     * @return    boolean             TRUE is the .htaccess is protecting this folder, or FALSE else
     */
    private function _reading_htaccess($absolute) {
        global $msg;
        $msg->log("hta", "_reading_htaccess", $absolute);
        $file = fopen("$absolute/.htaccess", "r+");
        $lignes = array(1, 1, 1);
        $errr = 0;
        if (!$file) {
            return false;
        }
        while (!feof($file) && !$errr) {
            $s = fgets($file, 1024);
            if (substr($s, 0, 12) != "RewriteCond " && substr($s, 0, 14) != "ErrorDocument " && substr($s, 0, 12) != "RewriteRule " && substr($s, 0, 14) != "RewriteEngine " && trim($s) != "") {
                $errr = 1;
            }
            if (strtolower(trim($s)) == strtolower("authuserfile $absolute/.htpasswd")) {
                $lignes[0] = 0;
                $errr = 0;
            } // authuserfile
            if (strtolower(trim($s)) == "require valid-user") {
                $lignes[1] = 0;
                $errr = 0;
            } //require
            if (strtolower(trim($s)) == "authtype basic") {
                $lignes[2] = 0;
                $errr = 0;
            } //authtype
        } // Reading config file
        fclose($file);
        if ($errr || in_array(0, $lignes)) {
            $msg->raise("ERROR", "hta", _("An incompatible .htaccess file exists in this folder"));
            return false;
        }
        return true;
    }

} /* class m_hta */

