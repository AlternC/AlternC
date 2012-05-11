<?php 
/*
 $Id: m_mail_alias.php author: squidly 
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
* This class handle emails aliases
*
* Copyleft {@link http://alternc.net/ AlternC Team}
*
* @copyright    AlternC-Team 2002-11-01 http://alternc.net/
*
*/

Class m_mail_alias{
  var $enabled;
  var $advanced;

/**
* Function used to set the "visibility" of the property: meaning wheter the option is enabled (hence visible) and if it is part of the advanced options.
*/
  function m_mail_alias(){
   $this->enabled=variable_get('mail_alias_enabled',null);
     if (is_null($this->enabled)) { // if not configuration var, setup one (with a default value)
       variable_set('mail_alias_enabled',true,'To enable or disable the alias module in the mail edit page');
       $this->enabled=true;
     }

   $this->advanced=variable_get('mail_alias_advanced',null);
     if (is_null($this->advanced)) { // if not configuration var, setup one (with a default value)
       variable_set('mail_alias_advanced',true,'To place the alias option in the advanced area');
       $this->advanced=true;
     }
  }
 
 /*
  * function used to list every aliases ( aka local redirections ) of a given mail address
  * param: mail address aliased.
  * return : an array containing every alias of a given mail on every domain related to the current user.
  *
  */

  function list_alias($mail_address) {
    global $db, $mail, $cuid,$err;
    $err->log("mail_alias","list_alias");
    $db->query("SELECT r.id as id, r.address_id from recipient r,address a, domaines d where r.recipients REGEXP '^[[:space:]]*".mysql_real_escape_string($mail_address)."[[:space:]]*$'and r.address_id=a.id and a.domain_id=d.id and d.compte = $cuid;");
    $rcp = Array();
  while ($db->next_record()) {
    $rcp[$db->f("id")] = $db->f("address_id");
  }
  foreach ($rcp as $k => $v) {
    $rcp[$k] = $mail->mail_get_details($v, false);
  }
    return $rcp;
  }

  function form($mail_id, $edit_id) {
    global $mail,$err;
    if ($edit_id) {
      //echo "<a href='mail_redirection_edit.inc.php?mail_id=$edit_id'>";__("Edit");echo "</a>";
      echo "<a href='mail_properties.php?mail_id=$edit_id'>";__("Edit");echo "</a>";
    } else {
      include('mail_alias_create.inc.php');
    }
  }


 /**
  *hooks called to list a given mail properties
  *@param: the id of the mail in question
  *@return: an hashtable of every information usefull to edit the mail if it is part of the class 
  *including a url to the edition page of the propertie in question ( here local hosting of a mail)
  *if the mail cannot be a localbox because of some of it's properties the return is NULL, thus not     *displayed in the properties listing page.
  */ 
  function hooks_mail_properties_list($mail_id){  
    global $db, $mail, $err;
    $err->log("mail","mail_properties_list");
    $val = array (
      "label"         => "alias",
      "short_desc"    => _("Alias"),
      "human_desc"    => _("To add an alias to this mail address.<br/><i>You must have the domain's alias setup in your AlternC account.</i>"),
      "url"           => "mail_alias_create.php?mail_id=$mail_id",
      "class"         => "mail_alias",
      "form_param"    => Array($mail_id,false),
      "advanced"      => $this->advanced,
      "pass_required" => false
      );

    if (!$details = $mail->mail_get_details($mail_id, true)) return Array();
    $return=Array();
    $return[]=$val; // To be able to add a new alias
    foreach ($details['alias'] as $k => $v ) {
      $tmp = $val;
      $tmp['url'] = "mail_redirection_edit.php?mail_id=".$v['address_id'];
      $tmp["form_param"] = Array($mail_id,$v['address_id']);
      $tmp['short_desc'] = sprintf(_("Alias of %s"),$v['address_full']);
      $tmp['human_desc'] = sprintf(_("All the mails sent to %s will be received here."),$v['address_full']);
      $return[] = $tmp;
    }


  return $return; 

  }

 /*
  * Function inserting an alias in the recipient table
  * @param integer alias_id : unique id of the alias just inserted in address table.
  * @param string mail_arg : the mail being aliased.
  * @return true if the alias was inserted ,false if there is an error or if alias al* ready in the base
  */
  function setalias($alias_id,$mail_arg){
    global $db, $err;
    $err->log("mail","setalias"); 
    
    $compare=$this->list_alias($mail_arg);
    $db->query("select address from address where id=$alias_id;");
    $db->next_record();
    $mail_left=$db->f('address');
    foreach($compare as $k => $v){
     if($v['address'] === $mail_left)
         return false;
    }
    $db->query("INSERT INTO recipient (address_id, recipients) VALUES ($alias_id,'$mail_arg');");
    return true;
    


  }

}

?>
