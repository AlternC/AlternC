<?php 

/* 
 * Proof of concept of what a new feature look like with the new mail interface
 *
**/

Class m_mail_jabber{
  var $advanced;
  var $enabled;

  function m_mail_jabber(){
    // Get configuration var
    $this->enabled=variable_get('mail_jabber_enabled',null);
    $this->advanced=variable_get('mail_jabber_advanced',null);

    // Setup the vars if there aren't any
    if (is_null($this->enabled)) { 
      variable_set('mail_jabber_enabled',true,'To enable or disable the Jabber module in the mail edit page');
      $this->enabled=true;
    }

    if (is_null($this->advanced)) { 
      variable_set('mail_jabber_advanced',true,'To choose the category of Jabber in the mail edit page');
      $this->advanced=true;
    }

  }

  /**
    * Hooks called by the mail class, it's 
    * used to verify that a given mail is 
    * allowed to be created by all the class friends of mail
    *
    * @param dom_id integer domain id of the target mail
    * @param mail_arg string left part of '@' of the target mail
    * @return array a hashtable contening the statei (boolean) and an error message
    *
   */
  function hooks_mail_cancreate($dom_id, $mail_arg){
    global $db, $err, $cuid;  
    $err->log("m_mail_jabber","hooks_mail_cancreate");    
    $return = array ( 
       "state"   => true, // Do we allow this creation ?
       "error"   => "");  // Error message (txt)

    // Return our informations
    return $return;
  }  

  /**
    * Hooks called to list a given mail properties
    * @param mail_id the id of the mail being processed
    * @return false, or an hashtable of the usefull information
    *
   **/ 
  function hooks_mail_properties_list($mail_id){    
    global $db, $err;
    $err->log("mail_jabber","mail_properties_list");

    // Return if this feature isn't enabled
    if (!$this->enabled) return false;

    // Setup the object
    $return = array (
        "label"         => "jabberdemo",      // an internal label 
        "short_desc"    => _("Jabber Demo"),  // A human short description
        "human_desc"    => _("This is just a demo.<br/>Look at m_mail_jabber.php"), // A human long description
        "url"           => "javascript:alert('Ici un renvoie vers le formulaire adequat de cette entrée.');", // The URL to go
        "pass_required" => true, 	            // This feature require the mail to have a global password ?
        "advanced"      => $this->advanced,   // Is this an advanced feature ?
	      );

    /* We can return many array merged to have many 
     * entry (with different informations, for example 
     * different description or target URL), to list many
     * action directly in the page
    **/
    // To view an example, uncomment next line
    // $return=Array($return,$return,$return);
    return $return;
  }

}

?>
