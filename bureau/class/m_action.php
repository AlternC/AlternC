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
 * This class manage actions to be performed on the file system on behalf of alternc Classes
 * It primary use is to store the actions to be performed ( creating file or folder, deleting, setting permissions etc..) in the action sql table. 
 * The script /usr/lib/alternc/do_actions.php handled by cron and incron is then used to perform those actions.
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */
class m_action {


    /**
     * Tell the incron that an action should be performed
     * 
     * @global m_messages $msg
     * @global string $L_INOTIFY_DO_ACTION
     * @return boolean
     */
    function do_action() {
        global $msg, $L_INOTIFY_DO_ACTION;
        $msg->log("action", "do_action");
        if (!@touch($L_INOTIFY_DO_ACTION)) {
            return FALSE;
        }
        return TRUE;
    }


    /**
     * Plans a file creation
     * 
     * @param string  $file
     * @param string $content
     * @param int $user
     * @return boolean
     */
    function create_file($file, $content = "", $user = "root") {
        return $this->set('create_file', $user, array('file' => $file, 'content' => $content));
    }


    /**
     * Plans the a chmod on file or dir
     * 
     * @param string $filename
     * @param int $perms
     * @param string $user
     * @return boolean
     */
    function chmod($filename, $perms, $user = "root") {
        return $this->set('chmod', $user, array('filename' => $filename, "perms" => $perms));
    }


    /**
     * Plans the creation of a dir 
     * 
     * @param string $dir
     * @param int $user
     * @return boolean
     */
    function create_dir($dir, $user = "root") {
        return $this->set('create_dir', $user, array('dir' => $dir));
    }


    /**
     * Plans a perms fix upon user creation 
     * @param int $uid
     * @param string $user
     * @return boolean
     */
    function fix_user($uid, $user = "root") {
        return $this->set('fix_user', $user, array('uid' => $uid));
    }


    /**
     * Plans a dir permission fix
     * 
     * @param string $dir
     * @param m_user $user
     * @return boolean
     */
    function fix_dir($dir, $user = "root") {
        return $this->set('fix_dir', $user, array('dir' => $dir));
    }


    /**
     * Plans a file permission fix
     * 
     * @param string $file
     * @param m_user $user
     * @return boolean
     */
    function fix_file($file, $user = "root") {
        return $this->set('fix_file', $user, array('file' => $file));
    }


    /**
     * function to delete file / folder
     * 
     * @param string $dir
     * @param m_user $user
     * @return boolean
     */
    function del($dir, $user = "root") {
        return $this->set('delete', $user, array('dir' => $dir));
    }


    /**
     * function returning the first not locked line of the action table 
     * 
     * @param string $src
     * @param string $dst
     * @param m_user $user
     * @return boolean
     */
    function move($src, $dst, $user = "root") {
        return $this->set('move', $user, array('src' => $src, 'dst' => $dst));
    }


    /**
     * 
     * function archiving a directory ( upon account deletion )
     * 
     * @global int $cuid
     * @global m_mysql $db
     * @global m_messages $msg
     * @param string $archive Directory to archive within the archive_del_data folder if set in variable sql table
     *                      If archive_del_data is not set we delete the folder
     * @param string $dir  sub_directory of the archive directory
     * @return boolean
     */
    function archive($archive, $dir = "html") {
        global $cuid, $db, $msg;

        $arch = variable_get('archive_del_data');
        if (empty($arch)) {
            $this->del($archive);
            return true;
        }
        $BACKUP_DIR = $arch;
        $db->query("select login from membres where uid= ?;", array($cuid));
        $db->next_record();
        if (!$db->Record["login"]) {
            $msg->raise("ERROR", "action", _("Login corresponding to $cuid not found"));
            return false;
        }
        $uidlogin = $cuid . "-" . $db->Record["login"];

        //The path will look like /<archive_del_data>/YYYY-MM/<uid>-<login>/<folder>
        $today = getdate();
        $dest = $BACKUP_DIR . '/' . $today["year"] . '-' . $today["mon"] . '/' . $uidlogin . '/' . $dir;
        $this->move($archive, $dest);
        return true;
    }


    /**
     * function inserting the action in the sql table
     * 
     * @global m_mysql $db
     * @global m_messages $msg
     * @param string $type
     * @param string|integer $user wich user do we impersonate?
     * @param mixed $parameters
     * @return boolean
     */
    function set($type, $user, $parameters) {
        global $db, $msg;
        $msg->log("action", "set", $type);
        $serialized = serialize($parameters);
        $type = strtoupper($type);
        if (in_array($type, array('CHMOD',
        'CREATE_FILE', 
        'CREATE_DIR', 
        'MOVE', 
        'FIX_USER', 
        'FIX_FILE', 
        'FIX_DIR', 
        'DELETE'))) {
            $query = "INSERT INTO `actions` (type, parameters, creation, user) VALUES('$type', '$serialized', now(), '$user');";
        } else {
            return False;
        }

        if (!$db->query($query)) {
            $msg->raise("ERROR", "action", _("Error setting actions"));
            return false;
        }
        return $this->do_action();
    }


    /**
     * This seems to be unused ?
     * 
     * @global m_messages $msg
     * @global m_mysql $db
     * @return boolean
     */
    function get_old() {
        global $msg, $db;

        $purge = "select * from actions where TO_DAYS(curdate()) - TO_DAYS(creation) > 2;";
        $result = $db->query($purge);
        if (!$result) {
            $msg->raise("ERROR", "action", _("Error selecting  old actions"));
            return false;
        }
        return $db->num_rows($result);
    }


    /**
     * 
     * @global m_messages $msg
     * @global m_mysql $db
     * @param type $all
     * @return boolean
     */
    function purge($all = null) {
        global $msg, $db;
        if (is_null($all)) {
            $purge = "delete from actions where TO_DAYS(curdate()) - TO_DAYS(creation) > 2 and status = 0;";
        } else {
            $purge = "delete from actions where TO_DAYS(curdate()) - TO_DAYS(creation) > 2;";
        }
        $result = $db->query($purge);
        if (!$result) {
            $msg->raise("ERROR", "action", _("Error purging old actions"));
            return false;
        }
        return $db->num_rows($result);
    }


    /**
     *  function returning the first not locked line of the action table 
     * 
     * @global m_mysql $db
     * @return boolean or array
     */
    function get_action() {
        global $db;

        $tab = array();
        $db->query('select * from actions where end = 0 and begin = 0 order by id limit 1;');
        if ($db->next_record()) {
            $tab[] = $db->Record;
            return $tab;
        } else {
            return false;
        }
    }


    /**
     * function locking an entry while it is being executed by the action script
     * 
     * @global m_mysql $db
     * @global m_messages $msg
     * @param int $id
     * @return boolean
     */
    function begin($id) {
        global $db, $msg;
        if (!$db->query("update actions set begin=now() where id= ? ;", array($id))) {
            $msg->raise("ERROR", "action", _("Error locking the action : $id"));
            return false;
        }
        return true;
    }


    /**
     *  function locking an entry while it is being executed by the action script
     * 
     * @global m_mysql $db
     * @global m_messages $msg
     * @param int $id
     * @param integer $return
     * @return boolean
     */
    function finish($id, $return = 0) {
        global $db, $msg;
        if (!$db->query("update actions set end=now(),status=? where id= ?;", array($return, $id))) {
            $msg->raise("ERROR", "action", _("Error unlocking the action : $id"));
            return false;
        }
        return true;
    }


    /**
     * 
     * @global m_mysql $db
     * @global m_messages $msg
     * @param int $id
     * @return boolean
     */
    function reset_job($id) {
        global $db, $msg;
        if (!$db->query("update actions set end=0,begin=0,status='' where id= ?;", array($id))) {
            $msg->raise("ERROR", "action", _("Error unlocking the action : $id"));
            return false;
        }
        return true;
    }


    /**
     * Returns a list of actions marked as executable and ready for execution
     * 
     * @global m_mysql $db
     * @global m_messages $msg
     * @return boolean 
     */
    function get_job() {
        global $db;
        $tab = array();
        $db->query("Select * from actions where begin !=0 and end = 0 ;");
        if ($db->next_record()) {
            $tab[] = $db->Record;
            return $tab;
        } else {
            return false;
        }
    }


    /**
     *  function locking an entry while it is being executed by the action script
     * 
     * @global m_mysql $db
     * @param int $id
     * @return boolean
     */
    function cancel($id) {
        $this->finish($id, 666);
        return true;
    }

} /* Class action */

