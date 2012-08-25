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


  /* ----------------------------------------------------------------- */
  /** Quota list (hook for quota class)
   */
  function hook_quota_names() {
    return "mail";
  }


  /* ----------------------------------------------------------------- */
  /** get_quota (hook for quota class), returns the number of used 
   * service for a quota-bound service
   * @param $name string the named quota we want
   * @return the number of used service for the specified quota, 
   * or false if I'm not the one for the named quota
   */
  function hook_quota_get($name) {
    global $db,$err,$cuid;
    if ($name=="mail") {
      $err->log("mail","getquota");
      $db->query("SELECT COUNT(*) AS cnt FROM address a, domaines d WHERE a.domain_id=d.id AND d.compte=$cuid AND a.type='';");
      $db->next_record();
      return $db->f("cnt");
    }
    return false;
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
      $db->query("SELECT d.id, d.domaine, COUNT(a.id) AS nb_mail FROM domaines d LEFT JOIN address a ON a.domain_id=d.id WHERE d.compte={$uid} GROUP BY d.id ORDER BY d.domaine ASC;");
      $this->enum_domains=array();
      while($db->next_record()){
          $this->enum_domains[]=$db->Record;
      }
      return $this->enum_domains;
  }


  /* ----------------------------------------------------------------- */
  /* function used to list every mail address hosted on a domain.
   * @param $dom_id integer the domain id.
   * @param $search string search that string in recipients or address.
   * @param $offset integer skip THAT much emails in the result.
   * @param $count integer return no more than THAT much emails. -1 for ALL. Offset is ignored then.
   * @result an array of each mail hosted under the domain.
   */
  function enum_domain_mails($dom_id = null, $search="", $offset=0, $count=30){
    global $db,$err,$cuid,$hooks;
    $err->log("mail","enum_domains_mail");

    $search=trim($search);

    $where="a.domain_id=$dom_id";
    if ($search) $where.=" AND (a.address LIKE '%".addslashes($search)."%' OR r.recipients LIKE '%".addslashes($search)."%')";
    $db->query("SELECT count(a.id) AS total FROM address a LEFT JOIN recipient r ON r.address_id=a.id WHERE $where;");
    $db->next_record();
    $this->total=$db->f("total");
    if ($count!=-1) $limit="LIMIT $offset,$count"; else $limit="";
    $db->query("SELECT a.id, a.address, a.password, a.`enabled`, a.mail_action, d.domaine AS domain, m.quota, m.quota*1024*1024 AS quotabytes, m.bytes AS used, NOT ISNULL(m.id) AS islocal, a.type, r.recipients, m.lastlogin  
         FROM (address a LEFT JOIN mailbox m ON m.address_id=a.id) LEFT JOIN recipient r ON r.address_id=a.id, domaines d 
         WHERE $where AND d.id=a.domain_id $limit;");
    if (! $db->next_record()) {
      $err->raise("mail",_("No mail found for this query"));
      return false;
    }
    $res=array();
    do {
      $details=$db->Record;
      // if necessary, fill the typedata with data from hooks ...
      if ($details["type"]) {
	$result=$hooks->invoke("hook_mail_get_details",array($details["id"])); // Will fill typedata if necessary
	$details["typedata"]=implode("<br />",$result);
      }
      $res[]=$details;
    } while ($db->next_record());
    return $res;
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
  function create($dom_id, $mail){
    global $err,$db,$cuid,$quota,$dom,$hooks;
    $err->log("mail","create");

    // Validate the domain id
    if (!($domain=$dom->get_domain_byid($dom_id))) {
      return false;
    }

    // Validate the email syntax:
    $m=$mail."@".$domain;
    if (!filter_var($m,FILTER_VALIDATE_EMAIL)) {
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
      $err->raise("mail",_("You cannot create email addresses: your quota is over."));
      return false;
    }
    // Already exists?
    $db->query("SELECT * FROM address WHERE domain_id=".$dom_id." AND address='".addslashes($mail)."';");
    if ($db->next_record()) {
      $err->raise("mail",_("This email address already exists"));
      return false;
    }
    // Create it now
    $db->query("INSERT INTO address (domain_id, address) VALUES ($dom_id, '".addslashes($mail)."');");
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
    if ($db->f("type")!="") { // Technically special : mailman, sympa ... 
      $err->raise("mail",_("The email %s is special, it can't be deleted"),$mail);
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
  function set_details($mail_id, $islocal, $quotamb, $recipients) {
    global $err,$db,$cuid,$quota,$dom,$hooks;
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
      $path=ALTERNC_MAIL."/".substr($me["address"]."_",0,1)."/".$me["address"]."_".$me["domain"];
      foreach($this->forbiddenchars as $str) {
	if (strpos($me["address"],$str)!==false) {
	  $err->raise("mail",_("There is forbidden characters in your mail name. You can't make it a POP/IMAP account, you can only use it as redirections to other emails."));
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
      $db->query("INSERT INTO mailbox SET address_id=".$mail_id.", path='".addslashes($path)."';");
    }
    if ($me["islocal"] && $islocal && $me["mailbox_action"]=="DELETE") {
      $db->query("UPDATE mailbox SET mail_action='OK' WHERE mail_action='DELETE' AND address_id=".$mail_id.";");
    }

    if ($islocal) {
      if ($quotamb!=0 && $quotamb<(intval($me["used"]/1024/1024)+1)) {
	$quotamb=intval($me["used"]/1024/1024)+1;
	$err->raise("mail",_("You set a quota smaller than the current mailbox size. Since it's not allowed, we set the quota to the current mailbox size."));
      }
      $db->query("UPDATE mailbox SET quota=".intval($quotamb)." WHERE address_id=".$mail_id.";");
    }

    $r=explode("\n",$recipients);
    $red="";
    foreach($r as $m) {
      $m=trim($m);
      if ($m && filter_var($m,FILTER_VALIDATE_EMAIL)  // Recipient Email is valid
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
 

} /* Class m_mail */


?>
