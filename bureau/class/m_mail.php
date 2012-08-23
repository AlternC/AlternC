<?php
/*
 $Id: m_mail.php,v 2.00 2012/03/12 06:26:16 anarcat Exp $
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
* This class handle emails (pop and/or aliases and even wrapper for internal
* classes) of hosted users.
*
* Copyleft {@link http://alternc.net/ AlternC Team}
*
* @copyright    AlternC-Team 2002-11-01 http://alternc.net/
*
*/
class m_mail {

  /** domain list
   * @access private
   */
  var $domains;


  /* ----------------------------------------------------------------- */
  /**
   * Constructor
   */
  function m_mail() {
  }


  /* ----------------------------------------------------------------- */
  /**
   * Quota list (hook for quota class)
   */
  function alternc_quota_names() {
    return "mail";
  }

  function alternc_get_quota($name) {
    global $db,$err,$cuid;
    if ($name=="mail") {
      $err->log("mail","getquota");
      //$db->query("SELECT COUNT(*) AS cnt FROM address WHERE domain_id in(select id from domaines where compte=$cuid);");
      $db->query("SELECT COUNT(a.id) AS cnt FROM address a, domaines d WHERE a.domain_id =d.id and d.compte=$cuid group by a.id;");
      $db->next_record();
      return $db->f("cnt");
    }
  }


  /**
   * Password kind used in this class (hook for admin class)
   */
  function alternc_password_policy() {
    return array("pop"=>"POP/IMAP account passwords");
  }


  /* ----------------------------------------------------------------- */
  /** Returns the list of mail-hosted domains for a user
   * @return array indexed array of hosted domains
   */
  function enum_domains() {
      global $db,$err,$cuid;
      $err->log("mail","enum_domains");
      $db->query("select d.id, d.domaine, count(a.id) as nb_mail FROM domaines d left join address a on a.domain_id=d.id where d.compte = $cuid group by d.id order by d.domaine asc;");
      while($db->next_record()){
          $this->enum_domains[]=$db->Record;
      }
      //print_r("<pre>");print_r($this->enum_domains);die();
      return $this->enum_domains;

  }

  /* function used to list every mail address hosted on a domain.
   * @param : the technical domain id.
   * @result : an array of each mail hosted under the domain.
   */
  function enum_domain_mails($dom_id = null){
    global $db,$err,$cuid;
    $err->log("mail","enum_domains_mail");
    $db->query("select * from address where domain_id=$dom_id order by address asc;");
    if (!$db->num_rows()) {
      return false;
    }
    while($db->next_record()){
      $this->enum_domain_mails[]=$db->Record;
    }
    return $this->enum_domain_mails;
  }

  function enum_doms_mails_letters($mail_id) {
    global $err,$cuid,$db;
    $err->log("mail","enum_doms_mails_letters");
    //$db->query("select distinct left(ad.address,1) as letter from address ad, domaines d where ad.domain_id = d.id and d.compte='$cuid' order by letter;");
    $db->query("select distinct left(ad.address,1) as letter from address ad,where ad.id = $mail_id ;");
    $res=array();
    while($db->next_record()) {
      $res[]=$db->f("letter");
    }
    return $res;
  }

  /*
   * Function used to insert a new mail into de the db
   * @param: a domain_id (linked to the user ) and the left part of mail waiting to be inserted 
   * @return: an hashtable containing the database id of the newly created mail, the state ( succes or failure ) of the operation, 
   * and an error message if necessary.
   * TODO piensar a enlever la contrainte d'unicité sur le champs address et en rajouter une sur adrresse+dom_id.
   */ 
  function create($dom_id, $mail_arg,$dom_name){
    global $mail,$err,$db,$cuid,$quota;
    $err->log("mail","create");
  
    $return = array ( 
      "state" => true,
      "mail_id" => null,
      "error" => "OK");

    $m=$mail_arg."@".$dom_name;
    if(checkmail($m) != 0){
      $return["state"]=false;
      $return["error"]="erreur d'appel a cancreate";
      return $return;
    }
  
    $return=$mail->cancreate($dom_id, $mail_arg);
    //Si l'appel échoue
    if(!$return ){
      $return["state"]=false;
      $return["error"]="erreur d'appel a cancreate";
      return $return;
    }
    if($return["state"]==false){
      $return["error"]="erreur d'appel a cancreate";
      return ($return);
    }    
    // check appartenance domaine      
    $test=$db->query("select id from domaines where compte=$cuid and id=$dom_id;");
  
    if(!$db->next_record($test)){
        $return["state"]= false;
        $return["error"]=" hophophop tu t'es prix pour un banquier ouquoi ?";
        return $return;
    }

    // Check the quota :
    if (!$quota->cancreate("mail")) {
      $err->raise("mail",10);
      return false;
    }
  
		$db->query("insert into address (domain_id, address) VALUES ($dom_id, '$mail_arg');");
		$test=$db->query("select id from address where domain_id=$dom_id and address=\"$mail_arg\";");

    $db->next_record();
    
    $return["mail_id"]=$db->f("id");
  
    return $return;
  }

/*
 *Function used to check if a given mail address can be inserted a new mail into de the db
 *@param: a domain_id (linked to the user ) and the left part of mail waiting to be inserted 
 *@return: an hashtable containing the database id of the newly created mail, the state ( succes or failure ) of the operation, 
 *and an error message if necessary.
 */
  function cancreate($dom_id,$mail_arg){
    global $db,$err,$cuid,$hooks;
    $err->log("mail","cancreate");
  
    $return = array ( 
        "state" => true,
        "mail_id" => null,
        "error" => "");
  
    $return2 = array ();
    $return2 = $hooks->invoke('hooks_mail_cancreate',array($dom_id,$mail_arg));
  
    foreach($return2 as $tab => $v){
      if($v["state"] != true){
        //print_r($tab);
        $return["state"]=false;
        $return["error"]="erreur lors du check de la classe $tab";
        return $return; 
      }
    }
    return $return; 
  }

  function form($mail_id) {
    global $mail, $err;
    include('mail_edit.inc.php');
  }


  /*
   * hooks called by the cancreate function
   * @param: a domain_id (linked to the user ) and the left part of mail waiting to be inserted 
   * @return: an hashtable containing the database id of the newly created mail, the state ( succes or failure ) of the operation, 
   * and an error message if necessary.
   *
   */ 
  function hooks_mail_cancreate($dom_id, $mail_arg) {
    global $db,$err;
    $err->log("mail","hooks_mail_cancreate");
  
    $return = array ( 
        "state" => true,
        "mail_id" => null,
        "error" => "");
  
    $db->query("select count(*) as cnt from address where domain_id=$dom_id and address=\"$mail_arg\";"); 
  
    if($db->next_record()){
      //if mail_arg not already in table "address"
      if( $db->f("cnt") == "0") {
        return $return;
      }else{
        $return["state"] = false;
        $return["error"]="mail existe deja";
        return $return;
      }
    } 
    $return["error"]="erreur de requête";
    return $return;
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
  
  /* function used to get every information at our disposal concerning a mail.
  *  @param: $mail_id, $recur (used to stop the fonction correctly when called from list alias.
  *  @return: an hashtable of every usefull informations we can get about a mail.
  */

  function mail_get_details($mail_id, $recur=true){
    global $db, $err, $mail_redirection,$mail_alias, $mail_localbox;
    $err->log("mail","mail_get_details");
    
    $details = array (
        "address_id" => "",
        "address" => "",
        "domain" => "", 
        "address_full" => "",
        "password" => "",
        "enabled" => false,
        "is_local" => Array(),
        "recipients" => Array(),
        "alias" => Array(),
        );
  
    //on recupere les info principales de toutes adresses
    $db->query("select a.address,a.password,a.enabled, d.domaine from address a, domaines d where a.id=$mail_id and d.id=a.domain_id;");
  
    // Return if no entry in the database
    if (! $db->next_record()) return false;
  
    $details["address_id"]  =$mail_id;
    $details["address"]  =$db->f("address");
    $details["password"]  =$db->f("password");
    $details["enabled"]  =$db->f("enabled");
    $details["domain"]  =$db->f("domaine");
    $details["address_full"]=$details["address"].'@'.$details["domain"];
  
    if ($recur) {
      // Get some human-usefull informations
      $details["is_local"]=$mail_localbox->details($mail_id);
      $details["recipients"] = $mail_redirection->list_recipients($mail_id);
      $details["alias"] = $mail_alias->list_alias($details['address_full']);
    }
    
    return $details;
  }

 /** 
  * activate a mail address.
  * @param integer mail_id: unique mail identifier
  */  
  function enable($mail_id){
    global $db,$err;
    $err->log("mail","enable");
    if( !$db->query("UPDATE address SET enabled=1 where id=$mail_id;"))return false;
  }

 /** 
  * disable a mail address.
  * @param integer mail_id: unique mail identifier
  */  
  function disable($mail_id){
    global $db,$err;
    $err->log("mail","enable");
    if( !$db->query("UPDATE address SET enabled=0 where id=$mail_id;")) return false;
  }

 /** 
  * setpasswd a mail address.
  * @param integer mail_id: unique mail identifier
  */  
  function setpasswd($mail_id,$pass,$passwd_type){
    global $db,$err,$admin;
    $err->log("mail","setpasswd");
    if(!$admin->checkPolicy("pop",$mail_full,$pass)) return false;
    if(!$db->query("UPDATE address SET password='"._md5cr($pass)."' where id=$mail_id;")) return false;
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
