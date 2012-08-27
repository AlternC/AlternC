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
 Purpose of file: Manage piwik Statistics set
 ----------------------------------------------------------------------
*/

/**
 * This class manage piwik statistics management through AlternC, using piwik's "API".
 */
class m_piwik {
  var $piwik_server_uri;
  var $piwik_admin_token;


  /*---------------------------------------------------------------------------*/
  /** Constructor
  */
  function m_piwik() {
    $this->piwik_server_uri=variable_get('piwik_server_uri',null);
    if (is_null($this->piwik_server_uri)) { // if not configuration var, setup one (with a default value)
      variable_set('piwik_server_uri','','Remote Piwik server uri');
      $this->piwik_server_uri='';
    }
    $this->piwik_admin_token=variable_get('piwik_admin_token',null);
    if (is_null($this->piwik_admin_token)) { // if not configuration var, setup one (with a default value)
      variable_set('piwik_admin_token','','Remote Piwik super-admin token');
      $this->piwik_admin_token='';
    }
  }

  /* ----------------------------------------------------------------- */
  /** hook called when an AlternC account is deleted
   */
  function hook_admin_del_member() {
    //FIXME : implement the hook_admin_del_member for piwik
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Returns the used quota for the $name service for the current user.
   * @param $name string name of the quota 
   * @return integer the number of service used or false if an error occured
   * @access private
   */
  function hook_quota_get() {
    global $db, $cuid;
    $db->query("SELECT COUNT(id) AS nb FROM piwik_users WHERE uid='$cuid'");
    $q=Array("name"=>"piwik", "description"=>_("Statistics through Piwik accounts"), "used"=>0);
    if ($db->next_record()) {
      $q['used']=$db->f('nb');
    }
    return $q;
  }


  function url() {
    return $this->piwik_server_uri;
  }


  /***********************/
  /* User-related tasks */
  /***********************/


  function user_add($user_login, $user_mail = null) {

        global $db, $mem, $cuid, $err;

        $user_login = $this->clean_user_name($user_login);
	$user_pass  = create_pass();
	$user_mail  = $user_mail ? $user_mail : $mem->user['mail'];
	$user_mail = create_pass(4) . $user_mail;
	$user_alias = $user_login;

	$api_data = $this->call_privileged_page('API', 'UsersManager.addUser', array('userLogin' => $user_login, 'password' => $user_pass, 'email' => $user_mail, 'alias' => $user_alias), 'JSON'); 
	if ($api_data) {
	  if ($api_data->result === 'success') {
	    $user = $this->get_user($user_login);
	    $user_creation_date = $user->date_registered;
	    $db->query("INSERT INTO piwik_users (uid, login, created_date) VALUES ('$cuid', '$user_login', '$user_creation_date')");
	  }
	} else { // api_data = false -> error is already filled
	  return FALSE;
	}
  }


  // Edite un user
  function user_edit() {
    //FIXME
    return true;
  }


  function get_user($user_login) {
    $api_data = $this->call_privileged_page('API', 'UsersManager.getUser', array('userLogin' => $user_login));

    if ($api_data)
      return $api_data[0];
    else
      return FALSE;
  }


  // Supprime l'utilisateur Piwik passé en parametre
  // Ne le supprime pas localement tant que pas supprimé en remote
  function user_delete($piwik_user_login) {
    global $db, $cuid, $err;
    
    $db->query("SELECT created_date, COUNT(id) AS cnt FROM piwik_users WHERE uid='$cuid' AND login='$piwik_user_login'");
    $db->next_record();

    if ($db->f('cnt') == 1) {
	$api_data = $this->call_privileged_page('API', 'UsersManager.getUser', array('userLogin' => $piwik_user_login));
	printvar($api_data);
	if ($api_data[0]->date_registered == $db->f('created_date'))
	  echo "equals";
	else
	  echo "non equals";
	// $api_data = $this->call_privileged_page('API', 'UsersManager.deleteUser', array('idSite' => $site_id));	
    } else {
      $err->raise("piwik", _("You are not allowed to delete the statistics of this website"));
      return FALSE;
    }
    //SitesManager.deleteSite (idSite)
    //FIXME
    return true;
  }
 

  function users_list() { 
    global $db, $cuid;
    $db->query("SELECT login FROM piwik_users WHERE uid = '$cuid'");
    if ($db->num_rows() == 0)
      return array();
    $users = '';
    while ($db->next_record())
	$users .= ($users !== '') ? ',' . $db->f('login') : $db->f('login');
    return $this->call_privileged_page('API', 'UsersManager.getUsers', array('userLogins' => $users)); 
  }


  // Verifie que l'utilisateur existe bien dans piwik
  function user_checkremote($puser_id) {
    //FIXME
    return true;
  }


  // Récupére un token pour le SSO avec piwik pour l'user
  function user_remoteauth() {
    //FIXME
    return true;
  }

  // Montre la liste des site pour lesques un user à accés
  function user_access() {
    // FIXME
    return true;
  }





  /***********************/
  /* Site-related tasks */
  /***********************/


  function site_list() {
    $api_data = $this->call_privileged_page('API', 'SitesManager.getAllSites');
    $data = array();

    if($api_data) {
      foreach ($api_data AS $site) {

	$item = new stdClass();

	$item->id       = $site->idsite;
	$item->name     = $site->name;
	$item->main_url = $site->main_url;

	$user_data = $this->call_privileged_page('API', 'UsersManager.getUsersAccessFromSite', array('idSite' => $site->idsite));

	if (is_array($user_data)) {
	    printvar($user_data);
	  } else if(is_object($user_data)) {
	    $item->rights = json_decode($user_data[0]);
	  }

	$data[] = $item;
      }
      return $data;
    } else
      return FALSE;
  }


  // Ajoute un site à Piwik
  // can't figure out how to pass multiple url through the API
  function site_add($siteName, $urls, $ecommerce = FALSE) {
    $urls = is_array($urls) ? implode(',', $urls) : $urls;
    $api_data = $this->call_privileged_page('API', 'SitesManager.addSite', array('siteName' => $siteName, 'urls' => $urls));
    printvar($api_data);
    return TRUE;
  }


  // Supprime un site de Piwik
  function site_delete($site_id) {
    global $db, $cuid, $err;
    
    $db->query("SELECT COUNT(id) AS cnt FROM piwik_sites WHERE uid='$cuid' AND piwik_id='$site_id'");
    $db->next_record();

    if ($db->f('cnt') == 1) {
	$api_data = $this->call_privileged_page('API', 'SitesManager.deleteSite', array('idSite' => $site_id));
	printvar($api_data);
	
    } else {
	$err->raise("piwik", _("You are not allowed to delete the statistics of this website"));
	return FALSE;
    }

    //SitesManager.deleteSite (idSite)
    //FIXME
    return true;
  }
 

  // Ajoute un alias sur un site existant
  function site_alias_add() {
    // FIXME
    return true;
  }



  /* Helper code FIXME: rename those function using "private" + "_" prefix  */

  function clean_user_name($username) {
    return mysql_real_escape_string(trim($username));
  }


  function dev() {
    // $this->call_page('module', 'method', array('user' => 'fser', 'pass' => 'toto'));
    // return $this->users_list();
  }


  function call_page($module, $method, $arguments=array(), $output = 'JSON') {
    global $err;
	$url = sprintf('%s/?module=%s&method=%s&format=%s', $this->piwik_server_uri, $module, $method, $output);
	foreach ($arguments AS $k=>$v)
	  $url .= sprintf('&%s=%s', urlencode($k), $v); //  urlencode($v));

	// We are supposed to chose what's enabled on our php instance :-)
	// if (! ini_get('allow_url_fopen')==True) {
      	//	$err->raise("piwik",_("Program Error: PHP ini var 'allow_url_fopen' is not allowed"));
	//}
	echo $url;
	$page_content = file_get_contents($url);
	if ($page_content === FALSE) {
	  $err->raise("piwik", _("Unable to reach the API"));
	  return FALSE;
	}

	if ($output == 'JSON') {
	  $api_data = json_decode($page_content);
	  if ($api_data == FALSE) {
	    $err->raise("piwik", _("Error while decoding response from the API"));
	    return FALSE;
	  }

	  return $api_data;
	} else {
	  $err->raise("piwik", _("Other format than JSON is not implemented yet"));
	  return FALSE;
	}
  }


  function call_privileged_page($module, $method, $arguments=array(), $output = 'JSON') {
	$arguments['token_auth'] = $this->piwik_admin_token;
	return $this->call_page($module, $method, $arguments, $output);
  }


} /* Class piwik */
