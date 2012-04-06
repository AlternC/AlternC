<?php 

/*
 $Id: m_mail_localbox.php author: squidly 
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
 Original Author of file: Benjamin Sonntag, Franck Missoum
 Purpose of file: Manage Email accounts and aliases.
 ----------------------------------------------------------------------
*/

/**
* This class handle emails local mailboxes
*
* Copyleft {@link http://alternc.net/ AlternC Team}
*
* @copyright    AlternC-Team 2002-11-01 http://alternc.net/
*
*/

Class m_mail_localbox{
  var $enabled;
  var $advanced;



/**
* Function used to set the "visibility" of the property: meaning wheter the option is enabled (hence visible) and if it is part of the advanced options.
*/
  function m_mail_localbox(){
    $this->enabled=variable_get('mail_localbox_enabled',null);
      if (is_null($this->enabled)) { // if not configuration var, setup one (with a default value)
        variable_set('mail_localbox_enabled',true,'To enable or disable the alias module in the mail edit page');
        $this->enabled=true;
      }

    $this->advanced=variable_get('mail_localbox_advanced',null);
      if (is_null($this->advanced)) { // if not configuration var, setup one (with a default value)
        variable_set('mail_localbox_advanced',false,'To place the alias option in the advanced area');
        $this->advanced=false;
      }
  }

  /*
   * Set a localbox
   * @param integer $mail_id
   */
  function set_localbox($mail_id){
    global $db, $err;
    $err->log("localbox","set_localbox");
    $path="mail/";
    //if(!$db->query("select distinct left(ad.address,1) as letter,d.domaine from address ad,where ad.id = $mail_id ;"));
    if(!$db->query("select distinct left(ad.address,1) as letter,ad.address ,d.domaine from address ad, domaines d where ad.domain_id = d.id  and ad.id = $mail_id order by letter;"));

    if(! $db->next_record()){
        return null;
    }
    $path="/var/alternc/mail/".$db->f('letter')."/".$db->f('address')."_".$db->f('domaine');
    //FIXME faire un touch de la maildir si dovecot ne sait pas le faire.
    if(!$db->query("INSERT into mailbox (address_id,path) values ($mail_id,'$path');"));     

  }

  /*
   * Set a localbox
   * @param integer $mail_id
   */
  function unset_localbox($mail_id){
    global $db, $err;
    $err->log("localbox","set_localbox");
    if(!$db->query("DELETE from  mailbox where address_id=$mail_id;"));     

  }


  /*
     hooks called by the mail class, it is used to verify that a given mail is not already in the adress table
     in wich case we can create it so the 
     @param: dom_id=domain in use, mail_arg= mail address waiting to be created
     @result: an hashtable contening the state ( success /failure, un case of success) the id of the created mail, and an error message if something went wrong.
   */
  function hooks_mail_cancreate($dom_id, $mail_arg){
    global $db, $err, $cuid;  
    $err->log("m_mail_localbox","hooks_mail_cancreate");    
    $return = array ( 
            "state" => true,
            "mail_id" => null,
            "error" => "");

   return $return;
  }  


  function form($mail_id) {
    global $mail, $err;
    include('mail_localbox_edit.inc.php');
  }

  /* hooks called to list a given mail properties
   * @param: the id of the mail being processed
   * @return: an hashtable of every information usefull to edit the mail if it is part of the class 
   * including a url to the edition page of the propertie in question ( here local hosting of a mail)
   * if the mail cannot be a localbox because of some of it's properties the return is NULL, thus not       displayed in the properties listing page.
   */ 
  function hooks_mail_properties_list($mail_id){    
    global $db, $err;
    $err->log("mail_localbox","mail_properties_list");
    $return = array (
        "label"       => "localbox",
        "short_desc"  => _("Local mailbox"),
        "human_desc"  => _("Actually disabled.<br/>To have your mail stored on the server.<br/><i>You can access them remotely with the webmail, IMAP or POP</i>"),
        "url"         => "mail_localbox_edit.php",
        "form_param"  => Array($mail_id),
        "class"       => 'mail_localbox',
        "pass_required" => true,
        "advanced" => $this->advanced
        );


    // on recherche si la boite est deja presente en tant que boite locale
    $db->query("select address_id from mailbox where address_id=$mail_id;");

    // Si pas d'entrée dans mailbox, on retourne directement le Array
    if(! $db->next_record()){
      $return['url'] .= "?mail_id=$mail_id";
      return $return;
    }
    // Sinon, on le met à jour avant
    $return["is_local"]= true;
    $return["object_id"]= $db->f('address_id');
    $return["human_desc"] = _("Actually enabled.<br/>Your mails are stored on the server.<br/><i>You can access them remotely with the webmail, IMAP or POP</i>");

    // On met à jour l'URL
    $return['url'] .= "?mail_id=$mail_id";

    return $return;
  }

 
  /* Function testing if a given mail id is hosted as a localbox on the domain or not
  *  @param: mail_id
  *  @return: an indexed array of localbox usefull informations
  */
  function details($mail_id){
  global $db,$err;
  $err->log("mail_localbox","details");
  $mail_local = array (
        "path" => "",
        "quota" => null,
        "delivery" => "");

  $db->query("select path, quota, delivery from mailbox where address_id=$mail_id;");
  if (! $db->next_record()) return false;

  $mail_local["path"]=$db->f("path");  
  $mail_local["quota"]=$db->f("quota");  
  $mail_local["delivery"]=$db->f("delivery");  
  return $mail_local;
  }

 
}

?>
