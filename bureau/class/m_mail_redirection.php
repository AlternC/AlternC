<?php 
/*
 $Id: m_mail_redirection.php author: squidly 
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
* This class handle emails redirections
*
* Copyleft {@link http://alternc.net/ AlternC Team}
*
* @copyright    AlternC-Team 2002-11-01 http://alternc.net/
*
*/

Class m_mail_redirection{

  var $enabled;
  var $advanced;

/**
* Function used to set the "visibility" of the property: meaning wheter the option is enabled (hence visible) and if it is part of the advanced options.
*/
  function m_mail_redirection(){
    $this->enabled=variable_get('mail_redirection_enabled',null);
    if (is_null($this->enabled)) { // if not configuration var, setup one (with a default value)
       variable_set('mail_redirection_enabled',true,'To enable or disable the alias module in the mail edit page');
       $this->enabled=true;
    }

   $this->advanced=variable_get('mail_redirection_advanced',null);
   if (is_null($this->advanced)) { // if not configuration var, setup one (with a default value)
     variable_set('mail_redirection_advanced',true,'To place the redirection option in the advanced area');
     $this->advanced=true;
   }
  }
 

 /**
  * function listing each redirections associated with a mail on th ecurrent domain
  * @param integer mail_id 
  * @return ani indexed array of all the recipients of a mail address
  */
  function list_recipients($mail_id) {
    global $db,$err;
    $err->log("mail_redirection","list_recipient");
    $db->query("SELECT r.id, r.recipients from recipient r where address_id = ".intval($mail_id)." order by r.recipients ;");
    $recipients = Array();
    while ($db->next_record()) {
      $recipients[$db->f("id")] = $db->f("recipients");
    }
    return $recipients;
  }

 /**
  * Fonction used to insert the redirections specified by te panel
  * @param integer $mail_id
  * @param array $recipients : an array contening each and every domain specific mail redirection for a given mail. 
  * @return: true if everything went ok, false if one are more recipients could not be added
  */
 function setredirection($mail_id, $recipients){
   global $db,$err;
   $err->log("mail_redirection","setredirection");

   $all_correct=true;
   
   $recipients=array_unique($recipients);
   foreach($recipients as $k => $v){
     if(checkmail($recipients[$k]) != 0){
       unset($recipients[$k]);
       $all_correct=false;
     }
   }
   
   $recip_clean=array_values($recipients);

   $rec_tmp=implode("\n",$recip_clean);
   $db->query("INSERT INTO recipient (address_id,recipients) values ($mail_id,'$rec_tmp') ON DUPLICATE KEY UPDATE recipients='$rec_tmp' ;");
   if($all_correct == false){
     return false;
   }else{
     return true;
   }
 }


 /*
  * Function using list_recipient() to get the list of recipient of a mail and turning it into an array
  * @param integer $mail_id : mail unique identifier.
  * @return array 
  */
 function recipients_get_array($mail_id) {
   global $mail,$err;
   $err->log("mail_redirection","recipient_get_array");
   $r = $this->list_recipients($mail_id);

   foreach ($r as $b) {$v = explode("\n", $b);} // Only one pass, this array is a 1 row array
   if (empty($v)) $v=Array();
   foreach ($v as $k => $f) { if (empty($f)) unset($v[$k]); } // clear empty entry
   sort($v);

   return $v;
 }
  
  function recipients_set_array($mail_id, $recipients){
    global $db,$err,$mail;
  }


  function form($mail_id) {
    global $mail, $err, $mail_redirection;
    include('mail_redirection_edit.inc.php');
  }

  /* 
   * hooks called to list a given mail properties
   * @param: the id of the mail in question
   * @return: an hashtable of every information usefull to edit the mail if it is part of the class 
   *   including a url to the edition page of the propertie in question ( here local hosting of a mail)
   *   if the mail cannot be a localbox because of some of it's properties the return is NULL, thus not      
   *   displayed in the properties listing page.
   */ 
  function hooks_mail_properties_list($mail_id){  
    global $db, $mail, $err;
    $err->log("mail_redirection","mail_properties_list");
    $return = array (
        "label"          => "redirection",
        "short_desc"     => _("Redirection"),
        "human_desc"     => _("Send a copy of incoming emails to another mail address"),
        "url"            => "mail_redirection_edit.php?mail_id=$mail_id",
	"form_param"     => Array($mail_id),
        "class"          => "mail_redirection",
        "advanced"  => $this->advanced,
        "pass_required"  => false
        );

    return $return; 
  }

 //FIXME fonction de suppresion.

}

?>
