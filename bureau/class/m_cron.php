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
 Purpose of file: Manage hook system.
 ----------------------------------------------------------------------
*/

/**
 * This class manage web-cron tasks
 */
class m_cron {

  const MAX_SOCKETS=8;
  const DEFAULT_CAFILE="/etc/ssl/certs/ca-certificates.crt";

  /*---------------------------------------------------------------------------*/
  /** Constructor
  */
  function m_cron() {
  }

  function schedule() {
    return Array(
		 Array('unit'=>1440, 'name'=>_("Daily")),
		 Array('unit'=>60,   'name'=>_("Hour")),
		 Array('unit'=>30,   'name'=>_("Half Hour")),
		 );
  }

  
  /*---------------------------------------------------------------------------*/
  /** List the crontab for the current user.
   * @return array an hash for each crontab.
   */
  function lst_cron() {
    global $cuid,$db,$err;
    $err->log("cron","lst_cron");
    $db->query("SELECT * FROM cron WHERE uid = $cuid ORDER BY url;");
    $r=Array();
    while ($db->next_record()) {
      $tmp=Array();
      $tmp['id']=$db->f('id');
      $tmp['url']=urldecode($db->f('url'));
      $tmp['user']=urldecode($db->f('user'));
      $tmp['password']=urldecode($db->f('password'));
      $tmp['schedule']=$db->f('schedule');
      $tmp['email']=urldecode($db->f('email'));
      $tmp['next_execution']=$db->f('next_execution');
      $r[]=$tmp;
    }
    return $r;
  }

  function hook_menu() {
    $obj = array(
      'title'       => _("Scheduled tasks"),
      'ico'         => 'images/schedule.png',
      'link'        => 'cron.php',
      'pos'         => 90,
     ) ;

     return $obj;
  }

  /*---------------------------------------------------------------------------*/
  /** update the crontab 
   * @param $arr array the crontab information, including its ID
   * @return boolean TRUE if the crontab has been edited
  */
  function update($arr) {
    $ok=true;
    foreach ($arr as $a) {
      if (! isset($a['id'])) $a['id']=null;
      if (empty($a['url']) && is_null($a['id'])) continue;
      if (! $this->_update_one($a['url'], $a['user'], $a['password'], $a['email'], $a['schedule'], $a['id']) ) {
        $ok=false;
      }
    }
    return $ok;
  }
  

  /*---------------------------------------------------------------------------*/
  /** delete a crontab 
   * @param $id the id of the crontab to delete
   * @return boolean TRUE if the crontab has been deleted
  */
  function delete_one($id) {
    global $db,$err,$cuid;
    $err->log("cron","delete_one");
    return $db->query("DELETE FROM cron WHERE id=".intval($id)." AND uid=$cuid LIMIT 1;");
  }
  

  /*---------------------------------------------------------------------------*/
  /** update a crontab, 
   * @return boolean TRUE if the crontab has been edited
  */
  private function _update_one($url, $user, $password, $email, $schedule, $id=null) {
    global $db,$err,$quota,$cuid;
    $err->log("cron","update_one");

    if (empty($url) && !is_null($id)) {
      return $this->delete_one($id);
    }


    if(filter_var($url,FILTER_VALIDATE_URL)===false){
      $err->raise("cron",_("URL not valid"));
      return false;
    }
    $url=urlencode($url);
    $user=urlencode($user);
    if (empty($user)) $password='';
    $password=urlencode($password);
    
    //@todo remove checkmail cf functions.php
    if (!empty($email) && ! checkmail($email) == 0 ){ 
        $err->raise("cron",_("Email address is not valid"));
      return false;
    }
    $email=urlencode($email);
    if (! $this->valid_schedule($schedule)) return false;

    if (is_null($id)) { // if a new insert, quotacheck
      $q = $quota->getquota("cron");
      if ( $q["u"] >= $q["t"] ) {
        $err->raise("cron",_("You quota of cron entries is over. You cannot create more cron entries"));
        return false;
      }
    } else { // if not a new insert, check the $cuid
      $db->query("SELECT uid FROM cron WHERE id = $id;");
      if (! $db->next_record()) { 
        return "false"; 
      } // return false if pb
      if ( $db->f('uid') != $cuid ) {
        $err->raise("cron",_("Identity problem"));
        return false;
      } 
    }
    $query = "REPLACE INTO cron (id, uid, url, user, password, schedule, email) VALUES ('$id', '$cuid', '$url', '$user', '$password', '$schedule', '$email') ;";
    return $db->query("$query");
  }


  /*---------------------------------------------------------------------------*/
  /** validate a crontab schedule
   * @param $s array schedule paramters
   * @return boolean TRUE if the schedule is valid
  */
  function valid_schedule($s) {
    $s2 = intval($s);
    if ($s2 != $s) return false;
    $r=false;
    foreach ($this->schedule() as $cs ) {
      if ($cs['unit'] == $s) return true;
    }
    return $r;
  }

  /*---------------------------------------------------------------------------*/
  /** hook for quota computation
   */
  function hook_quota_get() {
    global $cuid,$db,$err;
    $err->log("cron","alternc_get_quota");
    $q=Array("name"=>"cron", "description"=>_("Scheduled tasks"), "used"=>0);
    $db->query("select count(*) as cnt from cron where uid = $cuid;");
    if ($db->next_record()) {
        $q['used']=$db->f('cnt');
    }
    return $q;
  }

  /*---------------------------------------------------------------------------*/
  /**
   * Execute the required crontab of AlternC users
   * this function EXIT at the end.
   */
  function execute_cron() {
    global $db;

    $db->query("SELECT id, url, email, schedule, user, password FROM cron WHERE next_execution <= NOW();");
    $urllist=array();
    
    while ($db->next_record()) {
      $db->Record["url"]=urldecode($db->Record["url"]);
      // we support only http or https schemes:
      if (substr($db->Record["url"],0,7)=="http://" || substr($db->Record["url"],0,8)=="https://") {
	$u=array(
		 "url" => $db->Record["url"], 
		 "id" => $db->Record["id"], "email" =>$db->Record["email"], 
		 );
	
	if ($db->Record["user"] && $db->Record["password"]) {
	  $u["login"]=$db->Record["user"]; 
	  $u["password"]=$db->Record["password"]; 
	}
	$urllist[]=$u;
      }
      
      if (empty($urllist)) { // nothing to do : 
	exit(0); 
      }
      
      // cron_callback($url, $content, $curlobj) will be called at the end of each http call.
      $this->rolling_curl($urllist, array("m_cron","cron_callback"));
    }
  }

  
  
  /*---------------------------------------------------------------------------*/
  /**
   * Callback function called by rolling_curl when a cron resulr has been received
   * schedule it for next run and send the mail if needed
   */
  function cron_callback($url,$content,$curl) {
    global $db,$L_FQDN;
    if (empty($url["id"])) return; // not normal...
    $id=intval($url["id"]);

    if ($curl["http_code"]==200) {
      $ok=true;
    } else {
      $ok=false;
    }
    if (isset($url["email"]) && $url["email"] && $content) {
      mail($url["email"],"AlternC Cron #$id - Report ".date("%r"),"Please find below the stdout content produced by your cron task.\n------------------------------------------------------------\n\n".$content,"From: postmaster@$L_FQDN");
    }
    // now schedule it for next run:
    $db->query("UPDATE cron SET next_execution=FROM_UNIXTIME( UNIX_TIMESTAMP(NOW()) + schedule * 60) WHERE id=$id");
  }
  


  /*---------------------------------------------------------------------------*/
  /**
   * Launch parallel (using MAX_SOCKETS sockets maximum) retrieval
   * of URL using CURL 
   * @param $urls array of associative array, each having the following keys : 
   *  url = url to get (of the form http[s]://login:password@host/path/file?querystring )
   *  login & password = if set, tell the login and password to use as simple HTTP AUTH.
   *  - any other key will be sent as it is to the callback function
   * @param $callback function called for each request when completing. First argument is the $url object, second is the content (output)
   *  third is the info structure from curl for the returned page. 200 for OK, 403 for AUTH FAILED, 0 for timeout, dump it to know it ;) 
   *  this function should return as soon as possible to allow other curl calls to complete properly.
   * @param $cursom_options array of custom CURL options for all transfers
   */
  function rolling_curl($urls, $callback, $custom_options = null) {
    // make sure the rolling window isn't greater than the # of urls
    if (!isset($GLOBALS["DEBUG"])) $GLOBALS["DEBUG"]=false;
    $rolling_window = m_cron::MAX_SOCKETS;
    $rolling_window = (count($urls) < $rolling_window) ? count($urls) : $rolling_window;
    
    $master = curl_multi_init();
    $curl_arr = array();
    
    // add additional curl options here
    $std_options = array(CURLOPT_RETURNTRANSFER => true,
			 CURLOPT_FOLLOWLOCATION => false,
			 CURLOPT_CONNECTTIMEOUT => 5,
			 CURLOPT_TIMEOUT => 240, // 4 minutes timeout for a page
			 CURLOPT_USERAGENT => "AlternC (Cron Daemon)",
			 CURLOPT_MAXREDIRS => 0);

    if ($GLOBALS["DEBUG"]) $std_options[CURLOPT_VERBOSE]=true;
    $options = ($custom_options) ? ($std_options + $custom_options) : $std_options;
    
    // start the first batch of requests
    for ($i = 0; $i < $rolling_window; $i++) {
      $ch = curl_init();
      $options[CURLOPT_URL] = $urls[$i]["url"];
      if ($GLOBALS["DEBUG"]) echo "URL: ".$urls[$i]["url"]."\n";
      curl_setopt_array($ch,$options);
      // Handle custom cafile for some https url
      if (strtolower(substr($options[CURLOPT_URL],0,5))=="https") {
	  curl_setopt($ch,CURLOPT_CAINFO,m_cron::DEFAULT_CAFILE);
	  if ($GLOBALS["DEBUG"]) echo "cainfo set to DEFAULT\n";
      }
      if (isset($urls[$i]["login"]) && isset($urls[$i]["password"])) { // set basic http authentication
	curl_setopt($ch,CURLOPT_HTTPAUTH,CURLAUTH_BASIC);
	curl_setopt($ch,CURLOPT_USERPWD,urlencode($urls[$i]["login"]).":".urlencode($urls[$i]["password"]));
	if ($GLOBALS["DEBUG"]) echo "set basic auth\n";
      }
      curl_multi_add_handle($master, $ch);
    }
    
    do {
      while(($execrun = curl_multi_exec($master, $running)) == CURLM_CALL_MULTI_PERFORM);
      if($execrun != CURLM_OK)
	break;
      // a request was just completed -- find out which one
      while($done = curl_multi_info_read($master)) {
	$info = curl_getinfo($done['handle']);
	// TODO : since ssl_verify_result is buggy, if we have [header_size] => 0  && [request_size] => 0 && [http_code] => 0, AND https, we can pretend the SSL certificate is buggy.
	if ($GLOBALS["DEBUG"]) { echo "Info for ".$done['handle']." \n"; print_r($info); } 
	if ($info['http_code'] == 200)  {
	  $output = curl_multi_getcontent($done['handle']);
	} else {
	  // request failed.  add error handling.
	  $output="";
	}
	// request terminated.  process output using the callback function.
	// Pass the url array to the callback, so we need to search it
	foreach($urls as $url) {
	  if ($url["url"]==$info["url"]) {
	    call_user_func($callback,$url,$output,$info);
	    break;
	  }
	}
	
	// If there is more: start a new request
	// (it's important to do this before removing the old one)
	if ($i<count($urls)) {
	  $ch = curl_init();
	  $options[CURLOPT_URL] = $urls[$i++];  // increment i
	  curl_setopt_array($ch,$options);
	  if (strtolower(substr($options[CURLOPT_URL],0,5))=="https") {
	    curl_setopt($ch,CURLOPT_CAINFO,m_cron::DEFAULT_CAFILE);
	    if ($GLOBALS["DEBUG"]) echo "cainfo set to DEFAULT\n";
	  }
	  if (isset($urls[$i]["login"]) && isset($urls[$i]["password"])) {  // set basic http authentication
	    curl_setopt($ch,CURLOPT_HTTPAUTH,CURLAUTH_BASIC);
	    curl_setopt($ch,CURLOPT_USERPWD,urlencode($urls[$i]["login"]).":".urlencode($urls[$i]["password"]));
	    if ($GLOBALS["DEBUG"]) echo "set basic auth\n";
	  }
	  curl_multi_add_handle($master, $ch);
	}
	// remove the curl handle that just completed
	curl_multi_remove_handle($master, $done['handle']);
      }
    } while ($running);
    
    curl_multi_close($master);
    return true;
  }


} /* Class cron */
