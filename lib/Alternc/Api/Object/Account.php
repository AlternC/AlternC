<?php

/**
 * Account Api of AlternC, used by alternc-api package
 */
class Alternc_Api_Object_Account {
    
  const ERR_INVALID_ARGUMENT = 1115101;
  const ERR_ALTERNC_FUNCTION = 1115102;
  const ERR_NOT_FOUND = 1115103;

  function __construct($service) {
    global $admin,$cuid;
    if (!($service instanceof Alternc_Api_Service)) {
      throw new \Exception("Bad argument: service is not an Alternc_Api_Service", self::ERR_INVALID_ARGUMENT);
    }
    // We store the global $cuid to AlternC legacy classes
    $cuid=$service->token->uid;
    // We use the global $ssl from AlternC legacy classes
    $this->admin=$admin;
  }

  
  /** API Method from legacy class method admin->add_mem()
   * @param $options a hash with parameters transmitted to legacy call
   * mandatory parameters: login, pass, nom, prenom, mail, 
   * non-mandatory: canpass, type, duration, notes, force, create_dom, db_server_id
   * @return Alternc_Api_Response whose content is the newly created UID
   */
  function add($options) {      
      $mandatory=array("login","pass","nom","prenom","mail");
      $defaults=array("canpass"=>1, "type"=>"default","duration"=>0, "notes"=>"", "force"=>0, "create_dom"=>"");
      $missing="";
      foreach ($mandatory as $key) {
          if (!isset($options[$key])) {
              $missing.=$key." ";
          }
      }
      if ($missing) {
          return new Alternc_Api_Response( array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: ".$missing) );
      }
      foreach ($defaults as $key => $value) {
          if (!isset($options[$key])) {
              $options[$key]=$value;
          }
      }
      if (!isset($options["db_server_id"])) {
          $stmt = $this->db->prepare("SELECT MIN(db_servers.id) AS id FROM db_servers;");
          $stmt->execute();
          $me=$stmt->fetch(PDO::FETCH_OBJ);
          $options["db_server_id"]=$me->id;
      }
      $uid=$this->admin->add_mem($options["login"], $options["pass"], $options["nom"], $options["prenom"], $options["mail"], 
              $options["canpass"], $options["type"], $options["duration"], $options["notes"], $options["force"], 
              $options["create_dom"], $options["db_server_id"]);
      if (!$uid) {
          return $this->alterncLegacyErrorManager();
      } else {
          return new Alternc_Api_Response( array("content" => $uid ) );
      }
  }


  /** API Method from legacy class method admin->update_mem()
   * @param $options a hash with parameters transmitted to legacy call
   * mandatory parameters: nom, prenom, mail, 
   * non-mandatory: pass, canpass, type, duration, notes, force, create_dom, db_server_id
   * @return Alternc_Api_Response whose content is the updated UID
   */
//    function update_mem($uid, $mail, $nom, $prenom, $pass, $enabled, $canpass, $type='default', $duration=0, $notes = "",$reset_quotas=false) {
  function update($options) {      
      $defaults=array("nom","prenom","mail","canpass", "enabled","type","duration", "notes");
      if (!isset($options["uid"])) {
          return new Alternc_Api_Response( array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: UID") ); 
      }
      $uid=intval($options["uid"]);
      $old=$this->admin->get($uid);
      if (!$old) {
          return new Alternc_Api_Response( array("code" => self::ERR_NOT_FOUND, "message" => "User not found") ); 
      }
      
      foreach ($defaults as $key) {
          if (!isset($options[$key])) {
              $options[$key]=$old[$key];
          }
      }
      if (!isset($options["pass"])) $options["pass"]="";
      $uid=$this->admin->update_mem($uid, $options["mail"], $options["nom"], $options["prenom"], $options["pass"], 
              $options["enabled"], $options["canpass"], $options["type"], $options["duration"], $options["notes"]);
      if (!$uid) {
          return $this->alterncLegacyErrorManager();
      } else {
          return new Alternc_Api_Response( array("content" => $uid ) );
      }
  }

  
  /** API Method from legacy class method admin->del_mem()
   * @param $options a hash with parameters transmitted to legacy call
   * mandatory parameters: uid
   * @return Alternc_Api_Response TRUE if the account has been deleted.
   */
  function del($options) {      
      if (!isset($options["uid"])) {
          return new Alternc_Api_Response( array("code" => self::ERR_ALTERNC_FUNCTION, "message" => "Missing or invalid argument: UID") );
      }
      $result=$this->admin->del_mem(intval($options["uid"]));
      if (!$result) {
          return $this->alterncLegacyErrorManager();
      } else {
          return new Alternc_Api_Response( array("content" => true ) );
      }
  }

  
  /** API Method from legacy class method admin->get_list()
   * @param $options a hash with parameters transmitted to legacy call
   * non-mandatory parameters: ONE OF: 
   *  uid(strict), login(like %%), domain(like %%), creator(strict, by uid), 
   *  Any of: offset(int=0), count(int=+inf)
   * @return Alternc_Api_Response An array with all matching users informations as hashes
   */
  function find($options) {
      $result=false;
      if (!$result && isset($options["uid"])) {
          $result=$this->admin->get(intval($options["uid"]));
          if ($result) $result=array($result);
      }
      if (!$result && isset($options["login"])) {
          $result=$this->admin->get_list(1/*ALL*/, "", $options["login"], "login");
      }
      if (!$result && isset($options["domain"])) {
          $result=$this->admin->get_list(1/*ALL*/, "", $options["domain"], "domaine");
      }
      if (!$result && isset($options["creator"])) {
          $result=$this->admin->get_list(1/*ALL*/, intval($options["creator"]));
      }
      if (!$result) {          // everybody
          $result=$this->admin->get_list(1/*ALL*/, "");
      }
      
      if (!$result) {
          return $this->alterncLegacyErrorManager();
      } else {
          $offset=-1; $count=-1;
          if (isset($options["count"])) $count=intval($options["count"]);
          if (isset($options["offset"])) $offset=intval($options["offset"]);
          if ($offset!=-1 || $count!=-1) {
              if ($offset<0 || $offset>count($result)) $offset=0;
              if ($count<0 || $count>1000) $count=1000;
              $result=  array_slice($result, $offset, $count);
          }
          return new Alternc_Api_Response( array("content" =>$result) );
      }
  }



  /** return a proper Alternc_Api_Response from an error class and error string 
   * from AlternC legacy class
   */
  private function alterncLegacyErrorManager() {
    global $err;
    return new Alternc_Api_Response( array("code" => self::ERR_ALTERNC_FUNCTION, "message" => "[".$err->clsid."] ".$err->error) );
  }


} // class Alternc_Api_Object_Ssl