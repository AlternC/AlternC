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
      'title'       => _("Virtual server"),
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


	private function sendMessage($action, $user, $password, $uid)
	{
		$fp = fsockopen($this->IP, $this->PORT, $errno, $errstr, $this->TIMEOUT);
		if (!$fp) 
		{
			$this->error[] = 'Unable to connect';
			return FALSE;
		}

		$msg = sprintf("%s|%s|%s|%d\n", $action, $user, $password, $uid);
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
		global $mem, $db;

		$user = $login ? $login : $mem->user['login'];
		$pass = $pass  ? $pass  : $mem->user['pass'];
		$uid  = $uid   ? $uid   : $mem->user['uid'];

		$res = $this->sendMessage('start', $user, $pass, $uid);
		if ($res === FALSE)
			return $this->error[0];
		else
		{
			$data = unserialize($res);
			$error = $data['error'];
			$hostname = $data['hostname'];
			$msg = $data['msg'];
			$date_start = 'NOW()';
			$uid = $mem->user['uid'];

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
		global $mem;
                printvar($mem);
		echo "lxc::stop";

	}
}
