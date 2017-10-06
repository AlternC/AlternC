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

include_once(dirname(__FILE__) . '/vm.class.php');

/**
 * 
 * Manage AlternC's virtual machine start/stop using our own inetd-based protocol.
 */
class m_lxc implements vm {

    public $IP;
    public $KEY;
    public $PORT;
    public $maxtime;
    public $TIMEOUT = 5;
    public $error = array();


    /**
     * Constructor, initialize the class informations from AlternC's variables
     */
    function m_lxc() {
        $this->IP = variable_get('lxc_ip', '', "IP address of the Alternc's LXC server. If empty, no LXC server.", array('desc' => 'IP address', 'type' => 'ip'));
        $this->PORT = variable_get('lxc_port', '6504', "Port of the Alternc's LXC server", array('desc' => 'Port', 'type' => 'integer'));
        $this->KEY = variable_get('lxc_key', '', "Shared key with the Alternc's LXC server", array('desc' => 'Shared key', 'type' => 'string'));
        $this->maxtime = variable_get('lxc_maxtime', '4', "How many hours do we allow to have a server before shutting it down", array('desc' => 'Max time', 'type' => 'integer'));
    }


    /**
     * HOOK: add the "Console Access" to AlternC's main menu
     */
    function hook_menu() {
        if (empty($this->IP))
            return; // No menu if no server

        $obj = array(
            'title' => _("Console access"),
            'ico' => 'images/ssh.png',
            'link' => 'vm.php',
            'pos' => 95,
        );

        return $obj;
    }


    /**
     * HOOK: remove VM history for AlternC account
     */
    function hook_admin_del_member() {
        global $db, $msg, $cuid;
        $msg->log("lxc", "alternc_del_member");
        $db->query("DELETE FROM vm_history WHERE uid= ?", array($cuid));
        return true;
    }


    /**
     * Send a message to a remote VM manager instance
     * $params are the parameters to send as serialized data
     * to the listening server. 
     * Return the unserialized response data, if the message has been sent successfully
     * or FALSE if an error occurred. In that case $error[] is set.
     */
    private function sendMessage($params) {
        global $L_FQDN, $hooks;
        $fp = @fsockopen($this->IP, $this->PORT, $errno, $errstr, $this->TIMEOUT);
        if (!$fp) {
            $this->error[] = 'Unable to connect';
            return FALSE;
        }
        // Authenticate:
        $params['server'] = $L_FQDN;
        $params['key'] = $this->KEY;
        // MySQL Host for this user ? 
        $moreparams = $hooks->invoke("lxc_params", array($params));
        foreach ($moreparams as $p) {
            foreach ($p as $k => $v) {
                $params[$k] = $v;
            }
        }

        $msg = serialize($params);
        if (fwrite($fp, $msg . "\n") < 0) {
            $this->error[] = 'Unable to send data';
            return FALSE;
        }
        $resp = fgets($fp, 8192);
        fclose($fp);

        $data = @unserialize($resp);

        if (isset($data['error']) && $data['error'] > 0) {
            $this->error[] = $data['msg'];
            return FALSE;
        } else {
            return $resp;
        }
    }


    /**
     * START a Virtual Machine on the remote VM manager
     * for user $login having hashed password $pass and uid $uid
     */
    public function start($login = FALSE, $pass = FALSE, $uid = FALSE) {
        global $mem, $db, $msg, $mysql;

        if ($this->getvm() !== FALSE) {
            $msg->raise("ERROR", 'lxc', _('VM already started'));
            return FALSE;
        }
        unset($this->error);

        $login = $login ? $login : $mem->user['login'];
        $pass = $pass ? $pass : $mem->user['pass'];
        $uid = $uid ? $uid : $mem->user['uid'];

        $msgg = array('action' => 'start', 'login' => $login, 'pass' => $pass, 'uid' => $uid);
        $msgg['mysql_host'] = $mysql->dbus->Host;

        $res = $this->sendMessage($msgg);
        if ($res === FALSE) {
            return $this->error;
        } else {
            $data = unserialize($res);
            $error = (int) $data['error'];
            $hostname = $data['hostname'];
            $msg = $data['msg'];
            $date_start = 'NOW()';
            $uid = $mem->user['uid'];

            if ($error != 0) {
                $msg->raise("ERROR", 'lxc', _($msg));
                return FALSE;
            }
            $db->query("INSERT INTO vm_history (ip,date_start,uid,serialized_object) VALUES (?, ?, ?, ?);", array($hostname, $date_start, $uid, $res));
            return $res;
        }
    }


    /**
     * 
     */
    public function getvm($login = FALSE) {
        global $mem;

        $login = $login ? $login : $mem->user['login'];
        $msgg = array('action' => 'get', 'login' => $login);
        $res = $this->sendMessage($msgg);
        if (!$res) {
            return FALSE;
        }
        return unserialize($res);
    }


    /**
     * Stop the currently running VM
     */
    public function stop() {
        $vm = $this->getvm();
        if ($vm === FALSE) {
            return FALSE;
        }
        if ($this->sendMessage(array('action' => 'stop', 'vm' => $vm['vm'])) === FALSE) {
            return FALSE;
        }
        return TRUE;
    }

} /* class m_lxc */

