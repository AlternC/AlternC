<?php

/**
 * Authentication API used by server to authenticate a user using a 
 * SHARED SECRET (ApiKey)
 */
class Alternc_Api_Auth_Sharedsecret implements Alternc_Api_Auth_Interface {
    

  private $db; // PDO object

  const ERR_INVALID_ARGUMENT = 1111801;

  /**
   * Constructor of the Shared Secret Api Auth
   *
   * @param $service an Alternc_Api_Service object
   * @return create the object
   */
  function __constructor($service) {

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
      throw new \Exception("Invalid shared secret", self::ERR_INVALID_ARGUMENT);
    }

    if (!preg_match("#^[0-9a-zA-Z-]{1,32}$#",$options["login"])) { // FIXME : normalize this on AlternC !!!
      throw new \Exception("Invalid login", self::ERR_INVALID_LOGIN);
    }

    $stmt = $db->query("SELECT m.enabled,m.uid,m.login,m.su FROM membres m, sharedsecret s WHERE s.uid=m.uid AND m.login=? AND s.secret=?;",array($options["login"],$options["secret"]),PDO::FETCH_CLASS);
    $me=$stmt->fetch();
    if (!$me) 
      return new Alternc_Api_Response(array("code"=>ERR_INVALID_AUTH, "message" => "Invalid shared secret"));
    if (!$me->enabled) 
      return new Alternc_Api_Response(array("code"=>ERR_DISABLED_ACCOUNT, "message" => "Account is disabled"));

    return Alternc_Api_Token::tokenGenerate(
					     array("uid"=>$me->uid, "isAdmin"=>($me->su!=0) ), 
					     $this->db
					     );
  }


} // class Alternc_Api_Auth_Sharedsecret

