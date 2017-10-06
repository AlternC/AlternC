<?php
/*
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2000-2012 by the AlternC Development Team. 
 https://alternc.org/ 
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
 Purpose of file: Gestion des statistiques web par Awstats
 ----------------------------------------------------------------------
*/
/**
* This class manage awstats statistic sets.
* 
* Copyleft {@link http://alternc.org/ AlternC Team}
* 
* @copyright    AlternC-Team 2004-09-01 http://alternc.org/
* 
*/
class m_aws {


  /** Where are the awstats configuration files : 
   * @access private 
   */
  var $CONFDIR="/etc/awstats";
  var $HTAFILE="/etc/alternc/awstats.htpasswd";
  var $CACHEDIR="/var/cache/awstats";


  /** Where is the template for conf files :
   * @access private 
   */
  var $TEMPLATEFILE="/etc/alternc/templates/awstats/awstats.template.conf";


  /* ----------------------------------------------------------------- */
  /**
   * Constructor
   */
  function m_aws() {
  }

  function hook_menu() {
    $obj = array(
      'title'       => _("Web Statistics"),
      'ico'         => 'images/stat.png',
      'link'        => 'aws_list.php',
      'pos'         => 80,
     ) ;

     return $obj;
  }

  /* ----------------------------------------------------------------- */  
  /**
   * Password kind used in this class (hook for admin class)
   */
  function alternc_password_policy() {
    return array("aws"=>"Awstats Web Statistics");
  }


  /* ----------------------------------------------------------------- */
  /**
   * Name of the module function
   */
  function alternc_module_description() {
    return array("aws"=>_("The stats module allows any user to ask for statistics about his web site. Statistics are web pages generated daily based on the visits of the day before. Awstats is the soft used to produce those stats. The statistics are then protected by a login and a password."));
  } 


  /* ----------------------------------------------------------------- */
  /**
   * Returns an array with all the statistics of a member.
   *
   * @return array Returns an indexed array of associative arrays 
   * like that :
   *  $r[0-n]["id"] = Id of the stat set
   *  $r[0-n]["hostname"]= domain
   *  $r[0-n]["users"]= list of allowed users separated with ' '
   */
  function get_list() {
    global $db,$msg,$cuid;
    $msg->log("aws","get_list");
    $r=array();
    $db->query("SELECT id, hostname FROM aws WHERE uid='$cuid' ORDER BY hostname;");
    if ($db->num_rows()) {
      while ($db->next_record()) {
	$r[]=array(
		   "id"=>$db->f("id"),
		   "hostname"=>$db->f("hostname")
		   );
      }
      $t=array();
      foreach ($r as $v) {
	$db->query("SELECT login FROM aws_access WHERE id='".$v["id"]."';");
	$u="";
	while ($db->next_record()) {
		$u.=$db->f("login")." ";
	}
	$t[]=array(
        "id"=>$v["id"],
        "hostname"=>$v["hostname"],
        "users"=>$u
    );
      }
      return $t;
    } else {
      return false;
    }
  }


  /* ----------------------------------------------------------------- */
  /**
   * Return an array with the details for 1 statistic set 
   *
   * @param integer $id ID of the set we want.
   * @return array Returns an associative array as follow : 
   *  $r["id"] = Id
   *  $r["hostname"]= domain
   *  $r["users"] = List of allowed users, separated by ' '
   */
  function get_stats_details($id) {
    global $db,$msg,$cuid;
    $msg->log("aws","get_stats_details",$id);
    $db->query("SELECT id, hostname, hostaliases, public FROM aws WHERE uid='$cuid' AND id='$id';");
    if ($db->num_rows()) {
      $db->next_record();
      $id=$db->f("id");
      $hostname=$db->f("hostname");
      $hostaliases=$db->f("hostaliases");
      $public=$db->f("public");
      $db->query("SELECT login FROM aws_access WHERE id='$id';");
      $u="";
      while ($db->next_record()) {
	$u.=$db->f("login")." ";
      }
      return array(
		"id"=>$id,
		"hostname"=>$hostname,
		"users"=>$u,
                "hostaliases"=>$hostaliases,
                "public"=>$public
		   );
    } else {
      $msg->raise('Error', "aws",_("This statistic does not exist"));
      return false;
    }
  }


/* ----------------------------------------------------------------- */
  /** Return the list of domains / subdomains allowed for this member with the type (MX,URL,...)
   * 
   * @return array an array of allowed domains / subdomains.
   */
  function host_list() {
    global $db,$msg,$cuid;
    $r=array();
    $db->query("SELECT sd.domaine, sd.sub, dt.name, dt.description FROM sub_domaines sd, domaines_type dt WHERE compte='$cuid' AND lower(sd.type) = lower(dt.name) AND dt.only_dns = false ORDER BY domaine,sub;");
    while ($db->next_record()) {
      if ($db->f("sub")) {
        $r[]=array(
            "hostname"=>$db->f("sub").".".$db->f("domaine"),
            "type"=>$db->f("name"),
            "desc"=>$db->f("description")
        );
      } else {
        $r[]=array(
            "hostname"=>$db->f("domaine"),
            "type"=>$db->f("name"),
            "desc"=>$db->f("description")
        );
      }
    }
    return $r;
  }


  /* ----------------------------------------------------------------- */
  /** Returns the list of prefixes that can be used on current account
   * @return array an arry with the list of domains + the login of the account
   */
  function prefix_list() {
    global $db,$mem,$cuid;
    $r=array();
    $r[]=$mem->user["login"];
    $db->query("SELECT domaine FROM domaines WHERE compte='$cuid' ORDER BY domaine;");
    while ($db->next_record()) {
      $r[]=$db->f("domaine");
    }
    return $r;
  }


  /* ----------------------------------------------------------------- */
  /** Echoes <option> tags of all the domains hosted on the account + the login of the account
   * They can be used as a root for the login that may have access to web statistics
   * hosted on an account
   * $current will be the selected value.
   * @param string $current The default selected value
   * @return boolean TRUE.
   */
  function select_prefix_list($current) {
    $r=$this->prefix_list();
    reset($r);
    while (list($key,$val)=each($r)) {
      if ($current==$val) $c=" selected=\"selected\""; else $c="";
      echo "<option$c>$val</option>";
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** 
   * Draw options for a select html code with the list of allowed and availables domains
   * for this member.
   */
  function select_host_list($current) {
    $r=$this->host_list();
    reset($r);
    while (list($key,$val)=each($r)) {
      $ho=$val["hostname"];
      $ty=$val["desc"];
      if ($current==$ho) $c=" selected=\"selected\""; else $c="";
      if ($this->check_host_available($ho)) echo "<option value=\"$ho\"$c>$ho ("._($ty).")</option>";
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** 
   * Check if hosts is already used by awstats
   * of available for this member.
   */
  function check_host_available($current) {
    global $msg;
    $msg->log("aws","check_host_available",$current);
    $r=$this->get_list();
    if(is_array($r)){
        reset($r);
        while (list($key,$val)=each($r)) {
          if ($current==$val["hostname"]) {
              $msg->raise('Alert', "aws",_("Host already managed by awstats!"));
              return false;
          }
        }
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** 
   * Return the hostaliases list with an id.
   */
  function get_hostaliases($id) {
    global $db,$msg,$cuid;
    $r=array();
    if ($id == NULL)  
      return $r; 
    $db->query("SELECT hostaliases FROM aws WHERE uid='$cuid' and id='$id' ORDER by hostaliases;");
    while ($db->next_record()) {
      $r[]=$db->f("hostaliases");
    }
    
    return $r;
  }


  /* ----------------------------------------------------------------- */
  /** 
   * Edit a statistic set (change allowed user list)
   * @param integer $id the stat number we change
   * @param array $users the list of allowed users
   */
  function put_stats_details($id,$users,$hostaliases,$public) {
    global $msg,$db,$cuid;
    if ($this->get_stats_details($id)) {
      $this->delete_allowed_login($id, 1);
      if (is_array($users)) {
        foreach($users as $v) {
          $this->allow_login($v,$id,1);
	}
      }
      $db->query("UPDATE aws SET hostaliases='$hostaliases', public='$public' where id='$id';");
      $this->_createconf($id);
      $this->_createhtpasswd();
      return true;
    } else {
      return false;
    }
  }


  /* ----------------------------------------------------------------- */
  /** 
   * Delete a statistic set.
   * @param integer $id The statistic set ID
   * @return string the domain name of the deleted statistic set, or FALSE if an error occurred
   */
  function delete_stats($id) {
    global $db,$msg,$cuid,$action;
    $msg->log("aws","delete_stats",$id);
    $db->query("SELECT hostname FROM aws WHERE id='$id' and uid='$cuid';");
    if (!$db->num_rows()) {
      $msg->raise('Error', "aws",_("This statistic does not exist"));
      return false;
    }
    $db->next_record();
    $hostname=$db->f("hostname");
    $this->delete_allowed_login($id,1);
    $this->_delconf($hostname);
    $db->query("DELETE FROM aws WHERE id='$id'");
    $action->del($this->CACHEDIR. DIRECTORY_SEPARATOR . $hostname . DIRECTORY_SEPARATOR);
    return $hostname;

  }


  /* ----------------------------------------------------------------- */
  /** 
   * Create a new statistic set
   * @param string $hostname The domain name
   * @param $uers array the array of users that will be allowed
   * @param $hostaliases array an array of host aliases
   * @param $public boolean Shall this statistic set be public ? 
   * @return boolean TRUE if the set has been created
   */
  function add_stats($hostname,$users="", $hostaliases,$public) {
    global $db,$msg,$quota,$mem,$cuid;
    $msg->log("aws","add_stats",$hostname);
    $ha="";
    $r=$this->host_list();
    $hosts=array();
    foreach ($r as $key=>$val) {
        $hosts[]=$val["hostname"];
    }
    reset($hosts);
    if (!in_array($hostname,$hosts) || $hostname=="") {
      $msg->raise('Error', "aws",_("This hostname does not exist (Domain name)"));
      return false;
    }

    // Parse the hostaliases array (it should contains valid domains)
	if (is_array($hostaliases)) {
		foreach($hostaliases as $ho) {
			if (!in_array($ho,$hosts) || $hostname=="") {
				$msg->raise('Error', "aws",_("This hostname does not exist (Hostaliases)"));
				return false;
			}
			$ha .= "$ho ";
		}
    }

    if ($quota->cancreate("aws")) {
      $db->query("INSERT INTO aws (hostname,uid,hostaliases,public) VALUES ('$hostname','$cuid','$ha','$public')");
      $id=$db->lastid();
      if (is_array($users)) {
        foreach($users as $v) {
          $this->allow_login($v,$id, 1);
        }
      }
      if (!$this->_createconf($id) ) return false;
      if (!$this->_createhtpasswd() ) return false;
      mkdir($this->CACHEDIR."/".$hostname,0777);
      return true;
    } else {
      $msg->raise('Alert', "aws",_("Your stat quota is over..."));
      return false;
    }
  }


  /* ----------------------------------------------------------------- */
  function list_login() {
    global $db,$msg,$cuid;
    $msg->log("aws","list_login");
    $db->query("SELECT login FROM aws_users WHERE uid='$cuid';");
    $res=array();
    if (!$db->next_record()) {
	$msg->raise('Info', "aws",_("No user currently defined"));
      return false;
    }
    do { 
      $res[]=$db->f("login");
    } while ($db->next_record());
    return $res;
  }


  /* ----------------------------------------------------------------- */
  function list_allowed_login($id) {
    global $db,$msg,$cuid;
    $msg->log("aws","list_allowed_login");
    $db->query("SELECT u.login,a.id FROM aws_users u LEFT JOIN aws_access a ON a.id='$id' AND a.login=u.login WHERE u.uid='$cuid';");
    $res=array();
    if (!$db->next_record()) {
      return false;
    }
    do { 
      $res[]=array("login"=>$db->f("login"),"selected"=>($db->f("id")));
    } while ($db->next_record());
    return $res;
  }

  /* ----------------------------------------------------------------- */
  function get_view_public($id) {
    global $db,$msg,$cuid;
    $db->query("SELECT public FROM aws WHERE id='$id' and uid='$cuid';");
    if ($db->num_rows()) {
      $db->next_record();
      $pub=$db->f("public");
    } else {
	  $pub=1;
	}
    return $pub;
  }


  /* ----------------------------------------------------------------- */
  /* Check that a login exists ($exists=1) or doesn't exist ($exists=0) */
  function login_exists($login,$exists=1) {
    global $db,$msg,$cuid;
    $msg->log("aws","list_login");
    $db->query("SELECT login FROM aws_users WHERE uid='$cuid' AND login='$login';");
    if (!$db->next_record()) {
      return ($exists==0);
    } else {
      return ($exists==1);
    }
  }


  /* ----------------------------------------------------------------- */
  function del_login($login) {
    global $db,$msg,$cuid;
    $msg->log("aws","del_login");
    if (!$this->login_exists($login,1)) {
      $msg->raise('Error', "aws",_("Login does not exist")); 
      return false;
    }
    $db->query("DELETE FROM aws_users WHERE uid='$cuid' AND login='$login';");
    $db->query("DELETE FROM aws_access WHERE uid='$cuid' AND login='$login';");
    $this->_createhtpasswd();
    return true;
  }


  /* ----------------------------------------------------------------- */
  function add_login($login,$pass) {
    global $db,$msg,$cuid,$admin;
    $msg->log("aws","add_login");

    if (!($login=$this->_check($login))) {
      return false;
    }
    if ($this->login_exists($login,1)) {
      $msg->raise('Error', "aws",_("Login already exist")); 
      return false;
    }
    // Check this password against the password policy using common API :
    if (is_callable(array($admin, "checkPolicy"))) {
      if (!$admin->checkPolicy("aws", $login, $pass)) {
	return false; // The error has been raised by checkPolicy()
      }
    }
    $pass=$this->crypt_apr1_md5($pass);
    // FIXME retourner une erreur l'insert se passe pas bien
    $db->query("INSERT INTO aws_users (uid,login,pass) VALUES ('$cuid','$login','$pass');");
    return $this->_createhtpasswd();
  }


  /* ----------------------------------------------------------------- */
  function change_pass($login,$pass) {
    global $db,$msg,$cuid,$admin;
    $msg->log("aws","change_pass");

    if (!($login=$this->_check($login))) {
      $msg->raise('Error', "aws",_("Login incorrect")); // Login incorrect 
      return false;
    }
    if (!($this->login_exists($login))) {
      $msg->raise('Error', "aws",_("Login does not exists")); // Login does not exists
      return false;
    }
    // Check this password against the password policy using common API :
    if (is_callable(array($admin, "checkPolicy"))) {
      if (!$admin->checkPolicy("aws", $login, $pass)) {
        return false; // The error has been raised by checkPolicy()
      }
    }
    $pass=$this->crypt_apr1_md5($pass);
    $db->query("UPDATE aws_users SET pass='$pass' WHERE login='$login';");
    return $this->_createhtpasswd();
  }


  /* ----------------------------------------------------------------- */
  function allow_login($login,$id,$noconf=0) { // allow user $login to access stats $id.
    global $db,$msg,$cuid;
    $msg->log("aws","allow_login");

    if (!($login=$this->_check($login))) {
      $msg->raise('Error', "aws",_("Login incorrect"));
      return false;      
    }
    if (!$this->login_exists($login)) {
      $msg->raise('Error', "aws",_("Login does not exist"));
      return false;
    }
    $db->query("SELECT id FROM aws WHERE id='$id' AND uid='$cuid'");
    if (!$db->next_record()) {
      $msg->raise('Error', "aws",_("The requested statistic does not exist.")); 
      return false;
    }
    $db->query("SELECT login FROM aws_access WHERE id='$id' AND login='$login'");
    if ($db->next_record()) {
      $msg->raise('Error', "aws",_("This login is already allowed for this statistics."));
      return false;
    }
    $db->query("INSERT INTO aws_access (uid,id,login) VALUES ('$cuid','$id','$login');");
    if (!$noconf) { 
      $this->_createconf($id); 
      $this->_createhtpasswd();
    }
    return true;
  }


  /* ----------------------------------------------------------------- */

  /**
   * @param integer $id
   */
  function delete_allowed_login($id,$noconf=0) {
    global $db,$msg,$cuid;
    $msg->log("aws","delete_allowed_login");

    $db->query("SELECT id FROM aws WHERE id='$id' AND uid='$cuid'");
    if (!$db->next_record()) {
      $msg->raise('Error', "aws",_("The requested statistic does not exist.")); 
      return false;
    }
    $db->query("DELETE FROM aws_access WHERE id='$id';");
    if (!$noconf) { 
      $this->_createconf($id); 
      $this->_createhtpasswd();
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  function deny_login($login,$id,$noconf=0) { // deny user $login to access stats $id.
    global $db,$msg,$cuid;
    $msg->log("aws","deny_login");

    if (!($login=$this->_check($login))) {
      $msg->raise('Error', "aws",_("Login incorrect")); // Login incorrect 
      return false;
    }
    if (!$this->login_exists($login,0)) {
      $msg->raise('Error', "aws",_("Login does not exists")); // Login does not exists
      return false;
    }
    $db->query("SELECT id FROM aws WHERE id='$id' AND uid='$cuid'");
    if (!$db->next_record()) {
      $msg->raise('Error', "aws",_("The requested statistic does not exist."));
      return false;
    }
    $db->query("SELECT login FROM aws_access WHERE id='$id' AND login='$login'");
    if (!$db->next_record()) {
      $msg->raise('Error', "aws",_("This login is already denied for this statistics."));
      return false;
    }
    $db->query("DELETE FROM aws_access WHERE id='$id' AND login='$login';");
    if (!$noconf) { 
      $this->_createconf($id); 
      $this->_createhtpasswd();
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  function alternc_del_member() {
    global $db,$msg,$cuid;
    $msg->log("aws","del_member");
    $db->query("SELECT * FROM aws WHERE uid='$cuid';");
    $t=array();
    while ($db->next_record()) {
      $t[]=$db->f("hostname");
    }
    $db->query("DELETE FROM aws WHERE uid='$cuid';");
    foreach ($t as $i) {
      $this->_delconf($i);
    }
    $db->query("DELETE FROM aws_access WHERE uid='$cuid'");
    $db->query("DELETE FROM aws_users WHERE uid='$cuid';");
    return $this->_createhtpasswd();
  }


  /* ----------------------------------------------------------------- */
  /** 
   * This function is called on each class when a domain name is uninstalled
   * @param string $dom the domain to uninstall
   */
  function alternc_del_domain($dom) {
    global $msg,$cuid;
    $msg->log("aws","alternc_del_domain",$dom);
    $db=new DB_System();
    $db->query("SELECT id,hostname FROM aws WHERE uid='$cuid' AND (hostname='$dom' OR hostname like '%.$dom')");
    $t=array();
    while ($db->next_record()) {
      $t[]=array($db->f("hostname"),$db->f("id"));
    }
    foreach ($t as $i) {
      $db->query("DELETE FROM aws WHERE uid='$cuid' AND hostname='".$i[0]."';");
      $db->query("DELETE FROM aws_access WHERE uid='$cuid' AND id='".$i[1]."';");
      $this->_delconf($i[0]);
    }
    return $this->_createhtpasswd();
  }


  /* ----------------------------------------------------------------- */
  /** 
   * This function is called when we are asked to compute the used quota
   * for a service
   */
  function hook_quota_get() {
    global $db,$msg,$cuid;
    $msg->log("aws","get_quota");
    $db->query("SELECT COUNT(*) AS cnt FROM aws WHERE uid='$cuid'");
    $q=Array("name"=>"aws", "description"=>_("Awstats"), "used"=>0);
    if ($db->next_record()) {
      $q['used']=$db->f("cnt");
    }
    return $q; 
  }


  /* ----------------------------------------------------------------- */
  function _check($login) {
    global $msg,$mem;
    $login=trim($login); 
    $login=strtolower($login); 
    if ($c=strpos($login,"_")) {
	$prefix=substr($login,0,$c);
	$postfix=substr($login,$c+1);
    } else {
	$prefix=$login;
	$postfix="";
    }
    $r=$this->prefix_list();
    if (!in_array($prefix,$r)) { 
      $msg->raise('Error', "aws",_("prefix not allowed.")); // prefix not allowed. 
      return false;
    } 
   if (!preg_match('/^[0-9a-z_-]*$/', $postfix)){
      $msg->raise('Error', "aws", _("There is some forbidden characters in the login (only A-Z 0-9 _ and - are allowed)"));
      return false;
    }
    return $login;
  }


  /* ----------------------------------------------------------------- */
  /** Delete the awstats configuration file for a statistic set.
   * @access private
   */
  function _delconf($hostname) {
    global $msg,$action;
    if (!preg_match('/^[._a-z0-9-]*$/', $hostname)){
      $msg->raise('Error', "aws",_("Hostname is incorrect")); 
      return false;
    }
    $action->del($this->CONFDIR. DIRECTORY_SEPARATOR . "awstats.".$hostname.".conf");
  }


  /* ----------------------------------------------------------------- */
  /** Create a configuration file for a statistic set.
   * if nochk==1, does not check the owner of the stat set (for admin only)
   * @access private
   */
  function _createconf($id,$nochk=0) {
    global $db,$msg,$cuid,$L_ALTERNC_LOGS;
    $s=@implode("",file($this->TEMPLATEFILE));
    if (!$s) {
      $msg->raise('Error', "aws",_("Problem to create the configuration"));
      return false;
    }
    if ($nochk) {
        $db->query("SELECT * FROM aws WHERE id='$id';");
    } else { 
        $db->query("SELECT * FROM aws WHERE id='$id' AND uid='$cuid';");
    }
    if (!$db->num_rows()) {
      $msg->raise('Error', "aws",_("This statistic does not exist")); 
      return false;
    }
    $db->next_record();
    $uid = $db->f('uid');
    $hostname=$db->f("hostname");
    $hostaliases=$db->f("hostaliases");
    $public=$db->f("public");
    $db->query("SELECT login FROM membres WHERE uid = '$uid'");
    $db->next_record();
    $username = $db->f('login');
    $db->query("SELECT login FROM aws_access WHERE id='$id';");
    $users="";
    while ($db->next_record()) {
        $users.=$db->f("login")." ";
    }

    $replace_vars = array(
        '%%UID%%' => $uid,
        '%%USER%%' => $username,
        '%%ALTERNC_LOGS%%' => $L_ALTERNC_LOGS,
        '%%PUBLIC%%' => $public,
        '%%HOSTNAME%%' => $hostname,
        '%%HOSTALIASES%%' => $hostaliases,
        '%%USERS%%' => $users,
    );
    foreach ($replace_vars as $k=>$v){
        $s=str_replace($k,$v,$s);
    }

    $f=fopen($this->CONFDIR."/awstats.".$hostname.".conf","wb");
    fputs($f,$s,strlen($s));
    fclose($f);

    return true;
  }


  /* ----------------------------------------------------------------- */
  function _createhtpasswd() {
    global $db, $msg;
    $f=@fopen($this->HTAFILE,"wb");
    if ($f) {
      $db->query("SELECT login,pass FROM aws_users;");
      while ($db->next_record()) {
        fputs($f,$db->f("login").":".$db->f("pass")."\n");
      }
      fclose($f);
      return true;
    } else {
      $msg->raise('Error', "aws", _("Problem to edit file %s"), $this->HTAFILE);
      return false;
    }
  }


  /* ----------------------------------------------------------------- */
  /**
   * Exports all the aws related information for an account.
   * @access private
   * EXPERIMENTAL 'sid' function ;) 
   */
  function alternc_export() {
    global $db,$msg,$cuid;
    $msg->log("aws","export");
    $str="<aws>\n";
    $db->query("SELECT login,pass FROM aws_users WHERE uid='$cuid';");
    while ($db->next_record()) {
      $str.="  <user><login>".$db->Record["login"]."</login><pass hash=\"des\">".$db->Record["pass"]."</pass></user>\n";
    }
    $r=array();
    $db->query("SELECT id, hostname FROM aws WHERE uid='$cuid' ORDER BY hostname;");
    while ($db->next_record()) {
      $r[$db->Record["id"]]=$db->Record["hostname"];
    }
    foreach($r as $id=>$host) {
      $str.="  <domain>\n    <name>".$host."</name>\n";
      $db->query("SELECT login FROM aws_access WHERE id='$id';");
      while ($db->next_record()) {
        $str.="    <user>".$db->Record["login"]."</user>\n";
      }
      $str.="  </domain>\n";
    }
    $str.="</aws>\n";
    return $str;
  }


  /* ----------------------------------------------------------------- */
  /**
   * from http://php.net/crypt#73619 
   */
  function crypt_apr1_md5($plainpasswd) {
    $salt = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8);
    $len = strlen($plainpasswd);
    $text = $plainpasswd.'$apr1$'.$salt;
    $bin = pack("H32", md5($plainpasswd.$salt.$plainpasswd));
    for($i = $len; $i > 0; $i -= 16) { $text .= substr($bin, 0, min(16, $i)); }
    for($i = $len; $i > 0; $i >>= 1) { $text .= ($i & 1) ? chr(0) : $plainpasswd{0}; }
    $bin = pack("H32", md5($text));
    for($i = 0; $i < 1000; $i++) {
      $new = ($i & 1) ? $plainpasswd : $bin;
      if ($i % 3) $new .= $salt;
      if ($i % 7) $new .= $plainpasswd;
      $new .= ($i & 1) ? $bin : $plainpasswd;
      $bin = pack("H32", md5($new));
    }
    for ($i = 0; $i < 5; $i++) {
      $k = $i + 6;
      $j = $i + 12;
      if ($j == 16) $j = 5;
      $tmp = $bin[$i].$bin[$k].$bin[$j].$tmp;
    }
    $tmp = chr(0).chr(0).$bin[11].$tmp;
    $tmp = strtr(strrev(substr(base64_encode($tmp), 2)),
		 "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
		 "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz");
    return "$"."apr1"."$".$salt."$".$tmp;
  }




} /* CLASSE m_aws */

?>
