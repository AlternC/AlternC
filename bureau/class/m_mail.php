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
  Purpose of file: Manage Email accounts and aliases.
  ----------------------------------------------------------------------
*/

/**
* This class handle emails (pop and/or aliases and even wrapper for internal
* classes) of hosted users.
*
* @copyright    AlternC-Team 2012-09-01 http://alternc.com/
* This class is directly using the following alternc MySQL tables:
* address = any used email address will be defined here, mailbox = pop/imap mailboxes, recipient = redirection from an email to another
* and indirectly the domain class, to know domain names from their id in the DB.
* This class is also defining a few hooks, search ->invoke in the code.
*/
class m_mail {


  /* ----------------------------------------------------------------- */
  /** domain list for this account
   * @access private
   */
  var $domains;


  /* ----------------------------------------------------------------- */
  /** If an email has those chars, 'not nice in shell env' ;) 
   * we don't store the email in $mail/u/{user}_domain, but in $mail/_/{address_id}_domain
   * @access private
   */
  var $specialchars=array('"',"'",'\\','/');


  /* ----------------------------------------------------------------- */
  /** If an email has those chars, we will ONLY allow RECIPIENTS, NOT POP/IMAP for DOVECOT !
   * Since Dovecot doesn't allow those characters
   * @access private
   */
  var $forbiddenchars=array('"',"'",'\\','/','?','!','*','$','|','#','+');


  /* ----------------------------------------------------------------- */
  /** Number of results for a pager display
   * @access public
   */
  var $total;

  
  // Human server name for help
  var $srv_submission;
  var $srv_smtp;
  var $srv_smtps;
  var $srv_imap;
  var $srv_imaps;
  var $srv_pop3;
  var $srv_pop3s;

  /* ----------------------------------------------------------------- */
  /** 
   * Constructeur
   */
  function m_mail() {
    global $L_FQDN;
    $this->srv_submission = variable_get('mail_human_submission', $L_FQDN,'Human name for mail server (submission protocol)');
    $this->srv_smtp       = variable_get('mail_human_smtp',       $L_FQDN,'Human name for mail server (SMTP protocol)');
    $this->srv_smtps      = variable_get('mail_human_smtps',      $L_FQDN,'Human name for mail server (SMTPS protocol)');
    $this->srv_imap       = variable_get('mail_human_imap',       $L_FQDN,'Human name for IMAP mail server');
    $this->srv_imaps      = variable_get('mail_human_imaps',      $L_FQDN,'Human name for IMAPS mail server');
    $this->srv_pop3       = variable_get('mail_human_pop3',       $L_FQDN,'Human name for POP3 mail server');
    $this->srv_pop3s      = variable_get('mail_human_pop3s',      $L_FQDN,'Human name for POP3s mail server');
  }

  // FIXME documenter
  function catchall_getinfos($domain_id) {
    global $dom, $db;
    $rr=array(
      'mail_id'=>'',
      'domain' =>$dom->get_domain_byid($domain_id),
      'target' => '',
      'type'   => '',
      );
    
    $db->query("select r.recipients as dst, a.id mail_id from address a, recipient r where a.domain_id = $domain_id and r.address_id = a.id and a.address='';");
    if ($db->next_record()) {
      $rr['target'] = $db->f('dst');
      $rr['mail_id'] = $db->f('mail_id');
    }

    // Does it redirect to a specific mail or to a domain
    if (empty($rr['target'])) {
      $rr['type']='none';
    } elseif (substr($rr['target'],0,1)=='@') {
      $rr['type']='domain';
    } else {
      $rr['type']='mail';
    }
    
    return $rr;
  }

  function catchall_del($domain_id) {
    $catch = $this->catchall_getinfos($domain_id);
    if (empty($catch['mail_id'])) return false;
    return $this->delete($catch['mail_id']);
  }

  function catchall_set($domain_id, $target) {
    // target :
    $target=rtrim($target);
    if ( substr_count($target,'@') == 0 ) { // Pas de @
      $target = '@'.$target;
    } 

    if ( substr($target,0,1) == '@' ) { // le premier caractere est un @
      // FIXME validate domain
    } else { // ca doit être un mail
      if (!filter_var($target,FILTER_VALIDATE_EMAIL)) {
        $err->raise("mail",_("The email you entered is syntaxically incorrect"));
        return false;
      }
    }
    $this->catchall_del($domain_id);
    return $this->create_alias($domain_id, '', $target, "catchall", true);
  }


  /* ----------------------------------------------------------------- */
  /** get_quota (hook for quota class), returns the number of used 
   * service for a quota-bound service
   * @param $name string the named quota we want
   * @return the number of used service for the specified quota, 
   * or false if I'm not the one for the named quota
   */
  function hook_quota_get() {
    global $db,$err,$cuid;
    $err->log("mail","getquota");
    $q=Array("name"=>"mail", "description"=>_("Email addresses"), "used"=>0);
    $db->query("SELECT COUNT(*) AS cnt FROM address a, domaines d WHERE a.domain_id=d.id AND d.compte=$cuid AND a.type='';");
    if ($db->next_record()) {
      $q['used']=$db->f("cnt");
    }
    return $q;
  }


  /* ----------------------------------------------------------------- */
  /** Password policy kind used in this class (hook for admin class)
   * @return array an array of policykey => "policy name (for humans)"
   */
  function alternc_password_policy() {
    return array("pop"=>_("Email account password"));
  }


  /* ----------------------------------------------------------------- */
  /** Returns the list of mail-hosting domains for a user
   * @return array indexed array of hosted domains
   */
  function enum_domains($uid=-1) {
      global $db,$err,$cuid;
      $err->log("mail","enum_domains");
      if ($uid == -1) { $uid = $cuid; }
      $db->query("
SELECT
  d.id,
  d.domaine,
  IFNULL( COUNT(a.id), 0) as nb_mail
FROM
  domaines d LEFT JOIN address a ON (d.id=a.domain_id AND a.type='')
WHERE
  d.compte = $uid
GROUP BY
  d.id
ORDER BY
  d.domaine
;
");
      $this->enum_domains=array();
      while($db->next_record()){
          $this->enum_domains[]=$db->Record;
      }
      return $this->enum_domains;
  }


  /* ----------------------------------------------------------------- */
  /** available: tells if an email address can be installed in the server
   * check the domain part (is it mine too), the syntax, and the availability.
   * @param $mail string email to check
   * @return boolean true if the email can be installed on the server 
   */  
  function available($mail){
    global $db,$err,$dom;
    $err->log("mail","available");    
    list($login,$domain)=explode("@",$mail,2);
    // Validate the domain ownership & syntax
    if (!($dom_id=$dom->get_domain_byname($domain))) {
      return false;
    }
    // Validate the email syntax:
    if (!filter_var($mail,FILTER_VALIDATE_EMAIL)) {
      $err->raise("mail",_("The email you entered is syntaxically incorrect"));
      return false;
    }
    // Check the availability
    $db->query("SELECT a.id FROM address a WHERE a.domain_id=".$dom_id." AND a.address='".addslashes($login)."';");
    if ($db->next_record()) {
      return false;
    } else {
      return true;
    }
  }


  /* ----------------------------------------------------------------- */
  /* function used to list every mail address hosted on a domain.
   * @param $dom_id integer the domain id.
   * @param $search string search that string in recipients or address.
   * @param $offset integer skip THAT much emails in the result.
   * @param $count integer return no more than THAT much emails. -1 for ALL. Offset is ignored then.
   * @result an array of each mail hosted under the domain.
   */
  function enum_domain_mails($dom_id = null, $search="", $offset=0, $count=30, $show_systemmails=false){
    global $db,$err,$cuid,$hooks;
    $err->log("mail","enum_domains_mail");

    $search=trim($search);

    $where="a.domain_id=$dom_id";
    if ($search) $where.=" AND (a.address LIKE '%".addslashes($search)."%' OR r.recipients LIKE '%".addslashes($search)."%')";
    if (!$show_systemmails) $where.=" AND type='' ";
    $db->query("SELECT count(a.id) AS total FROM address a LEFT JOIN recipient r ON r.address_id=a.id WHERE $where;");
    $db->next_record();
    $this->total=$db->f("total");
    if ($count!=-1) $limit="LIMIT $offset,$count"; else $limit="";
    $db->query("SELECT a.id, a.address, a.password, a.`enabled`, a.mail_action, d.domaine AS domain, m.quota, m.quota*1024*1024 AS quotabytes, m.bytes AS used, NOT ISNULL(m.id) AS islocal, a.type, r.recipients, m.lastlogin, a.domain_id  
         FROM (address a LEFT JOIN mailbox m ON m.address_id=a.id) LEFT JOIN recipient r ON r.address_id=a.id, domaines d 
         WHERE $where AND d.id=a.domain_id $limit ;");
    if (! $db->next_record()) {
      $err->raise("mail",_("No email found for this query"));
      return false;
    }
    $res=array();
    do {
      $details=$db->Record;
      // if necessary, fill the typedata with data from hooks ...
      if ($details["type"]) {
	      $result=$hooks->invoke("hook_mail_get_details",array($details)); // Will fill typedata if necessary
	      $details["typedata"]=implode("<br />",$result);
      }
      $res[]=$details;
    } while ($db->next_record());
    return $res;
  }


  function hook_mail_get_details($detail) {
    if ($detail['type']=='catchall') return _(sprintf("Special mail address for catch-all. <a href='mail_manage_catchall.php?domain_id=%s'>Click here to manage it.</a>",$detail['domain_id']));
  }


  /* ----------------------------------------------------------------- */
  /** Function used to insert a new mail into the db
   * should be used by the web interface, not by third-party programs.
   *
   * This function calls the hook "hooks_mail_cancreate"
   * which must return FALSE if the user can't create this email, and raise and error accordingly
   * 
   * @param $dom_id integer A domain_id (owned by the user) 
   * (will be the part at the right of the @ in the email)
   * @param $mail string the left part of the email to create (something@dom_id)
   * @return an hashtable containing the database id of the newly created mail, 
   * or false if an error occured ($err is filled accordingly)
   */ 
  function create($dom_id, $mail,$type="",$dontcheck=false){
    global $err,$db,$cuid,$quota,$dom,$hooks;
    $err->log("mail","create",$mail);

    // Validate the domain id
    if (!($domain=$dom->get_domain_byid($dom_id))) {
      return false;
    }

    // Validate the email syntax:
    $m=$mail."@".$domain;
    if (!filter_var($m,FILTER_VALIDATE_EMAIL) && !$dontcheck) {
      $err->raise("mail",_("The email you entered is syntaxically incorrect"));
      return false;
    }

    // Call other classes to check we can create it:
    $cancreate=$hooks->invoke("hook_mail_cancreate",array($dom_id,$mail));
    if (in_array(false,$cancreate,true)) {
      return false;
    }

    // Check the quota:
    if (!$quota->cancreate("mail")) {
      $err->raise("mail",_("You cannot create email addresses: your quota is over"));
      return false;
    }
    // Already exists?
    $db->query("SELECT * FROM address WHERE domain_id=".$dom_id." AND address='".addslashes($mail)."';");
    if ($db->next_record()) {
      $err->raise("mail",_("This email address already exists"));
      return false;
    }
    // Create it now
    $db->query("INSERT INTO address (domain_id, address,type) VALUES ($dom_id, '".addslashes($mail)."','$type');");
    if (!($id=$db->lastid())) {
      $err->raise("mail",_("An unexpected error occured when creating the email"));
      return false;
    }
    return $id;
  }


  /* ----------------------------------------------------------------- */
  /** function used to get every information we can on a mail 
  * @param $mail_id integer
  * @return array a hashtable with all the informations for that email
  */
  function get_details($mail_id) {
    global $db, $err, $cuid, $hooks;
    $err->log("mail","get_details");

    $mail_id=intval($mail_id);
    // Validate that this email is owned by me...
    if (!($mail=$this->is_it_my_mail($mail_id))) {
      return false;
    }

    // We fetch all the informations for that email: these will fill the hastable : 
    $db->query("SELECT a.id, a.address, a.password, a.enabled, d.domaine AS domain, m.path, m.quota, m.quota*1024*1024 AS quotabytes, m.bytes AS used, NOT ISNULL(m.id) AS islocal, a.type, r.recipients, m.lastlogin, a.mail_action, m.mail_action AS mailbox_action FROM (address a LEFT JOIN mailbox m ON m.address_id=a.id) LEFT JOIN recipient r ON r.address_id=a.id, domaines d WHERE a.id=".$mail_id." AND d.id=a.domain_id;");
    if (! $db->next_record()) return false;
    $details=$db->Record;
    // if necessary, fill the typedata with data from hooks ...
    if ($details["type"]) {
      $result=$hooks->invoke("hook_mail_get_details",array($mail_id)); // Will fill typedata if necessary
      $details["typedata"]=implode("<br />",$result);
    }
    return $details;
  }


  private $isitmy_cache=array(); 

  /* ----------------------------------------------------------------- */
  /** Check if an email is mine ...
   *
   * @param $mail_id integer the number of the email to check
   * @return string the complete email address if that's mine, false if not
   * ($err is filled accordingly)
   */ 
  function is_it_my_mail($mail_id){
    global $err,$db,$cuid;
    $mail_id=intval($mail_id);
    // cache it (may be called more than one time in the same page).
    if (isset($this->isitmy_cache[$mail_id])) return $this->isitmy_cache[$mail_id];

    $db->query("SELECT concat(a.address,'@',d.domaine) AS email FROM address a, domaines d WHERE d.id=a.domain_id AND a.id=$mail_id AND d.compte=$cuid;");
    if ($db->next_record()) {
      return $this->isitmy_cache[$mail_id]=$db->f("email");
    } else {
      $err->raise("mail",_("This email is not yours, you can't change anything on it"));
      return $this->isitmy_cache[$mail_id]=false;
    }
  }


  /* ----------------------------------------------------------------- */
  /** Hook called when the DOMAIN class will delete a domain.
   *
   * @param $dom integer the number of the email to delete
   * @return true if the email has been properly deleted 
   * or false if an error occured ($err is filled accordingly)
   */ 
  function hook_dom_del_mx_domain($dom_id) {
    $list=$this->enum_domain_mails($dom_id,"",0,-1);
    if (is_array($list)) {
      foreach($list as $one) {
	$this->delete($one["id"]);
      }
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Function used to delete a mail from the db
   * should be used by the web interface, not by third-party programs.
   *
   * @param $mail_id integer the number of the email to delete
   * @return true if the email has been properly deleted 
   * or false if an error occured ($err is filled accordingly)
   */ 
  function delete($mail_id){
    global $err,$db,$cuid,$quota,$dom,$hooks;
    $err->log("mail","delete");

    $mail_id=intval($mail_id);

    if (!$mail_id)  {
      $err->raise("mail",_("The email you entered is syntaxically incorrect"));
      return false;
    }
    // Validate that this email is owned by me...
    if (!($mail=$this->is_it_my_mail($mail_id))) {
      return false;
    }

    // Search for that address:
    $db->query("SELECT a.id, a.type, a.mail_action, m.mail_action AS mailbox_action, NOT ISNULL(m.id) AS islocal FROM address a LEFT JOIN mailbox m ON m.address_id=a.id WHERE a.id='$mail_id';");
    if (!$db->next_record()) {
      $err->raise("mail",_("The email %s does not exist, it can't be deleted"),$mail);
      return false;
    }
    if ($db->f("mail_action")!="OK" || ($db->f("islocal") && $db->f("mailbox_action")!="OK")) { // will be deleted soon ...
      $err->raise("mail",_("The email %s is already marked for deletion, it can't be deleted"),$mail);
      return false;
    }
    $mail_id=$db->f("id");

    if ($db->f("islocal")) {
      // If it's a pop/imap mailbox, mark it for deletion
      $db->query("UPDATE address SET mail_action='DELETE', enabled=0 WHERE id='$mail_id';");
      $db->query("UPDATE mailbox SET mail_action='DELETE' WHERE address_id='$mail_id';");
      $err->raise("mail",_("The email %s has been marked for deletion"),$mail);
    } else {
      // If it's only aliases, delete it NOW.
      $db->query("DELETE FROM address WHERE id='$mail_id';");
      $db->query("DELETE FROM mailbox WHERE address_id='$mail_id';");
      $db->query("DELETE FROM recipient WHERE address_id='$mail_id';");
      $err->raise("mail",_("The email %s has been successfully deleted"),$mail);
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Function used to undelete a pending deletion mail from the db
   * should be used by the web interface, not by third-party programs.
   *
   * @param $mail_id integer the email id
   * @return true if the email has been properly undeleted 
   * or false if an error occured ($err is filled accordingly)
   */ 
  function undelete($mail_id){
    global $err,$db,$cuid,$quota,$dom,$hooks;
    $err->log("mail","undelete");

    $mail_id=intval($mail_id);

    if (!$mail_id)  {
      $err->raise("mail",_("The email you entered is syntaxically incorrect"));
      return false;
    }
    // Validate that this email is owned by me...
    if (!($mail=$this->is_it_my_mail($mail_id))) {
      return false;
    }

    // Search for that address:
    $db->query("SELECT a.id, a.type, a.mail_action, m.mail_action AS mailbox_action, NOT ISNULL(m.id) AS islocal FROM address a LEFT JOIN mailbox m ON m.address_id=a.id WHERE a.id='$mail_id';");
    if (!$db->next_record()) {
      $err->raise("mail",_("The email %s does not exist, it can't be undeleted"),$mail);
      return false;
    }
    if ($db->f("type")!="") { // Technically special : mailman, sympa ... 
      $err->raise("mail",_("The email %s is special, it can't be undeleted"),$mail);
      return false;
    }
    if ($db->f("mailbox_action")!="DELETE" || $db->f("mail_action")!="DELETE") { // will be deleted soon ...
      $err->raise("mail",_("Sorry, deletion of email %s is already in progress, or not marked for deletion, it can't be undeleted"),$mail);
      return false;
    }
    $mail_id=$db->f("id");

    if ($db->f("islocal")) {
      // If it's a pop/imap mailbox, mark it for deletion
      $db->query("UPDATE address SET mail_action='OK', `enabled`=1 WHERE id='$mail_id';");
      $db->query("UPDATE mailbox SET mail_action='OK' WHERE address_id='$mail_id';");
      $err->raise("mail",_("The email %s has been undeleted"),$mail);
      return true;
    } else {
      $err->raise("mail",_("-- Program Error -- The email %s can't be undeleted"),$mail);
      return false;
    }
  }


  /* ----------------------------------------------------------------- */
  /** set the password of an email address.
   * @param $mail_id integer email ID 
   * @param $pass string the new password.
   * @return boolean true if the password has been set, false else, raise an error.
   */  
  function set_passwd($mail_id,$pass){
    global $db,$err,$admin;
    $err->log("mail","setpasswd");

    if (!($email=$this->is_it_my_mail($mail_id))) return false;
    if (!$admin->checkPolicy("pop",$email,$pass)) return false;
    if (!$db->query("UPDATE address SET password='"._md5cr($pass)."' where id=$mail_id;")) return false;
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Enables an email address.
   * @param $mail_id integer Email ID
   * @return boolean true if the email has been enabled.
   */  
  function enable($mail_id){
    global $db,$err;
    $err->log("mail","enable");
    if (!($email=$this->is_it_my_mail($mail_id))) return false;
    if (!$db->query("UPDATE address SET `enabled`=1 where id=$mail_id;")) return false;
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Disables an email address.
   * @param $mail_id integer Email ID
   * @return boolean true if the email has been enabled.
   */ 
  function disable($mail_id){
    global $db,$err;
    $err->log("mail","disable");
    if (!($email=$this->is_it_my_mail($mail_id))) return false;
    if (!$db->query("UPDATE address SET `enabled`=0 where id=$mail_id;")) return false;
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Function used to update an email settings
   * should be used by the web interface, not by third-party programs.
   *
   * @param $mail_id integer the number of the email to delete
   * @param $islocal boolean is it a POP/IMAP mailbox ?
   * @param $quotamb integer if islocal=1, quota in MB
   * @param $recipients string recipients, one mail per line.
   * @return true if the email has been properly edited
   * or false if an error occured ($err is filled accordingly)
   */ 
  function set_details($mail_id, $islocal, $quotamb, $recipients,$delivery="dovecot",$dontcheck=false) {
    global $err,$db,$cuid,$quota,$dom,$hooks;
    $delivery=mysql_escape_string($delivery);
    $err->log("mail","set_details");
    if (!($me=$this->get_details($mail_id))) {
      return false;
    }
    if ($me["islocal"] && !$islocal) {
      // delete pop
      $db->query("UPDATE mailbox SET mail_action='DELETE' WHERE address_id=".$mail_id.";");
    } 
    if (!$me["islocal"] && $islocal) {
      // create pop
      $path="";
      if($delivery=="dovecot"){
        $path=ALTERNC_MAIL."/".substr($me["address"]."_",0,1)."/".$me["address"]."_".$me["domain"];
      }
      foreach($this->forbiddenchars as $str) {
	    if (strpos($me["address"],$str)!==false) {
	      $err->raise("mail",_("There is forbidden characters in your email address. You can't make it a POP/IMAP account, you can only use it as redirection to other emails"));
          return false;
          break;
        }
      }
      foreach($this->specialchars as $str) {
	    if (strpos($me["address"],$str)!==false) {
	      $path=ALTERNC_MAIL."/_/".$me["id"]."_".$me["domain"];
	      break;
  	    } 
      }
      $db->query("INSERT INTO mailbox SET address_id=$mail_id, delivery='$delivery', path='".addslashes($path)."';");
    }
    if ($me["islocal"] && $islocal && $me["mailbox_action"]=="DELETE") {
      $db->query("UPDATE mailbox SET mail_action='OK' WHERE mail_action='DELETE' AND address_id=".$mail_id.";");
    }

    if ($islocal) {
      if ($quotamb!=0 && $quotamb<(intval($me["used"]/1024/1024)+1)) {
	$quotamb=intval($me["used"]/1024/1024)+1;
	$err->raise("mail",_("You set a quota smaller than the current mailbox size. Since it's not allowed, we set the quota to the current mailbox size"));
      }
      $db->query("UPDATE mailbox SET quota=".intval($quotamb)." WHERE address_id=".$mail_id.";");
    }

    $r=explode("\n",$recipients);
    $red="";
    foreach($r as $m) {
      $m=trim($m);
      if ($m && ( filter_var($m,FILTER_VALIDATE_EMAIL) || $dontcheck)  // Recipient Email is valid
	  && $m!=($me["address"]."@".$me["domain"])) {  // And not myself (no loop allowed easily ;) )
	$red.=$m."\n";
      }
    }
    $db->query("DELETE FROM recipient WHERE address_id=".$mail_id.";");
    if ($m) {
      $db->query("INSERT INTO recipient SET address_id=".$mail_id.", recipients='".addslashes($red)."';");
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** A wrapper used by mailman class to create it's needed addresses 
   * @ param : $dom_id , the domain id associated to a given address
   * @ param : $m , the left part of the  mail address being created
   * @ param : $delivery , the delivery used to deliver the mail
   */

  function add_wrapper($dom_id,$m,$delivery){
    global $err,$db,$mail;
    $err->log("mail","add_wrapper","creating $delivery $m address");

    $mail_id=$mail->create($dom_id,$m,$delivery);
    $this->set_details($mail_id,1,0,'',$delivery);
    // FIXME return error code
  }

  /* ----------------------------------------------------------------- */
  /** A function used to create an alias for a specific address
   * @ param : $dom_id , the domain sql identifier
   * @ param : $m , the alias we want to create
   * @ param : $alias , the already existing aliased address
   * @ param : $type, the type of the alias created
   */
  function create_alias($dom_id,$m,$alias,$type="",$dontcheck=false) {
    global $err,$db,$mail;
    $err->log("mail","create_alias","creating $m alias for $alias type $type");

    $mail_id=$mail->create($dom_id,$m,$type,$dontcheck);
    $this->set_details($mail_id,0,0,$alias,"dovecot",$dontcheck);
    // FIXME return error code
  }



  /* ----------------------------------------------------------------- */
  /** A wrapper used by mailman class to create it's needed addresses 
   * @ param : $mail_id , the mysql id of the mail address we want to delete
   * of the email for the current acccount.
   */
  function del_wrapper($mail_id){
    global $err,$db;
    $err->log("mail","del_wrapper");
    $this->delete($mail_id);
  }

  /* ----------------------------------------------------------------- */
  /** Export the mail information of an account 
   * @return: str, string containing the complete configuration 
   * of the email for the current acccount.
   */
  function alternc_export_conf() {
    global $db,$err,$mail_localbox;
    $err->log("mail","export");
    $domain=$this->enum_domains();
    $str="<mail>\n";
    $onepop=false;
    foreach ($domain as $d) {
      $str.="  <domain>\n    <name>".xml_entities($d["domain"])."</name>\n";
      $s=$this->enum_domain_mails($d["id"]);
      if (count($s)) {
	while (list($key,$val)=each($s)){
	  $test=$this->get_details($val['id']);
	  $str.="    <address>\n";
	  $str.="      <name>".xml_entities($val["address"])."</name>\n";
	  $str.="      <enabled>".xml_entities($val["enabled"])."</enabled>\n";
	  if(is_array($val["islocal"])){
	    $str.="      <islocal>1</islocal>\n";
             $str.="      <quota>".$val["quota"]."</quota>\n";
             $str.="      <path>".$val["path"]."</path>\n";
           }else{
             $str.="      <islocal>0</islocal>\n";
          }
          if(!empty($val["recipients"])){
	    $r=explode("\n",$val["recipients"]);
              foreach($r as $recip){
                $str.="      <recipients>".$recip."<recipients>\n";
              }
          }
       $str.="    </address>\n";
     }
       }     
       $str.="  </domain>\n";
     }
     $str.="</mail>\n";
     return $str;
   }
 

  /* ----------------------------------------------------------------- */
  /**
   * Return the list of allowed slave accounts (secondary-mx)
   * @return array
   */
  function enum_slave_account() {
    global $db,$err;
    $db->query("SELECT login,pass FROM mxaccount;");
    $res=array();
    while ($db->next_record()) {
        $res[]=$db->Record;
    }
    if (!count($res)) return false;
    return $res;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Check for a slave account (secondary mx)
   * @param string $login the login to check
   * @param string $pass the password to check
   * @return boolean TRUE if the password is correct, or FALSE if an error occurred.
   */
  function check_slave_account($login,$pass) {
    global $db,$err;
    $login=mysql_escape_string($login);
    $pass=mysql_escape_string($pass);
    $db->query("SELECT * FROM mxaccount WHERE login='$login' AND pass='$pass';");
    if ($db->next_record()) {
        return true;
    }
    return false;
  }


  /* ----------------------------------------------------------------- */
  /** Out (echo) the complete hosted domain list : 
   */
  function echo_domain_list() {
  global $db,$err;
  $db->query("SELECT domaine FROM domaines WHERE gesdns=1 ORDER BY domaine");
  while ($db->next_record()) {
    echo $db->f("domaine")."\n";
  }
  return true;
  }


  /* ----------------------------------------------------------------- */
  /**
   * Add a slave account that will be allowed to access the mxdomain list
   * @param string $login the login to add
   * @param string $pass the password to add
   * @return boolean TRUE if the account has been created, or FALSE if an error occurred.
   */
  function add_slave_account($login,$pass) {
    global $db,$err;
    $login=mysql_escape_string($login);
    $pass=mysql_escape_string($pass);
    $db->query("SELECT * FROM mxaccount WHERE login='$login'");
    if ($db->next_record()) {
      $err->raise("mail",_("The slave MX account was not found"));
      return false;
    }
    $db->query("INSERT INTO mxaccount (login,pass) VALUES ('$login','$pass')");
    return true;
  }


  /* ----------------------------------------------------------------- */
  /**
   * Remove a slave account
   * @param string $login the login to delete
   */
  function del_slave_account($login) {
    global $db,$err;
    $login=mysql_escape_string($login);
    $db->query("DELETE FROM mxaccount WHERE login='$login'");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** hook function called by AlternC when a domain is created for
   * the current user account using the SLAVE DOMAIN feature
   * This function create a CATCHALL to the master domain
   * @param string $domain_id Domain that has just been created
   * @param string $target_domain Master domain 
   * @access private
   */
  function hook_dom_add_slave_domain($domain_id,$target_domain) { 
    global $err;
    $err->log("mail","hook_dom_add_slave_domain",$domain_id);
    $this->catchall_set($domain_id,'@'.$target_domain); 
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** hook function called by AlternC-upnp to know which open 
   * tcp or udp ports this class requires or suggests
   * @return array a key => value list of port protocol name mandatory values
   * @access private
   */
  function hook_upnp_list() {
    return array(
		 "imap" => array("port" => 143, "protocol" => "tcp", "mandatory" => 1),
		 "imaps" => array("port" => 993, "protocol" => "tcp", "mandatory" => 1),
		 "pop" => array("port" => 110, "protocol" => "tcp", "mandatory" => 1),
		 "pops" => array("port" => 995, "protocol" => "tcp", "mandatory" => 1),
		 "smtp" => array("port" => 25, "protocol" => "tcp", "mandatory" => 1),
		 "sieve" => array("port" => 2000, "protocol" => "tcp", "mandatory" => 1),
		 "submission" => array("port" => 587, "protocol" => "tcp", "mandatory" => 0),
		 );
  }

 

} /* Class m_mail */


