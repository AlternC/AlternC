<?php

/**
 * Authentication API used by server to authenticate a user using a 
 * SHARED SECRET (ApiKey)
 */
class Alternc_Api_Auth_Sharedsecret implements Alternc_Api_Auth_Interface {
    

  private $db; // PDO object

  const ERR_INVALID_ARGUMENT = 1111801;
  const ERR_INVALID_SECRET = 1111802;
  const ERR_INVALID_LOGIN = 1111803;
  const ERR_DISABLED_ACCOUNT = 1111804;


  /**
   * Constructor of the Shared Secret Api Auth
   *
   * @param $service an Alternc_Api_Service object
   * @return create the object
   */
  function __construct($service) {

    if (!($service instanceof Alternc_Api_Service))
      throw new \Exception("Invalid argument (service)",ERR_INVALID_ARGUMENT);

    $this->db = $service->getDb();

  } // __construct


  /**
   * Authenticate a user
   *
   * @param $options options, depending on the auth scheme, including uid for setuid users
   *   here, login is the alternc username, and secret is a valid shared secret for this user.
   * @return an Alternc_Api_Token
   */
  function auth($options) {

    if (!isset($options["login"]) || !is_string($options["login"])) {
      throw new \Exception("Missing required parameter login", self::ERR_INVALID_ARGUMENT);
    }
    if (!isset($options["secret"]) || !is_string($options["secret"])) {
      throw new \Exception("Missing required parameter secret", self::ERR_INVALID_ARGUMENT);
    }
    if (!preg_match("#^[0-9a-zA-Z]{32}$#",$options["secret"])) {
      return new Alternc_Api_Response( array("code" => self::ERR_INVALID_SECRET, "message" => "Invalid shared secret syntax") );
    }

    if (!preg_match("#^[0-9a-zA-Z-]{1,32}$#",$options["login"])) { // FIXME : normalize this on AlternC !!!
      return new Alternc_Api_Response( array("code" => self::ERR_INVALID_LOGIN, "message" => "Invalid login") );
    }

    $stmt = $this->db->prepare("SELECT m.enabled,m.uid,m.login,m.su FROM membres m, sharedsecret s WHERE s.uid=m.uid AND m.login=? AND s.secret=?;");
    $stmt->execute(array($options["login"],$options["secret"]) );
    $me=$stmt->fetch(PDO::FETCH_OBJ);
    if (!$me) 
      return new Alternc_Api_Response( array("code" => self::ERR_INVALID_AUTH, "message" => "Invalid shared secret") );
    if (!$me->enabled) 
      return new Alternc_Api_Response( array("code" => self::ERR_DISABLED_ACCOUNT, "message" => "Account is disabled") );

    return Alternc_Api_Token::tokenGenerate(
					    array("uid"=>(int)$me->uid, "isAdmin"=>($me->su!=0) ), 
					     $this->db
					     );
  }


  /** 
   * instructions on how to use this Auth class
   * @return array("fields" => array("fields to send, required or not"), "description" => "description of this auth")
   */
  function instructions() {
    return array("fields" => array("login" => "AlternC user account", "secret" => "API Key, Shared secrets, valid for this account, stored in sharedsecret table."),
		 "description" => "Authenticate against an Api Key, also called SharedSecret. distinct from the account's password, can be plenty and revoked independently"
		 );
  }


} // class Alternc_Api_Auth_Sharedsecret

