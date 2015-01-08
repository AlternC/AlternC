<?php

/* TODO: implements logger !
 */

/**
 * Service API used by server to export API methods
 */
class Alternc_Api_Service {
    

  public $db; // PDO object
  private $loggerList; // List of loggers
  private $allowedAuth; // list of allowed authenticators
  public $token; // Token (useful for called classes)

  const ERR_INVALID_ARGUMENT = 111801;
  const ERR_METHOD_DENIED = 111802;
  const ERR_INVALID_ANSWER = 111803;
  const ERR_SETUID_FORBIDDEN = 111804;
  const ERR_SETUID_USER_NOT_FOUND = 111805;
  const ERR_OBJECT_NOT_FOUND = 111806;
  const ERR_ACTION_NOT_FOUND = 111807;
  const ERR_INVALID_TOKEN = 111808;

  /**
   * Constructor of the Api Service Wrapper
   *
   * @param $options an hash with 
   * databaseAdapter: an already initialized PDO object
   *   see http://php.net/PDO
   * loginAdapterList: (not mandatory) list of allowed authentication adapters (their codename)
   *   see Alternc/Api/Auth/*
   * loggerAdapter: (not mandatory), a PSR3-Interface-compliant class or a list of it.
   *   see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md for more information
   *
   * @return create the object
   */

  function __construct($options) {

    // What DB shall we connect to?
    // Note: it MUST be in this mode : $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if (isset($options["databaseAdapter"]) && $options["databaseAdapter"] instanceof PDO) {
      $this->db=$options["databaseAdapter"];
    } else {
      throw new \Exception("Missing required parameter databaseAdapter", self::ERR_INVALID_ARGUMENT);
    }

    // Which login is allowed?
    $this->allowedAuth=array();
    if (isset($options["loginAdapterList"]) && is_array($options["loginAdapterList"]) ) {
      foreach($options["loginAdapterList"] as $lal) {
	$this->allowedAuth[] = (string)$lal;
      }
    }   

    // To which logger(s) shall we log to?
    if (isset($options["loggerAdapter"])) {
      if (!is_array($options["loggerAdapter"])) $options["loggerAdapter"]=array($options["loggerAdapter"]);
      foreach($options["loggerAdapter"] as $la) {
	if ($la instanceof Psr\Log\LoggerInterface)
	  $this->loggerList[]=$la;
      }
    }

  } // __construct


  /** 
   * Authenticate into an AlternC server
   * @param $auth hash with 
   *  method: string describing the authentication name (in Alternc_Api_Auth_xxx)
   *  options: array list of parameters for the corresponding auth. 
   *    if 'uid' is set in the option hash, the account MUST be an administrator one
   *    and as a result, the returned Api_Token will be set to this UID and not the admin one.
   * @return Alternc_Api_Token an API Token
   */
  function auth($auth) {
    if (!isset($auth["method"]) || !is_string($auth["method"])) {
      throw new \Exception("Missing required parameter method", self::ERR_INVALID_ARGUMENT);
    }
    if (!isset($auth["options"]) || !is_array($auth["options"])) {
      throw new \Exception("Missing required parameter options", self::ERR_INVALID_ARGUMENT);
    }

    if (count($this->allowedAuth) && !in_array($auth["method"],$this->allowedAuth)) {
      throw new \Exception("Method not allowed", self::ERR_METHOD_DENIED);
    }
    if (isset($auth["options"]["uid"]) && !intval($auth["options"]["uid"])) {
      throw new \Exception("Invalid UID", self::ERR_INVALID_ARGUMENT);
    }

    $adapterName = "Alternc_Api_Auth_".ucfirst(strtolower($auth["method"]));

    $authAdapter = new $adapterName($this);

    $token = $authAdapter->auth($auth["options"]);

    // something went wrong user-side
    if ($token instanceof Alternc_Api_Response) 
      return $token;
    // something went *really* wrong (bad type): 
    if (!$token instanceof Alternc_Api_Token) 
      throw new \Exception("Invalid answer from Api_Auth_Interface", self::ERR_INVALID_ANSWER);

    if (isset($auth["options"]["uid"])) {
      if (!$token->isAdmin) {
	// Non-admin are not allowed to setuid
	return new Alternc_Api_Response( array("code" => self::ERR_SETUID_FORBIDDEN, "message" => "This user is not allowed to set his uid") );
      } 
      // Search for the requested user. We allow using *disabled* account here since we are admin 
      foreach($this->db->query("SELECT uid FROM membres WHERE uid=".intval($auth["options"]["uid"])) as $setuid) {
	$token->uid=intval($setuid['uid']);
	$stmt=$this->db->prepare("UPDATE token SET data=? WHERE token=?");
	$stmt->execute(array( $token->toJson(), $token->token));
	return $token;
      } 
      return new Alternc_Api_Response( array("code" => self::ERR_SETUID_USER_NOT_FOUND, "message" => "Can't find the user you want to setuid to") );
    }
    return $token;
  }


  /** 
   * Manage an API Call
   * @param Alternc_Api_Request $request The API call
   *   the request must have "object" and "action" elements, and a "token" to authenticate
   *   "options" are sent as it is to the Api Call. 
   * @return Alternc_Api_Response an API response
   */
  function call($request) {
    if (!$request instanceof Alternc_Api_Request) 
      throw new \Exception("request must be an Alternc_Api_Request object", self::ERR_INVALID_ARGUMENT);

    // we set the token in the Service object, so that other classes can use it :) 
    $this->token = Alternc_Api_Token::tokenGet($request->token_hash,$this->db);
    if ($this->token instanceof Alternc_Api_Response)  // bad token
      return $this->token;
    
    $className = "Alternc_Api_Object_".ucfirst(strtolower($request->object));
    if (!class_exists($className)) 
      return new Alternc_Api_Response( array("code" => self::ERR_OBJECT_NOT_FOUND, "message" => "Object not found in this AlternC's instance") );
    
    $object = new $className($this);

    $action=$request->action;
    if (!method_exists($object, $action)) 
      return new Alternc_Api_Response( array("code" => self::ERR_ACTION_NOT_FOUND, "message" => "Action not found for this object in this AlternC's instance") );

    $request->token=$this->token; // we receive $request->token_hash as a STRING, but we transmit its object as an Alternc_Api_Token.

    // TODO: log this Api Call
    return $object->$action($request->options);
  }


  /** 
   * Getter for the databaseAdapter 
   * (used by authAdapter)
   */
  function getDb() {
    return $this->db;
  }




} // class Alternc_Api_Service

