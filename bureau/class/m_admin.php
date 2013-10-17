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
 Purpose of file: Administrate members and rights.
 ----------------------------------------------------------------------
*/

/* ----------------------------------------------------------------- */

/**
* Manage the AlternC's account administration (create/edit/delete)
*/
class m_admin {


  /* ----------------------------------------------------------------- */
  /** $enabled tells if the logged user is super-admin or not
   */
  var $enabled=0;

  /* ----------------------------------------------------------------- */
  /** List of the controls made for each TLD
   *
   * $tldmode is used by the administration panel, while choosing
   * the authorized TLDs. It's an array of strings explaining the current state of the TLD.
   */
  public $tldmode=array();

  var $archive='';


  /* ----------------------------------------------------------------- */
  /** Constructor
   */
  function m_admin() {
    global $db,$cuid;
    $db->query("SELECT su FROM membres WHERE uid='$cuid';");
    $db->next_record();
    $this->enabled=$db->f("su");

    $this->tldmode=array(
			 0 => _("This TLD is forbidden"),
			 1 => _("primary DNS is checked in WHOIS db"),
			 2 => _("primary & secondary DNS are checked in WHOIS db"),
			 3 => _("Domain must exist, but don't do any DNS check"),
			 4 => _("Domain can be installed, no check at all"),
			 5 => _("Domain can be installed, force NO DNS hosting"),
			 );
    $this->archive=variable_get('archive_del_data','','If folder specified html folder of deleted user is archived, else it is deleted. ');
  }

  function hook_menu() {
    global $mem, $cuid, $debug_alternc, $L_INOTIFY_UPDATE_DOMAIN;
    if (!$mem->checkRight()) return false;

    $obj = array(
      'title'       => _("Administration"),
      'ico'         => 'images/admin.png',
      'link'        => 'toggle',
      'class'       => 'adminmenu',
      'pos'         => 10,
      'links'       => 
        array(
          array(
           'txt'   => _("Manage AlternC accounts"), 
           'url'   => 'adm_list.php',
           'class' => 'adminmenu'
          ),
          array(
           'txt'   => _("User Quotas"), 
           'url'   => 'quotas_users.php?mode=4',
           'class' => 'adminmenu'
          ),
        )
     ) ;

    if ($cuid == 2000) {
      $obj['links'][] = 
        array(
           'txt'   => _("Admin Control Panel"), 
           'url'   => 'adm_panel.php',
           'class' => 'adminmenu'
          );
      $obj['links'][] = 
        array(
           'txt'   => _("PhpMyAdmin"), 
           'url'   => '/alternc-sql/',
           'class' => 'adminmenu',
           'target' => '_blank',
          );
      $obj['links'][] = 
        array(
           'txt'   => ($debug_alternc->status)?_("Switch debug Off"):_("Switch debug On"),
           'url'   => "alternc_debugme.php?enable=".($debug_alternc->status?"0":"1"), 
           'class' => 'adminmenu'
          );
      if (empty($L_INOTIFY_UPDATE_DOMAIN) || file_exists("$L_INOTIFY_UPDATE_DOMAIN") ) {
        $obj['links'][] =
          array(
             'txt'     => _("Applying..."),
             'url'     => 'javascript:alert(\''._("Domain changes are already applying").'\');',
             'class'   => 'adminmenu',
            );
      } else {
        $obj['links'][] =
          array(
             'txt'     => _("Apply changes"),
             'url'     => 'adm_update_domains.php',
             'class'   => 'adminmenu',
             'onclick' => 'return confirm("'.addslashes(_("Server configuration changes are applied every 5 minutes. Do you want to do it right now?")).'");',
            );

      } // L_INOTIFY_UPDATE_DOMAIN

    } // cuid == 2000


    return $obj;
  }

  function stop_if_jobs_locked() {
    if ( file_exists(ALTERNC_LOCK_JOBS)) {
      echo "There is a file ".ALTERNC_LOCK_JOBS."\n";
      echo "So no jobs are allowed\n";
      echo "Did you launch alternc.install ?\n";
      die();
    }
  }

  # return the uid of an alternc account
  function get_uid_by_login($login) {
    global $db;
    $db->query("SELECT uid FROM membres WHERE login='$login';");
    if (! $db->next_record()) {
      return null;
    }
    return $db->f('uid');
  }


  /* ----------------------------------------------------------------- */
  /** Returns the known information about a hosted account
   * 
   * Returns all what we know about an account (contents of the tables
   *  <code>membres</code> et <code>local</code>)
   * Ckecks if the account is super-admin
   * @param integer $uid a unique integer identifying the account
   * @return an associative array containing all the fields of the
   * table <code>membres</code> and <code>local</code> of the corresponding account.
   * Returns FALSE if an error occurs.
   */
  function get($uid,$recheck=false) {
    global $err,$db,$lst_users_properties;
    //    $err->log("admin","get",$uid);
    if (!$this->enabled) {
      $err->raise("admin",_("-- Only administrators can access this page! --"));
      return false;
    }

    if (!isset($lst_users_properties) || empty($lst_users_properties) || !is_array($lst_users_properties) || $recheck ) {
      $lst_users_properties=array();
      $db->query("
	SELECT 
		m.uid as muid, 
		l.*, 
		m.*, 
		parent.login as parentlogin,
		dbs.name as db_server_name,
		m.renewed + INTERVAL m.duration MONTH as expiry,
		CASE 
			WHEN m.duration IS NULL THEN 0 
			WHEN m.renewed + INTERVAL m.duration MONTH <= NOW() THEN 3	
			WHEN m.renewed <= NOW() THEN 2
		ELSE 1 END 'status'
		
	FROM membres as m 
		LEFT JOIN membres as parent ON (parent.uid = m.creator) 
		LEFT JOIN db_servers as dbs ON (m.db_server_id = dbs.id)
		LEFT JOIN local as l ON (m.uid = l.uid) ;");
       while ($db->next_record()) {
         $lst_users_properties[$db->f('muid')]=$db->Record;
       }
    }

    if ( !isset($lst_users_properties[$uid]) ) {
      if ( !$recheck ) {
        // don't exist, but is not a forced check. Do a forced check
        return $this->get($uid, true);
      } 
      $err->raise("admin",_("Account not found"));
      return false;
    }

    return $lst_users_properties[$uid];
  }


  /* ----------------------------------------------------------------- */
  /** Returns the known information about a specific hosted account
   * Similar to get_list() but for creators/resellers.
   */
  function get_creator($uid) {
    global $err,$db;
    //    $err->log("admin","get",$uid);
    if (!$this->enabled) {
      $err->raise("admin",_("-- Only administrators can access this page! --"));
      return false;
    }

    $db->query("SELECT m.*, parent.login as parentlogin FROM membres as m LEFT JOIN membres as parent ON (parent.uid = m.creator) WHERE m.uid='$uid';");

    if ($db->num_rows()) {
      $db->next_record();
      $c=$db->Record;
    } else {
      $err->raise("admin",_("Account not found"));
      return false;
    }

    $db->query("SELECT * FROM local WHERE uid='$uid';");
    if ($db->num_rows()) {
      $db->next_record();
      reset($db->Record);
      while (list($key,$val)=each($db->Record)) {
	      $c[$key]=$val;
      }
    }

    $db->query("SELECT count(*) as nbcreated FROM membres WHERE creator='$uid';");
    if ($db->num_rows()) {
      $db->next_record();
      reset($db->Record);
      while (list($key,$val)=each($db->Record)) {
	      $c[$key]=$val;
      }
    }

    return $c;
  }


  /* ----------------------------------------------------------------- */
  /** @return TRUE if there's only ONE admin account
   * @return boolean TRUE if there is only one admin account
   * (allow the program to prevent the destruction of the last admin account)
   */
  function onesu() {
    global $db;
    $db->query("SELECT COUNT(*) AS cnt FROM membres WHERE su=1");
    $db->next_record();
    return ($db->f("cnt")==1);
  }


  /* ----------------------------------------------------------------- */
  /** Returns the list of the hosted accounts
   * 
   * Returns all what we know about ALL the accounts (contents of the tables
   *  <code>membres</code> et <code>local</code>)
   * Check for super-admin accounts
   * @param
   * @return an associative array containing all the fields of the
   * table <code>membres</code> and <code>local</code> of all the accounts.
   * Returns FALSE if an error occurs.
   */
  function get_list($all=0,$creator=0,$pattern=FALSE,$pattern_type=FALSE) {
    global $err,$mem,$cuid;
    $err->log("admin","get_list");
    if (!$this->enabled) {
      $err->raise("admin",_("-- Only administrators can access this page! --"));
      return false;
    }
    $db=new DB_System();


    if ($pattern) {

  	if ($pattern_type === 'domaine') {

	   $request = 'SELECT compte AS uid FROM domaines WHERE 1';

	   if ($pattern && preg_match('/[.a-zA-Z0-9]+/', $pattern))
		$request .= sprintf(' AND domaine LIKE "%%%s%%"', $pattern);

	   $request .= ' GROUP BY uid';

        } elseif ($pattern_type === 'login') {

	   $request = 'SELECT uid FROM membres WHERE 1';

           if ($pattern && preg_match('/[a-zA-Z0-9]+/', $pattern))
		$request .= sprintf(' AND login LIKE "%%%s%%"', $pattern);

	   if ($creator) 
		$request .= sprintf(' AND creator = "%s"', $creator);

	   if ($mem->user['uid']!=2000 && !$all)
		$request .= sprintf(' AND creator = "%s"', $cuid);

	   $request .= ' ORDER BY login;';

	} else {

 	   $err->raise("admin", _("Invalid pattern type provided. Are you even performing a legitimate action?"));
	   return FALSE;

        }

    } else {
  
	if ($creator)
	{
      	    // Limit listing to a specific reseller
     	    $request = "SELECT uid FROM membres WHERE creator='".$creator."' ORDER BY login;";
	} elseif ($mem->user['uid']==2000 || $all) {
	      $request = "SELECT uid FROM membres ORDER BY login;";
        } else {
              $request = "SELECT uid FROM membres WHERE creator='".$cuid."' ORDER BY login;";
        }
    }

    $db->query($request);

    if ($db->num_rows()) {
      while ($db->next_record()) {
	$c[]=$this->get($db->f("uid"));
      }
      return $c;
    } else {
      return false;
    }
  }


  /* ----------------------------------------------------------------- */
  /** Send an email to all AlternC's accounts
   * @param $subject string Subject of the email to send
   * @param $message string Message to send 
   * @param $from string expeditor of that email.
   * @return true if the mail has been successfully sent.
   */ 
  function mailallmembers($subject,$message,$from) {
    global $err,$mem,$cuid,$db;
    $err->log("admin","mailallmembers");
    if (!$this->enabled) {
      $err->raise("admin",_("-- Only administrators can access this page! --"));
      return false;
    }
    $subject=trim($subject);
    $message=trim($message);
    $from=trim($from);

    if (empty($subject) || empty($message) || empty($from) ){
      $err->raise("admin",_("Subject, message and sender are mandatory"));
      return false;
    }

    if (checkmail($from) != 0) {
      $err->raise("admin",_("Sender is syntaxically incorrect"));
      return false;
    }

    @set_time_limit(1200);
    $db->query("SELECT DISTINCT mail FROM membres WHERE mail!='';");
    while ($db->next_record()) {
      // Can't do BCC due to postfix limitation
      // FIXME: use phpmailer, far better for mass-mailing than sendmail (reply-to issue among others)
      mail($db->f('mail'), $subject, $message, null, "-f$from");
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Returns an array with the known information about resellers (uid, login, number of accounts)
   * Does not include account 2000 in the list.
   * May only be called by the admin account (2000)
   * If there are no reseller accounts, returns an empty array.
   */
  function get_creator_list() {
    global $err,$mem,$cuid;

    $creators = array();

    $err->log("admin","get_reseller_list");
    if (!$this->enabled || $cuid!=2000) {
      $err->raise("admin",_("-- Only administrators can access this page! --"));
      return false;
    }

    $db=new DB_System();
    $db->query("SELECT DISTINCT creator FROM membres WHERE creator <> 0 ORDER BY creator ASC;");
    if ($db->num_rows()) {
      while ($db->next_record()) {
        $creators[] = $this->get_creator($db->f("creator"));
      }
    }
    $creators2 = array();
    foreach ($creators as $cc ) {
      $creators2[$cc['uid']] = $cc;
    }
    return $creators2;
  }

  /* ----------------------------------------------------------------- */
  /** Check if I am the creator of the member $uid
   * @param integer $uid a unique integer identifying the account
   * @return boolean TRUE if I am the creator of that account. FALSE else.
   */
  function checkcreator($uid) {
    global $err,$mem,$db,$cuid;
    if ($cuid==2000) {
      return true;
    }
    $db->query("SELECT creator FROM membres WHERE uid='$uid';");
    $db->next_record();
    if ($db->Record["creator"]!=$cuid) {
      $err->raise("admin",_("-- Only administrators can access this page! --"));
      return false;
    }
    return true;
  }

  // When the admin want to delegate a subdomain to an account
  function add_shared_domain($u, $domain_name) {
    global $db,$err,$dom,$mem,$cuid;
    $err->log("admin","add_shared_domain",$u."/".$domain_name);

    if (! $mem->checkright() ) {
      $err->raise("admin",_("-- Only administrators can do that! --"));
      return false;
    } 

    // Check if this domain exist on this admin account
    if (! in_array($domain_name, $dom->enum_domains())) {
      $err->raise("admin",_("You don't seem to be allowed to delegate this domain"));
      $err->log("admin","add_shared_domain","domain not allowed");
      return false;
    } 

    // Clean the domain_name 
    $domain_name=preg_replace("/^\.\.*/", "", $domain_name);

    $mem->su($u);
    $dom->lock();
    // option : 1=hébergement dns, 1=noerase, empeche de modifier, 1=force
    $dom->add_domain($mem->user['login'].".".$domain_name,1,1,1);
    $dom->unlock();
    $mem->unsu();
    return true;
  }
    
  /* ----------------------------------------------------------------- */
  /** Creates a new hosted account
   *  
   * Creates a new hosted account (in the tables <code>membres</code>
   * and <code>local</code>). Prevents any manipulation of the account if
   * the account $mid is not super-admin.
   *
   * @param $login string Login name like [a-z][a-z0-9]*
   * @param $pass string Password (max. 64 characters)
   * @param $nom string Name of the account owner
   * @param $prenom string First name of the account owner
   * @param $mail string Email address of the account owner, useful to get
   * one's lost password
   * @pararm $type string Account type for quotas
   * @return boolean Returns FALSE if an error occurs, TRUE if not.
   */
  function add_mem($login, $pass, $nom, $prenom, $mail, $canpass=1, $type='default', $duration=0, $notes = "", $force=0, $create_dom=false, $db_server_id) {
    global $err,$quota,$classes,$cuid,$mem,$L_MYSQL_DATABASE,$L_MYSQL_LOGIN,$hooks,$action;
    $err->log("admin","add_mem",$login."/".$mail);
    if (!$this->enabled) {
      $err->raise("admin",_("-- Only administrators can access this page! --"));
      return false;
    }
    if (empty($db_server_id)) {
      $err->raise("admin",_("Missing db_server field"));
      return false;
    }
    if (($login=="")||($pass=="")) {
      $err->raise("admin",_("All fields are mandatory"));
      return false;
    }
    if (!$force) {
      if ($mail=="") {
	$err->raise("admin",_("All fields are mandatory"));
	      return false;
      }
      if (checkmail($mail)!=0){
	$err->raise("admin",_("Please enter a valid email address"));
	      return false;
      }
    }
    $login=strtolower($login);
    if (!preg_match("#^[a-z0-9]+$#",$login)) { //$
      $err->raise("admin", _("Login can only contains characters a-z and 0-9"));
      return false;
    }
    if (strlen($login) > 14) {
      // Not an arbitrary value : MySQL user names can be up to 16 characters long
      // If we want to allow people to create a few mysql_user (and we want to!)
      // we have to limit the login lenght
      $err->raise("admin",_("The login is too long (14 chars max)"));
      return false;
    }
    // Some login are not allowed...
    if ($login==$L_MYSQL_DATABASE || $login==$L_MYSQL_LOGIN || $login=="mysql" || $login=="root") {
      $err->raise("admin",_("Login can only contains characters a-z, 0-9 and -"));
      return false;
    }
    $pass=_md5cr($pass);
    $db=new DB_System();
    // Already exist?
    $db->query("SELECT count(*) AS cnt FROM membres WHERE login='$login';");
    $db->next_record();
    if (!$db->f("cnt")) {
      $db->query("SELECT max(m.uid)+1 as nextid FROM membres m");
      if (!$db->next_record()) {
	      $uid=2000;
      } else {
	      $uid=$db->Record["nextid"];
	      if ($uid<=2000) $uid=2000;
      }
      $db->query("INSERT INTO membres (uid,login,pass,mail,creator,canpass,type,created,notes,db_server_id) VALUES ('$uid','$login','$pass','$mail','$cuid','$canpass', '$type', NOW(), '$notes', '$db_server_id');");
      $db->query("INSERT INTO local(uid,nom,prenom) VALUES('$uid','$nom','$prenom');");
      $this->renew_update($uid, $duration);
      #exec("sudo /usr/lib/alternc/mem_add ".$login." ".$uid);
      $action->create_dir(getuserpath("$login"));
      $action->fix_user($uid);
      
      // Triggering hooks
      $mem->su($uid);
      // TODO: old hook method FIXME: when unused remove this
      /*
      foreach($classes as $c) {
      	if (method_exists($GLOBALS[$c],"alternc_add_member")) {
	        $GLOBALS[$c]->alternc_add_member();
	      }
      }
      */
      $hooks->invoke("alternc_add_member");
      // New hook way
      $hooks->invoke("hook_admin_add_member", array(), array('quota')); // First !!! The quota !!! Etherway, we can't be sure to be able to create all
      $hooks->invoke("hook_admin_add_member");
      $mem->unsu();

      if (!empty($create_dom)) { 
        $this->add_shared_domain($uid, $create_dom); 
      }

      return $uid;
    } else {
      $err->raise("admin",_("This login already exists"));
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /** AlternC's standard function called when a user is created
   * This sends an email if configured through the interface.
   */
  function hook_admin_add_member() {
    global $err, $cuid, $L_FQDN, $L_HOSTING;
    $dest = variable_get('new_email');
    if (!$dest) {
      return false;
    }
    $db=new DB_System();
    if (!$db->query("SELECT m.*, parent.login as parentlogin FROM membres m LEFT JOIN membres parent ON parent.uid=m.creator WHERE m.uid='$cuid'")) {
      $err->raise("admin",sprintf(_("query failed: %s "), $db->Error));
      return false;
    }
    if ($db->next_record()) {
      // TODO: put that string into gettext ! 
      $mail = <<<EOF
A new AlternC account was created on %fqdn by %creator.

Account details
---------------

login: %login (%uid)
email: %mail
createor: %creator (%cuid)
can change password: %canpass
type: %type
notes: %notes
EOF;
       $mail = strtr($mail, array('%fqdn' => $L_FQDN,
       				  '%creator' => $db->Record['parentlogin'],
				  '%uid' => $db->Record['uid'],
				  '%login' => $db->Record['login'],
				  '%mail' => $db->Record['mail'],
				  '%cuid' => $db->Record['creator'],
				  '%canpass' => $db->Record['canpass'],
				  '%type' => $db->Record['type'],
				  '%notes' => $db->Record['notes']));
       $subject=sprintf(_("New account %s from %s on %s"), $db->Record['login'], $db->Record['parentlogin'], $L_HOSTING);
       if (mail($dest,$subject,$mail,"From: postmaster@$L_FQDN")) {
         //sprintf(_("Email successfully sent to %s"), $dest);
         return true;
       } else {
         $err->raise("admin",sprintf(_("Cannot send email to %s"), $dest));
         return false;
       } 
    } else {
       $err->raise("admin",sprintf(_("Query failed: %s"), $db->Error));
       return false;
    }
  }


  /* ----------------------------------------------------------------- */
  /** Edit an account
   *  
   * Change an account (in the tables <code>membres</code>
   * and <code>local</code>). Prevents any manipulation of the account if
   * the account $mid is not super-admin.
   *
   * @param $uid integer the uid number of the account we want to modify
   * @param login string new login name like [a-z][a-z0-9]*
   * @param $pass string new password (max. 64 characters)
   * @param $nom string new name of the account owner
   * @param $prenom string new first name of the account owner
   * @param $mail string new email address of the account owner
   * @param $enabled integer (value: 0 or 1) activates or desactivates the
   * @param $type string new type of account
   * access to the virtual desktop of this account.
   * @return boolean Returns FALSE if an error occurs, TRUE if not.
   */
  function update_mem($uid, $mail, $nom, $prenom, $pass, $enabled, $canpass, $type='default', $duration=0, $notes = "",$reset_quotas=false) {
    global $err,$db;
    global $cuid, $quota;

    $notes=addslashes($notes);

    $err->log("admin","update_mem",$uid);
    if (!$this->enabled) {
      $err->raise("admin",_("-- Only administrators can access this page! --"));
      return false;
    }
    $db=new DB_System();
    if ($pass) {
      $pass=_md5cr($pass);
      $ssq=" ,pass='$pass' ";
    } else {
      $ssq="";
    }

    if (($db->query("UPDATE local SET nom='$nom', prenom='$prenom' WHERE uid='$uid';"))
	     &&($db->query("UPDATE membres SET mail='$mail', canpass='$canpass', enabled='$enabled', `type`='$type', notes='$notes' $ssq WHERE uid='$uid';"))){
      if($reset_quotas == "on") {
        $quota->addquotas();
        $quota->synchronise_user_profile();
      }
      $this->renew_update($uid, $duration);
      return true;
    }
    else {
      $err->raise("admin",_("Account not found"));
      return false;
    }
  }


  /* ----------------------------------------------------------------- */
  /** Lock an account
   * Lock an account and prevent the user to access its account.
   * @param $uid integer the uid number of the account we want to lock
   * @return boolean Returns FALSE if an error occurs, TRUE if not.
   */
  function lock_mem($uid) {
    global $err,$db;
    $err->log("admin","lock_mem",$uid);
    if (!$this->enabled) {
      $err->raise("admin",_("-- Only administrators can access this page! --"));
      return false;
    }
    $db=new DB_System();
    if ($db->query("UPDATE membres SET enabled='0' WHERE uid='$uid';")) {
      return true;
    }
    else {
      $err->raise("admin",_("Account not found"));
      return false;
    }
  }


  /* ----------------------------------------------------------------- */
  /** UnLock an account
   * UnLock an account and prevent the user to access its account.
   * @param $uid integer the uid number of the account we want to unlock
   * @return boolean Returns FALSE if an error occurs, TRUE if not.
   */
  function unlock_mem($uid) {
    global $err,$db;
    $err->log("admin","unlock_mem",$uid);
    if (!$this->enabled) {
      $err->raise("admin",_("-- Only administrators can access this page! --"));
      return false;
    }
    $db=new DB_System();
    if ($db->query("UPDATE membres SET enabled='1' WHERE uid='$uid';")) {
      return true;
    }
    else {
      $err->raise("admin",_("Account not found"));
      return false;
    }
  }


  /* ----------------------------------------------------------------- */
  /** Deletes an account
   * Deletes the specified account. Prevents any manipulation of the account if
   * the account $mid is not super-admin.
   * @param $uid integer the uid number of the account we want to delete
   * @return boolean Returns FALSE if an error occurs, TRUE if not.
   */
  function del_mem($uid) {
    global $err,$quota,$classes,$cuid,$mem,$dom,$hooks,$action;
    $err->log("admin","del_mem",$uid);

    if (!$this->enabled) {
      $err->raise("admin",_("-- Only administrators can access this page! --"));
      return false;
    }
    $db=new DB_System();
    $tt=$this->get($uid);
    
    $mem->su($uid);
    // This script may take a long time on big accounts, let's give us some time ... Fixes 1132
    @set_time_limit(0);
    // WE MUST call m_dom before all others because of conflicts ...
    $dom->hook_admin_del_member();

    # New way of deleting or backup delted user html folders using action class
    $path=getuserpath($tt['login']);
    $action->archive($path); 	

    $hooks->invoke("alternc_del_member");
    $hooks->invoke("hook_admin_del_member");
    
    if (($db->query("DELETE FROM membres WHERE uid='$uid';")) &&
	($db->query("DELETE FROM local WHERE uid='$uid';"))) {
      $mem->unsu();
      // If this user was (one day) an administrator one, he may have a list of his own accounts. Let's associate those accounts to nobody as a creator.
      $db->query("UPDATE membres SET creator=2000 WHERE creator='$uid';");
      return true;
    } else {
      $err->raise("admin",_("Account not found"));
      $mem->unsu();
      return false;
    }
  }


  /* ----------------------------------------------------------------- */
  /** Renew an account
   * Renew an account for its duration
   * @param $uid integer the uid number of the account we want to renew
   * @param $periods integer the number of periods we renew for
   * @return boolean Returns FALSE if an error occurs, TRUE if not.
   */
  function renew_mem($uid, $periods=1) {
    global $err,$db;

    $periods = intval($periods);
    if($periods == 0)
      return false;

    $query = "UPDATE membres SET renewed = renewed + INTERVAL (duration * $periods) MONTH WHERE uid=${uid};";
    if ($db->query($query)) {
      return true;
    } else {
      $err->raise("admin",_("Account not found"));
      return false;
    }
  }


  /* ----------------------------------------------------------------- */
  /** Update the duration information for an account
   * @param $uid integer the uid number of the account we want to update
   * @param $duration integer the new duration, in months, of the account
   * @return boolean Returns FALSE if an error occurs, TRUE if not.
   */
  function renew_update($uid, $duration) {
    global $err,$db;

    if($duration == 0) {
      if($db->query("UPDATE membres SET duration = NULL, renewed = NULL WHERE uid=$uid;"))
	return true;
    } else {
      if($db->query("UPDATE membres SET duration = $duration WHERE uid=$uid") &&
	 $db->query("UPDATE membres SET renewed = NOW() WHERE uid=$uid and renewed is null;"))
	return true;
    }

    $err->raise("admin",_("Account not found"));
    return false;
  }


  /* ----------------------------------------------------------------- */
  /** Get the expiry date for an account
   * @param $uid integer The uid number of the account
   * @return string The expiry date, a string as printed by MySQL
   */
  function renew_get_expiry($uid) {
    $jj=$this->get($uid);
    if ( isset($jj) && isset($jj['expiry']) && ! empty($jj['expiry']) ) {
      return $jj['expiry'];
    }
    return '';
  }


  /* ----------------------------------------------------------------- */
  /** Get the expiry status for an account
   * @param $uid integer The uid number of the account
   * @return integer The expiry status:
   *  0: account does not expire
   *  1: expires in more than duration,
   *  2: expires within the duration
   *  3: has expired past the duration
   */
  function renew_get_status($uid) {
    $jj=$this->get($uid);

    if ( isset($jj) && isset($jj['status']) && ! empty($jj['status']) ) {
      return $jj['status'];
    }

    return 0;
  }


  /* ----------------------------------------------------------------- */
  /** Get the expired/about to expire accounts.
   * @return resource The recordset of the corresponding accounts
   */
  function renew_get_expiring_accounts() {
    global $db;

    if(!$db->query("SELECT *, m.renewed + INTERVAL duration MONTH 'expiry'," .
		   " CASE WHEN m.duration IS NULL THEN 0" .
		   " WHEN m.renewed + INTERVAL m.duration MONTH <= NOW() THEN 3" .
		   " WHEN m.renewed <= NOW() THEN 2" .
		   " ELSE 1 END 'status' FROM membres m, local l" .
		   " WHERE m.uid = l.uid" .
		   " HAVING status=2 or status=3 ORDER BY status DESC, expiry;"))
      return false;
    else {
      $res=array();
      while($db->next_record())
	      $res[] = $db->Record;
      return $res;
    }
  }


  /* ----------------------------------------------------------------- */
  /** Turns a common account into a super-admin account
   * @param $uid integer the uid number of the common account we want to turn into a
   *  super-admin account.
   * @return Returns FALSE if an error occurs, TRUE if not.
   */
  function normal2su($uid) {
    global $err,$db;
    $db->query("SELECT su FROM membres WHERE uid='$uid';");
    if (!$db->next_record()) {
      $err->raise("admin",_("Account not found"));
      return false;
    } 
    if ($db->Record["su"]!=0) {
      $err->raise("admin",_("This account is ALREADY an administrator account"));
      return false;
    }
    $db->query("UPDATE membres SET su=1 WHERE uid='$uid';");
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Turns a super-admin account into a common account
   * @param $uid integer the uid number of the super-admin account we want to turn into a
   * common account.
   * @return boolean Returns FALSE if an error occurs, TRUE if not.
   */
  function su2normal($uid) {
    global $err,$db;
    $db->query("SELECT su FROM membres WHERE uid='$uid';");
    if (!$db->next_record()) {
      $err->raise("admin",_("Account not found"));
      return false;
    }
    if ($db->Record["su"]!=1) {
      $err->raise("admin",_("This account is NOT an administrator account!"));
      return false;
    }
    $db->query("UPDATE membres SET su=0 WHERE uid='$uid';");
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** List of the authorized TLDs
   * Returns the list of the authorized TLDs and also the way they are
   * authorized. A TLD is the last members (or the last two) of a
   * domain. For example, "com", "org" etc... AlternC keeps a table
   * containing the list of the TLDs authorized to be installed on the
   * server with the instructions to validate the installation of a
   * domain for each TLD (if necessary).
   * @return array An associative array like $r["tld"], $r["mode"] where tld
   * is the tld and mode is the authorized mode.
   */
  function listtld() {
    global $db;
    $db->query("SELECT tld,mode FROM tld ORDER BY tld;");
    while ($db->next_record()) {
      $c[]=$db->Record;
    }
    return $c;
  }


  /* ----------------------------------------------------------------- */
  /** List the hosted domains on this server
   * Return the list of hosted domains on this server, (an array of associative arrays)
   * @param boolean $alsocheck Returns also errstr and errno telling the domains dig checks
   * @param boolean $forcecheck Force the check of dig domain even if a cache exists.
   * @return array $r[$i] / [domaine][member][noerase][gesdns][gesmx]
   */
  function dom_list($alsocheck=false,$forcecheck=false) {
    global $db;
    $cachefile="/tmp/alternc_dig_check_cache";
    $cachetime=3600; // The dns cache file can be up to 1H old
    if ($alsocheck) {
      if (!$forcecheck && file_exists($cachefile) && filemtime($cachefile)+$cachetime>time()) {
	      $checked=unserialize(file_get_contents($cachefile));
      } else {
        // TODO : do the check here (cf checkdom.php) and store it in $checked
        $checked=$this->checkalldom();
        file_put_contents($cachefile,serialize($checked));
      }
    }
    $db->query("SELECT m.uid,m.login,d.domaine,d.gesdns,d.gesmx,d.noerase FROM domaines d LEFT JOIN membres m ON m.uid=d.compte ORDER BY domaine;");
    while ($db->next_record()) {
      $tmp=$db->Record;
      if ($alsocheck) {
      	$tmp["errstr"]=$checked[$tmp["domaine"]]["errstr"];
	      $tmp["errno"]=$checked[$tmp["domaine"]]["errno"];
      }
      $c[]=$tmp;
    }
    return $c;
  }


  /* ----------------------------------------------------------------- */
  /** Check all the domains for their NS MX and IPs
   */
  function checkalldom() {
    global $db,$L_NS1,$L_NS2,$L_MX,$L_PUBLIC_IP;
    $checked=array();
    $r=$db->query("SELECT * FROM domaines ORDER BY domaine;");
    $dl=array();
    while ($db->next_record()) {
      $dl[$db->Record["domaine"]]=$db->Record;
    }
    sort($dl);
    foreach($dl as $c) {
      // For each domain check its type:
      $errno=0;
      $errstr="";
      $dontexist=false;
      // Check the domain.
      if ($c["gesdns"]==1) {
	      // Check the NS pointing to us
	      $out=array();
	      exec("dig +short NS ".escapeshellarg($c["domaine"]),$out);
	      if (count($out)==0) {
	        $dontexist=true;
	      } else {
	        if (!in_array($L_NS1.".",$out) || !in_array($L_NS2.".",$out)) {
	          $errno=1; $errstr.="NS for this domain are not $L_NS1 and $L_NS2 BUT ".implode(",",$out)."\n";
	        }
	      }
      }
      if ($c["gesmx"]==1 && !$dontexist) {
  	    $out=array();
	      exec("dig +short MX ".escapeshellarg($c["domaine"]),$out);
	      $out2=array();
	      foreach($out as $o) {
	        list($t,$out2[])=explode(" ",$o);
	      }
	      if (!in_array($L_MX.".",$out2)) {
	        $errno=1; $errstr.="MX is not $L_MX BUT ".implode(",",$out2)."\n";
	      }
      }
      if (!$dontexist) {
	      // We list all subdomains and check they are pointing to us.
	      $db->query("SELECT * FROM sub_domaines WHERE domaine='".addslashes($c["domaine"])."' ORDER BY sub;");
	      while ($db->next_record()) {
	        $d=$db->Record;
	        if ($d["type"]==0) {
	          // Check the IP: 
	          $out=array();
	          exec("dig +short A ".escapeshellarg($d["sub"].(($d["sub"]!="")?".":"").$c["domaine"]),$out);
	          if (!in_array($L_PUBLIC_IP,$out)) {
	            $errstr.="subdomain '".$d["sub"]."' don't point to $L_PUBLIC_IP but to ".implode(",",$out)."\n";
	            $errno=1;
	          }
	        }
	      }
      }
      if ($dontexist) {
        $errno=2;
	      $errstr="Domain don't exist anymore !";
      }
      if ($errno==0) $errstr="OK";
      $checked[$c["domaine"]]=array("errno"=>$errno, "errstr"=>$errstr); 
    }
    return $checked;
  }


  /* ----------------------------------------------------------------- */
  /** Lock / Unlock a domain 
   * Lock (or unlock) a domain, so that the member will be (not be) able to delete it
   * from its account
   * @param $dom string Domain name to lock / unlock
   * @return boolean TRUE if the domain has been locked/unlocked or FALSE if it does not exist.
   */
  function dom_lock($domain) {
    global $db,$err;
    $db->query("SELECT compte FROM domaines WHERE domaine='$domain';");
    if (!$db->next_record()) {
      $err->raise("dom",_("Domain '%s' not found."),$domain);
      return false;
    }
    $db->query("UPDATE domaines SET noerase=1-noerase WHERE domaine='$domain';");
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Add a new TLD to the list of the authorized TLDs 
   *
   * @param $tld string top-level domain to add (org, com...)
   * @param $mode integer number of the authorized mode (0 to 5)
   * @return boolean TRUE if the tld has been successfully added, FALSE if not.
   */ 
  function gettld($tld) {
    global $db,$err;
    $db->query("SELECT mode FROM tld WHERE tld='$tld';");
    if (!$db->next_record()) {
      $err->raise("admin",_("This TLD does not exist"));
      return false;
    }
    return $db->Record["mode"];
  }


  /* ----------------------------------------------------------------- */
  /** Prints the list of the actually authorized TLDs
   * @param $current integer Value to select in the list
   */
  function selecttldmode($current=false) {
    for($i=0;$i<count($this->tldmode);$i++) {
      echo "<option value=\"$i\"";
      if ($current==$i) echo " selected=\"selected\"";
      echo ">"._($this->tldmode[$i])."</option>\n";
    }
  }


  /* ----------------------------------------------------------------- */
  /** Deletes the specified tld in the list of the authorized TLDs
   * <b>Note</b> : This function does not delete the domains depending
   * on this TLD
   * @param $tld string The TLD you want to delete
   * @return boolean returns true if the TLD has been deleted, or
   * false if an error occured.
   */
  function deltld($tld) {
    global $db,$err;
    $db->query("SELECT tld FROM tld WHERE tld='$tld';");
    if (!$db->next_record()) {
      $err->raise("admin",_("This TLD does not exist"));
      return false;
    }
    $db->query("DELETE FROM tld WHERE tld='$tld';");
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Add a TLD to the list of the authorized TLDs during the installation
   * @param $tld string TLD we want to authorize
   * @param $mode integer Controls to make on this TLD.
   * <b>Note: </b> If you check in the whois, be sure that
   *  <code>m_domains</code> knows how to name the whois of the specified
   *  domain !
   * @return boolean TRUE if the TLD has been successfully
   *  added. FALSE if not.
   */
  function addtld($tld,$mode) {
    global $db,$err;
    if (!$tld) {
      $err->raise("admin",_("The TLD name is mandatory"));
      return false;
    }
    $tld=trim($tld);

    $db->query("SELECT tld FROM tld WHERE tld='$tld';");
    if ($db->next_record()) {
      $err->raise("admin",_("This TLD already exist"));
      return false;
    }
    if (substr($tld,0,1)==".") $tld=substr($tld,1);
    $mode=intval($mode);
    if ($mode==0) $mode="0";
    $db->query("INSERT INTO tld (tld,mode) VALUES ('$tld','$mode');");
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Modify a TLD of the list of the authorized TLDs 
   * @param $tld string TLD we want to modify
   * @param $mode integer Controls to make on this TLD.
   * @return boolean TRUE if the TLD has been successfully
   * modified. FALSE if not.
   */
  function edittld($tld,$mode) {
    global $db,$err;
    $db->query("SELECT tld FROM tld WHERE tld='$tld';");
    if (!$db->next_record()) {
      $err->raise("admin",_("This TLD does not exist"));
      return false;
    }
    $mode=intval($mode);
    if ($mode==0) $mode="0";
    $db->query("UPDATE tld SET mode='$mode' WHERE tld='$tld';");
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Get the login name of the main administrator account
   * @return string the login name of admin, like 'root' for older alterncs
   */
  function getadmin() {
    global $db;
    $db->query("SELECT login FROM membres WHERE uid = '2000';");
    $db->next_record();
    return $db->f("login");
  }


  /* ----------------------------------------------------------------- */
  /** List the password policies currently installed in the policy table
   * @return array an indexed array of associative array from the MySQL "policy" table
   */
  function listPasswordPolicies() {
    global $db,$classes,$hooks;
    $tmp1=array();
    $tmp2=array();
    $tmp3=array();
    $policies=array();
    $db->query("SELECT * FROM policy;");
    while ($db->next_record()) {
      $tmp1[$db->Record["name"]]=$db->Record;
    }
/* * /
    foreach($classes as $c) {
      if (method_exists($GLOBALS[$c],"alternc_password_policy")) {
	$res=$GLOBALS[$c]->alternc_password_policy(); // returns an array
	foreach($res as $k=>$v) {
	  $tmp2[$k]=$v;
	}
      }
    }
/* */
    $tmp3=$hooks->invoke("alternc_password_policy");
    foreach ($tmp3 as $v) {
      foreach ($v as $l=>$m) {
        $tmp2[$l]=$m;
      }
    }
    foreach($tmp2 as $k=>$v) {
      if (!isset($tmp1[$k])) {
	// Default policy : 
	$db->query("INSERT INTO policy SET name='".addslashes($k)."', minsize=0, maxsize=64, classcount=0, allowlogin=0;");
	$tmp1[$k]=array(
			"minsize"=>0, "maxsize"=>64, "classcount"=>0, "allowlogin"=>0 
			);
      }
      $policies[$k]=$tmp1[$k];
      $policies[$k]["description"]=_($v);
      unset($tmp1[$k]);
    }
    foreach ($tmp1 as $k=>$v) {
      // Delete disabled modules :
      $db->query("DELETE FROM policy WHERE name='".addslashes($k)."';");
    }
    return $policies;
  }


  /* ----------------------------------------------------------------- */
  /** Change a password policy for one kind of password
   * 
   * @param $policy string Name of the policy to edit
   * @param $minsize integer Minimum Password size
   * @param $maxsize integer Maximum Password size
   * @param $classcount integer How many class of characters must this password have
   * @param $allowlogin boolean Do we allow the password to be like the login ? 
   * @return boolean TRUE if the policy has been edited, or FALSE if an error occured.
   */
  function editPolicy($policy,$minsize,$maxsize,$classcount,$allowlogin) {
    global $db;
    $minsize=intval($minsize);
    $maxsize=intval($maxsize);
    $classcount=intval($classcount);
    $allowlogin=intval($allowlogin);

    $db->query("SELECT * FROM policy WHERE name='".addslashes($policy)."';");
    if (!$db->next_record()) {
      return false; // Policy not found
    }
    if ($minsize<0 || $minsize>64 || $maxsize<0 || $maxsize>64 || $maxsize<$minsize || $classcount<0 || $classcount>4) {
      return false; // Incorrect policy ...
    }      
    $allowlogin=($allowlogin)?1:0;
    $db->query("UPDATE policy SET minsize=$minsize, maxsize=$maxsize, classcount=$classcount, allowlogin=$allowlogin WHERE name='".addslashes($policy)."';");
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Check a password and a login for a specific policy 
   * @param $policy string Name of the policy to check for
   * @param $login The login that will be set
   * @param $password The password we have to check
   * @return boolean TRUE if the password if OK for this login and this policy, FALSE if it is not.
   */
  function checkPolicy($policy,$login,$password) {
    global $db,$err;

    if (empty($login)) {
      $err->raise("admin",_("Please enter a login"));
      return false;
    }
    if (empty($password)) {
      $err->raise("admin",_("Please enter a password"));
      return false;
    }

    $pol=$this->listPasswordPolicies();
    if (!$pol[$policy]) {
      $err->raise("admin",_("-- Program error -- The requested password policy does not exist!"));
      return false;
    }
    $pol=$pol[$policy];
    // Ok, now let's check it : 
    $plen=strlen($password);

    if ($plen<$pol["minsize"]) {
      $err->raise("admin",_("The password length is too short according to the password policy"));
      return false;
    }

    if ($plen>$pol["maxsize"]) {
      $err->raise("admin",_("The password is too long according to the password policy"));
      return false;
    }

    if (!$pol["allowlogin"]) {
      // We do misc check on password versus login : 
      $logins=explode("@",$login);
      $logins[]=$login;
      foreach($logins as $l) {
	if (strpos($password,$l)!==false) {
	  $err->raise("admin",_("The password policy prevents you to use your login name inside your password"));
	  return false;
	}
      }
    }

    if ($pol["classcount"]>0) {
      $cls=array(0,0,0,0,0);
      for($i=0;$i<strlen($password);$i++) {
	$p=substr($password,$i,1);
	if (strpos("abcdefghijklmnopqrstuvwxyz",$p)!==false) {
	  $cls[0]=1;
	} elseif (strpos("ABCDEFGHIJKLMNOPQRSTUVWXYZ",$p)!==false) {
	  $cls[1]=1;
	} elseif (strpos("0123456789",$p)!==false) {
	  $cls[2]=1;
	} elseif (strpos('!"#$%&\'()*+,-./:;<=>?@[\\]^_`',$p)!==false) {
	  $cls[3]=1;
	} else {
	  $cls[4]=1;
	}
      } // foreach
      $clc=array_sum($cls);
      if ($clc<$pol["classcount"]) {
	$err->raise("admin",_("Your password contains not enough different classes of character, between low-case, up-case, figures and special characters."));
	return false;
      }
    }
    return true; // congratulations ! 
  }


  /* ----------------------------------------------------------------- */
  /** hook function called by AlternC-upnp to know which open 
   * tcp or udp ports this class requires or suggests
   * @return array a key => value list of port protocol name mandatory values
   * @access private
   */
  function hook_upnp_list() {
    return array(
		 "http" => array("port" => 80, "protocol" => "tcp", "mandatory" => 1),
		 "https" => array("port" => 443, "protocol" => "tcp", "mandatory" => 0),
		 "ssh" => array("port" => 22, "protocol" => "tcp", "mandatory" => 0),
		 );
  }


} /* Classe ADMIN */

