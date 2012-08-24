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
  /** Number of results for a pager display
   * @access public
   */
  var $total;


  /* ----------------------------------------------------------------- */
  /** Quota list (hook for quota class)
   */
  function alternc_quota_names() {
    return "mail";
  }


  /* ----------------------------------------------------------------- */
  /** get_quota (hook for quota class), returns the number of used 
   * service for a quota-bound service
   * @param $name string the named quota we want
   * @return the number of used service for the specified quota, 
   * or false if I'm not the one for the named quota
   */
  function alternc_get_quota($name) {
    global $db,$err,$cuid;
    if ($name=="mail") {
      $err->log("mail","getquota");
      $db->query("SELECT COUNT(*) AS cnt FROM address WHERE domain_id in (select id from domaines where compte=$cuid);");
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
  function enum_domains() {
      global $db,$err,$cuid;
      $err->log("mail","enum_domains");
      $db->query("select d.id, d.domaine, count(a.id) as nb_mail FROM domaines d left join address a on a.domain_id=d.id where d.compte = $cuid group by d.id order by d.domaine asc;");
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
   * @param $count integer return no more than THAT much emails.
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

    $db->query("SELECT a.id, a.address, a.password, a.`enabled`, a.mail_action, d.domaine AS domain, m.quota, m.quota*1024*1024 AS quotabytes, m.bytes AS used, NOT ISNULL(m.id) AS islocal, a.type, r.recipients, m.lastlogin  
         FROM (address a LEFT JOIN mailbox m ON m.address_id=a.id) LEFT JOIN recipient r ON r.address_id=a.id, domaines d 
         WHERE $where AND d.id=a.domain_id 
         LIMIT $offset,$count;");
    if (! $db->next_record()) {
      $err->raise("mail",_("No mail found for this query"));
      return false;
    }
    $res=array();
    do {
      $details=$db->Record;
      // if necessary, fill the typedata with data from hooks ...
      if ($details["type"]) {
	$result=$hooks->invoke("mail_get_details",array($details["id"])); // Will fill typedata if necessary
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
    if (!filter_var($m,FILTER_VALIDATE_EMAIL) || (strpos($m,"..")!==false)  || (strpos($m,"/")!==false) ) {
      $err->raise("mail",_("The email you entered is syntaxically incorrect"));
      return false;
    }

    // Call other classes to check we can create it:
    $cancreate=$hooks->invoke('hooks_mail_cancreate',array($dom_id,$domain,$mail));
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
    $db->query("SELECT a.address, a.password, a.enabled, d.domaine AS domain, m.quota, m.quota*1024*1024 AS quotabytes, m.bytes AS used, NOT ISNULL(m.id) AS islocal, a.type, r.recipients, m.lastlogin, a.mail_action, m.mail_action AS mailbox_action FROM (address a LEFT JOIN mailbox m ON m.address_id=a.id) LEFT JOIN recipient r ON r.address_id=a.id, domaines d WHERE a.id=".$mail_id." AND d.id=a.domain_id;");
    if (! $db->next_record()) return false;
    $details=$db->Record;
    // if necessary, fill the typedata with data from hooks ...
    if ($details["type"]) {
      $result=$hooks->invoke("mail_get_details",array($mail_id)); // Will fill typedata if necessary
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
    if ($db->f("mailbox_action")!="OK" || $db->f("mail_action")!="OK") { // will be deleted soon ...
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
      $db->query("INSERT INTO mailbox SET address_id=".$mail_id.", path='".addslashes($path)."';");
    }
    if ($me["islocal"] && $islocal && $me["mailbox_action"]=="DELETE") {
      $db->query("UPDATE mailbox SET mail_action='OK' WHERE mail_action='DELETE' AND address_id=".$mail_id.";");
    }

    if ($islocal) {
      $db->query("UPDATE mailbox SET quota=".intval($quotamb)." WHERE address_id=".$mail_id.";");
    }

    $r=explode("\n",$recipients);
    $red="";
    foreach($r as $m) {
      $m=trim($m);
      if ($m && filter_var($m,FILTER_VALIDATE_EMAIL)) {
	$red.=$m."\n";
      }
    }
    $db->query("DELETE FROM recipient WHERE address_id=".$mail_id.";");
    if ($m) {
      $db->query("INSERT INTO recipient SET address_id=".$mail_id.", recipients='".addslashes($red)."';");
    }
    return true;
  }




  /* ############################################################ */
  /* ############################################################ */
  /* After that line, to be deleted / checked & co. */
  /* ############################################################ */
  /* ############################################################ */



  /* ----------------------------------------------------------------- */
  /** function used to list the first letter used in the list of the emails hosted in a specific domain
   * @param $domain_id integer the domain id.
   * @result an array of each letter used in mail hosted under the domain.
   */
  function enum_doms_mails_letters($domain_id) {
    global $err,$cuid,$db;
    $err->log("mail","enum_doms_mails_letters");
    $domain_id=intval($domain_id);
    $db->query("select distinct left(ad.address,1) as letter from address ad,where ad.domain_id = $domain_id ;");
    $res=array();
    while($db->next_record()) {
      $res[]=$db->f("letter");
    }
    return $res;
  }

  /* FIXME: check who is using that function and delete it when unused */
  function cancreate($dom_id, $email){
    return true;
  }
  
   /* FIXME: check who is using that function and delete it when unused */
  function form($mail_id) {
  }


   /* FIXME: check who is using that function and delete it when unused */
  function hooks_mail_cancreate($dom_id, $domain, $mail_arg) {
    global $db,$err;
    return true;
  }

  /**
  * @param : mail_id
  * fonction used to invoque the "hooks" corresponding to each mail relative classes
  * the two foreach are used to format the array the way we want.
  */
  function list_properties($mail_id) {
    global $err,$hooks;
    $err->log("mail","list_properties");
    $prop = $hooks->invoke("hooks_mail_properties_list",array($mail_id));
    $final=Array();
  
          /* Ici on :
             - trie/fait du ménage
             - prend en premier les properties non avancées
             - prend en second les properties avancées (donc en bas)
             - on pense a avoir un trie par label, histoire d'avoir une cohérence d'affichage
          */
    $f_simple=Array();
    $f_adv=Array();
    foreach ($prop as $k => $v ) {
      if ( empty($v) ) continue; // on continue si le tableau était vide
      if ( isset($v['label'] ) ) { // si c'est directement le tableau qu'on souhaite
        if ( isset($v['advanced']) && $v['advanced']) {
          $f_adv[] = $v;
        } else { // option simple
          $f_simple[] = $v;
        }
      } else {
        foreach ($v as $k2 => $v2 ) { // sinon on joue avec les sous-tableau
          if ( isset($v2['advanced']) && $v2['advanced']) {
            $f_adv[] = $v2;
          } else { // option simple
            $f_simple[]=$v2;
          }
        }
      }
    }
    $v_simple=usort($f_simple,'list_properties_order');
    $v_adv=usort($f_adv,'list_properties_order');
  
    $final=array_merge($f_simple,$f_adv);
  
    return $final;
  }
  



 /** 
  * mail_delete a mail address.
  * @param integer mail_id: unique mail identifier
	TODO: mail del
  */  
  function mail_delete($mail_id){
    global $db,$err,$admin;
    $err->log("mail","mail_delete");
  
   // $db->query("
  /*supprimer de la table address
	supprimer la mailbox si il yen a une.
	supprimer alias et redirection.
    supprimer les alias associé si il ne sont relié a aucunes autre addresses.
  */

  }

/**
* Export the mail information of an account 
* @return: str, chaine de caractere containing every usefull mail informations.
*
*/
function alternc_export_conf() {
     global $db,$err,$mail_localbox;
     $err->log("mail","export");
     $domain=$this->enum_domains();
     $str="<mail>\n";
     $onepop=false;
     foreach ($domain as $d) {
       $str.="  <domain>\n    <name>".xml_entities($d["domaine"])."</name>\n";
       $s=$this->enum_domain_mails($d["id"]);
       if (count($s)) {
         while (list($key,$val)=each($s)){
            $test=$this->mail_get_details($val['id']);
           $str.="    <address>\n";
           $str.="      <name>".xml_entities($val["address"])."</name>\n";
           $str.="      <enabled>".xml_entities($val["enabled"])."</enabled>\n";
           if(is_array($test["is_local"])){
             $str.="      <islocal>oui</islocal>\n";
             $str.="      <path>".$test["is_local"]["path"]."</path>\n";
             $str.="      <quota>".$test["is_local"]["quota"]."</quota>\n";
           }else{
             $str.="      <islocal>non</islocal>\n";
          }
          if(!empty($test["recipients"])){
              foreach($test["recipients"] as $recip){
                $str.="      <recipients>".$recip."<recipients>\n";
              }
          }
          if(!empty($test["alias"])){
              foreach($test["alias"] as $alias){
                $str.="      <alias>".$alias."<alias>\n";
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
