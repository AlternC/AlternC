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
    global $db,$err,$cuid;
    $err->log("aws","get_list");
    $r=array();
    $db->query("SELECT id, hostname FROM aws WHERE uid='$cuid' ORDER BY hostname;");
    if ($db->num_rows()) {
      while ($db->next_record()) {
	$r[]=array(
		   "id"=>$db->f("id"),
		   "hostname"=>$db->f("hostname"),
		   );
      }
      $t=array();
      foreach ($r as $v) {
	$db->query("SELECT login FROM aws_access WHERE id='".$v["id"]."';");
	$u="";
	while ($db->next_record()) {
		$u.=$db->f("login")." ";
	}
	$t[]=array("id"=>$v["id"],"hostname"=>$v["hostname"],"users"=>$u);
      }
      return $t;
    } else {
      $err->raise("aws",1); // No statistics currently defined
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
    global $db,$err,$cuid;
    $err->log("aws","get_stats_details",$id);
    $r=array();
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
      $err->raise("aws",2); // This statistic does not exist
      return false;
    }
  }


  /* ----------------------------------------------------------------- */
  /** Return the list of domains / subdomains allowed for this member
   * 
   * @return array an array of allowed domains / subdomains.
   */
  function host_list() {
    global $db,$err,$cuid;
    $r=array();
    $db->query("SELECT domaine,sub FROM sub_domaines WHERE compte='$cuid' ORDER BY domaine,sub;");
    while ($db->next_record()) {
      if ($db->f("sub")) {
	$r[]=$db->f("sub").".".$db->f("domaine");
      } else {
	$r[]=$db->f("domaine");
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
   * Draw options for a select html code with the list of allowed domains
   * for this member.
   */
  function select_host_list($current) {
    $r=$this->host_list();
    reset($r);
    while (list($key,$val)=each($r)) {
      if ($current==$val) $c=" selected=\"selected\""; else $c="";
      echo "<option$c>$val</option>";
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** 
   * Return the hostaliases list with an id.
   */
  function get_hostaliases($id) {
    global $db,$err,$cuid;
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
    global $err,$db,$cuid;
    if ($c=$this->get_stats_details($id)) {
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
    } else return false;
  }


  /* ----------------------------------------------------------------- */
  /** 
   * Delete a statistic set.
   * @param integer $id The statistic set ID
   * @return string the domain name of the deleted statistic set, or FALSE if an error occurred
   */
  function delete_stats($id) {
    global $db,$err,$cuid;
    $err->log("aws","delete_stats",$id);
    $db->query("SELECT hostname FROM aws WHERE id='$id' and uid='$cuid';");
    if (!$db->num_rows()) {
      $err->raise("aws",2); // This statistic does not exist
      return false;
    }
    $db->next_record();
    $hostname=$db->f("hostname");
    $this->delete_allowed_login($id,1);
    $this->_delconf($hostname);
    $db->query("DELETE FROM aws WHERE id='$id'");
    system("rm ".$this->CACHEDIR."/$hostname/ -rf");
    return $name;
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
    global $db,$err,$quota,$mem,$cuid;
    $err->log("aws","add_stats",$hostname);
    $r=$this->host_list();
    if (!in_array($hostname,$r) || $hostname=="") {
      $err->raise("aws",3); // This hostname does not exist
      return false;
    }
    // Parse the hostaliases array (it should contains valid domains)
	if (is_array($hostaliases)) {
		foreach($hostaliases as $ho) {
			if (!in_array($hostname,$r) || $hostname=="") {
				$err->raise("aws",3); // This hostname does not exist
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
      $this->_createconf($id);
      $this->_createhtpasswd();
      mkdir($this->CACHEDIR."/".$hostname,0777);
      return true;
    } else {
      $err->raise("aws",4); // Your stat quota is over...
      return false;
    }
  }


  /* ----------------------------------------------------------------- */
  function list_login() {
    global $db,$err,$cuid;
    $err->log("aws","list_login");
    $db->query("SELECT login FROM aws_users WHERE uid='$cuid';");
    $res=array();
    if (!$db->next_record()) {
	$err->raise("aws",13); // No user currently defined
      return false;
    }
    do { 
      $res[]=$db->f("login");
    } while ($db->next_record());
    return $res;
  }


  /* ----------------------------------------------------------------- */
  function list_allowed_login($id) {
    global $db,$err,$cuid;
    $err->log("aws","list_allowed_login");
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
    global $db,$err,$cuid;
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
    global $db,$err,$cuid;
    $err->log("aws","list_login");
    $db->query("SELECT login FROM aws_users WHERE uid='$cuid' AND login='$login';");
    if (!$db->next_record()) {
      return ($exists==0);
    } else {
      return ($exists==1);
    }
  }


  /* ----------------------------------------------------------------- */
  function del_login($login) {
    global $db,$err,$cuid;
    $err->log("aws","del_login");
    if (!$this->login_exists($login,1)) {
      $err->raise("aws",5); // Login does not exists
      return false;
    }
    $db->query("DELETE FROM aws_users WHERE uid='$cuid' AND login='$login';");
    $db->query("DELETE FROM aws_access WHERE uid='$cuid' AND login='$login';");
    $this->_createhtpasswd();
    return true;
  }


  /* ----------------------------------------------------------------- */
  function add_login($login,$pass) {
    global $db,$err,$cuid;
    $err->log("aws","add_login");

    if (!($login=$this->_check($login))) {
      $err->raise("aws",6); // Login incorrect 
      return false;
    }
    if (!($this->login_exists($login,0))) {
      $err->raise("aws",7); // Login does not exist
      return false;
    }
    $pass=crypt($pass);
    $db->query("INSERT INTO aws_users (uid,login,pass) VALUES ('$cuid','$login','$pass');");
    $this->_createhtpasswd();
    return true;
  }


  /* ----------------------------------------------------------------- */
  function change_pass($login,$pass) {
    global $db,$err,$cuid;
    $err->log("aws","change_pass");

    if (!($login=$this->_check($login))) {
      $err->raise("aws",6); // Login incorrect 
      return false;
    }
    if (!($this->login_exists($login))) {
      $err->raise("aws",5); // Login does not exists
      return false;
    }
    $pass=crypt($pass);
    $db->query("UPDATE aws_users SET pass='$pass' WHERE login='$login';");
    $this->_createhtpasswd();
    return true;
  }


  /* ----------------------------------------------------------------- */
  function allow_login($login,$id,$noconf=0) { // allow user $login to access stats $id.
    global $db,$err,$cuid;
    $err->log("aws","allow_login");

    if (!($login=$this->_check($login))) {
      $err->raise("aws",6); // Login incorrect 
      return false;      
    }
    if (!$this->login_exists($login)) {
      $err->raise("aws",5); // Login does not exists
      return false;
    }
    $db->query("SELECT id FROM aws WHERE id='$id' AND uid='$cuid'");
    if (!$db->next_record()) {
      $err->raise("aws",2); // The requested statistic does not exist.
      return false;
    }
    $db->query("SELECT login FROM aws_access WHERE id='$id' AND login='$login'");
    if ($db->next_record()) {
      $err->raise("aws",8); // This login is already allowed for this statistics.
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
  function delete_allowed_login($id,$noconf=0) {
    global $db,$err,$cuid;
    $err->log("aws","delete_allowed_login");

    $db->query("SELECT id FROM aws WHERE id='$id' AND uid='$cuid'");
    if (!$db->next_record()) {
      $err->raise("aws",2); // The requested statistic does not exist.
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
    global $db,$err,$cuid;
    $err->log("aws","deny_login");

    if (!($login=$this->_check($login))) {
      $err->raise("aws",6); // Login incorrect 
      return false;
    }
    if (!$this->login_exists($login,0)) {
      $err->raise("aws",5); // Login does not exists
      return false;
    }
    $db->query("SELECT id FROM aws WHERE id='$id' AND uid='$cuid'");
    if (!$db->next_record()) {
      $err->raise("aws",2); // The requested statistic does not exist.
      return false;
    }
    $db->query("SELECT login FROM aws_access WHERE id='$id' AND login='$login'");
    if (!$db->next_record()) {
      $err->raise("aws",9); // This login is already denied for this statistics.
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
    global $db,$err,$cuid;
    $err->log("aws","del_member");
    $db->query("SELECT * FROM aws WHERE uid='$cuid';");
    $t=array();
    while ($db->next_record()) {
      $t[]=$db->f("hostname");
    }
    $db->query("DELETE FROM aws WHERE uid='$cuid';");
    foreach ($t as $i) {
      $this->_delconf($i);
    }
    $this->_createhtpasswd();
    $db->query("DELETE FROM aws_access WHERE uid='$cuid'");
    $db->query("DELETE FROM aws_users WHERE uid='$cuid';");
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** 
   * This function is called on each class when a domain name is uninstalled
   * @param string $dom the domain to uninstall
   */
  function alternc_del_domain($dom) {
    global $db,$err,$cuid;
    $err->log("aws","alternc_del_domain",$dom);
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
    $this->_createhtpasswd();
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** 
   * This function is called when we are asked to compute the used quota
   * for a service
   * @param integer $id The userid whose quota should be computed.
   */
  function hook_quota_get() {
    global $db,$err,$cuid;
    $err->log("aws","get_quota");
    $db->query("SELECT COUNT(*) AS cnt FROM aws WHERE uid='$cuid'");
    $q=Array("name"=>"aws", "description"=>_("Awstats"), "used"=>0);
    if ($db->next_record()) {
      $q['used']=$db->f("cnt");
    }
    return $q; 
  }


  /* ----------------------------------------------------------------- */
  function _check($login) {
    global $err,$mem;
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
      $err->raise("aws",10); // prefix not allowed. 
      return false;
    } 
   if (!preg_match('/^[0-9a-z_-]*$/', $postfix)){
      $err->raise("aws",11); // Forbidden caracters in the postfix.
      return false;
    }
    return $login;
  }


  /* ----------------------------------------------------------------- */
  /** Delete the awstats configuration file for a statistic set.
   * @access private
   */
  function _delconf($hostname) {
    global $err;
    if (!preg_match('/^[._a-z0-9-]*$/', $hostname)){
      $err->raise("aws",12); // Hostname is incorrect
      return false;
    }
    @unlink($this->CONFDIR."/awstats.".$hostname.".conf");
  }


  /* ----------------------------------------------------------------- */
  /** Create a configuration file for a statistic set.
   * if nochk==1, does not check the owner of the stat set (for admin only)
   * @access private
   */
  function _createconf($id,$nochk=0) {
    global $db,$err,$cuid;
    $s=implode("",file($this->TEMPLATEFILE));
    if ($nochk) {
        $db->query("SELECT * FROM aws WHERE id='$id';");
    } else { 
        $db->query("SELECT * FROM aws WHERE id='$id' AND uid='$cuid';");
    }
    if (!$db->num_rows()) {
      $err->raise("aws",2); // This statistic does not exist
      return false;
    }
    $db->next_record();
    $hostname=$db->f("hostname");
    $hostaliases=$db->f("hostaliases");
    $public=$db->f("public");
    $db->query("SELECT login FROM aws_access WHERE id='$id';");
    $users="";
    while ($db->next_record()) {
        $users.=$db->f("login")." ";
    }
    $s=str_replace("%%HOSTNAME%%",$hostname,$s);
    $s=str_replace("%%PUBLIC%%",$public,$s);
    $s=str_replace("%%HOSTALIASES%%",$hostaliases,$s);
    $s=str_replace("%%USERS%%",$users,$s);
    $f=fopen($this->CONFDIR."/awstats.".$hostname.".conf","wb");
    fputs($f,$s,strlen($s));
    fclose($f);
  }


  /* ----------------------------------------------------------------- */
  function _createhtpasswd() {
    global $db;
    $f=fopen($this->HTAFILE,"wb");
    if ($f) {
      $db->query("SELECT login,pass FROM aws_users;");
      while ($db->next_record()) {
        fputs($f,$db->f("login").":".$db->f("pass")."\n");
      }
      fclose($f);
    }
  }


  /* ----------------------------------------------------------------- */
  /**
   * Exports all the aws related information for an account.
   * @access private
   * EXPERIMENTAL 'sid' function ;) 
   */
  function alternc_export() {
    global $db,$err,$cuid;
    $err->log("aws","export");
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

} /* CLASSE m_aws */

?>
