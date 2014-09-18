<?php



/**
 * Service API used by server to export API methods
 */
class Alternc_Api_Service {
    

  private $db; // PDO object
  private $loggerList; // List of loggers
  private $allowedAuth; // list of allowed authenticators

  const ERR_INVALID_ARGUMENT = 111801;
  const ERR_METHOD_DENIED = 111802;

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

    $adapterName = "Alternc_Api_Auth_".ucfirst(strtolower($auth["method"]));
    $authAdapter = new $adapterName($this);

    return $authAdapter->auth($auth["options"]);
    //    table des tokens : token, expire, json_encode('uid','is_admin')
    // return new Alternc_Api_Token();
  }


  /** 
   * Manage an API Call
   * @param Alternc_Api_Request $request The API call
   * @return Alternc_Api_Response an API response
   */
  function call($request) {
    
    return new Alternc_Api_Response();
  }


  /** 
   * Getter for the databaseAdapter 
   * (used by authAdapter)
   */
  function getDb() {
    return $this->db;
  }
 


} // class Alternc_Api_Service

