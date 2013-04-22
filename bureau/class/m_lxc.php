<?php

include_once(dirname(__FILE__) . '/vm.class.php');
# include('vm.php'); // This one fails ...

class m_lxc implements vm
{
  public $IP;
  public $PORT;
  public $TIMEOUT = 5;
  public $error = array();

  function m_lxc() {
    $this->IP   = variable_get('lxc_ip', '', "IP address of the Alternc's LXC server. If empty, no LXC server.");
    $this->PORT = variable_get('lxc_port', '6504', "Port of the Alternc's LXC server");
    $this->KEY  = variable_get('lxc_key', '', "Shared key with the Alternc's LXC server");
  }


  function hook_menu() {
    if ( empty($this->IP)) return ; # No menu if no server

    $obj = array(
      'title'       => _("Console access"),
      'ico'         => 'images/ssh.png',
      'link'        => 'vm.php',
      'pos'         => 95,
     ) ;

     return $obj;
  }

  function hook_admin_del_member() {
    global $db,$err,$cuid;
    $err->log("lxc","alternc_del_member");
    $db->query("DELETE FROM vm_history WHERE uid='$cuid'");
    return true;
  }


  private function sendMessage($params) {
    $fp = fsockopen($this->IP, $this->PORT, $errno, $errstr, $this->TIMEOUT);
    if (!$fp) 
    {
      $this->error[] = 'Unable to connect';
      return FALSE;
    }

    $msg = sprintf("%s\n", serialize($params) );
    if (fwrite ($fp, $msg) < 0)
    {
      $this->error[] = 'Unable to send data';
      return FALSE;
    }
    $resp = '';
    #while (($resp .= fgets($fp, 4096)) !== FALSE);
    $resp = fgets($fp, 4096);
    fclose ($fp);

    return $resp;
  
    if (stripos($resp, 'error') > 0)
    {
      $data = unserialize($resp);
      $this->error[] = $data['msg'];
      return FALSE;
    }
    else
    {
      return $resp;
    }
  }

  public function start($login = FALSE, $pass = FALSE, $uid = FALSE)
  {
    
    global $mem, $db, $err, $mysql;

    if ($this->getvm() !== FALSE)
    {
      $err->raise('lxc', _('VM already started'));
      return FALSE;
    }

    $login = $login ? $login : $mem->user['login'];
    $pass  = $pass  ? $pass  : $mem->user['pass'];
    $uid   = $uid   ? $uid   : $mem->user['uid'];

    $msgg = array('action'=>'start', 'login'=>$login, 'pass' => $pass, 'uid'=> $uid);  
    $msgg['mysql_host'] = $mysql->dbus->Host;

    $res = $this->sendMessage($msgg);
    if ($res === FALSE)
      return $this->error;
    else
    {
      $data = unserialize($res);
      $error = $data['error'];
      $hostname = $data['hostname'];
      $msg = $data['msg'];
      $date_start = 'NOW()';
      $uid = $mem->user['uid'];

      if ((int)$data['error'] != 0)
      {
        $err->raise('lxc', _($data['msg']));
        return FALSE;
      }

      $db->query("INSERT INTO vm_history (ip,date_start,uid,serialized_object) VALUES ('$hostname', $date_start, '$uid', '$res')");

      return $res;
    }
  }


  public function monit()
  {
    echo "1 / 5 used ";
  }

  public function getvm()
  {
    global $db, $mem;

    $uid = $mem->user['uid'];
    $res = array();

    $res = $db->query("SELECT * FROM vm_history WHERE date_end IS NULL AND uid= '$uid' ORDER BY id DESC LIMIT 1");

    if ($db->next_record())
    {
      $db->Record['serialized_object'] = unserialize($db->Record['serialized_object']);
      return $db->Record;
    }
    else
      return FALSE;
  }

  public function stop()
  {
    global $db, $mem;

    $vm = $this->getvm();

    if ($vm === FALSE)
      return TRUE;

    $vm_id = $vm['serialized_object']['vm'];
    $uid = $mem->user['uid'];
    $vid = $vm['id'];

    if ($this->sendMessage(array('action' => 'stop', 'vm' => $vm_id)) === FALSE)
      return FALSE;

    return $db->query("UPDATE vm_history SET date_end = NOW() WHERE uid = '$uid' AND id = '$vid' LIMIT 1");
  }
}
