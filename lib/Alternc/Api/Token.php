<?php

/**
 * Standard Token object for the AlternC API
 * 
 */
class Alternc_Api_Token {


  const ERR_DATABASE_ERROR=112001;
  const ERR_INVALID_ARGUMENT=112002;
  const ERR_MISSING_ARGUMENT=112003;
  const ERR_INVALID_TOKEN=112004;
  
    /**
     * AlternC User-Id
     *
     * @var int
     */
    public $uid; 
    
    /**
     * Is this an admin account ? 
     * 
     * @var boolean
     */
    public $isAdmin;
    
    /**
     * The Token itself
     * 
     * @var string
     */
    public $token;
    
    
    /**
     * how long (seconds) is a token valid
     *
     * @var int
     */
    public $tokenDuration = 2678400; // default is a month


    /**
     * initialize a token object
     * @param options any of the public above
     *   may contain a dbAdapter, in that case create() will be available
     */
    public function __construct($options=array()) {

      if (isset($options["uid"]) && is_int($options["uid"])) 
	$this->uid=$options["uid"];

      if (isset($options["isAdmin"]) && is_bool($options["isAdmin"])) 
	$this->isAdmin=$options["isAdmin"];

    }

    
    /**
     * Formats response to json
     * 
     * @return string
     */
    public function toJson (){       
      return json_encode(
			 array("uid"=>$this->uid, 
			       "isAdmin" => $this->isAdmin, 
			       "token" => $this->token)
			 );
    }


    /**
     * Create a new token in the DB for the associated user/admin
     * 
     * @return string the token (32 chars)
     */
    public static function tokenGenerate($options,$db) {       
      if (!($db instanceof PDO)) {
	throw new \Exception("No DB Object, can't create",self::ERR_DATABASE_ERROR);
      }
      if (!isset($options["uid"]) || !isset($options["isAdmin"])) {
	throw new \Exception("Missing Arguments (uid,isAdmin)",self::ERR_MISSING_ARGUMENT);
      }
      
      $token=new Alternc_Api_Token($options);

      do {
	$token->token = $token->tokenRandom();
	$stmt=$db->prepare("INSERT IGNORE INTO token SET token=?, expire=DATE_ADD(NOW(), INTERVAL ? SECOND), data=?");
	$stmt->execute(array($token->token,$token->tokenDuration, $token->toJson()));
	$rows = $stmt->rowCount();
	
      } while ($rows==0); // prevent collisions

      return $token;
    }



    /**
     * Check and return a token
     * @param $token string a 32-chars token
     * @param $db PDO a PDO object for token table access
     * 
     * @return Alternc_Api_Token object or NULL
     */
    public static function tokenGet($token,$db) {       
      if (!($db instanceof PDO)) {
	throw new \Exception("No DB Object, can't create",self::ERR_DATABASE_ERROR);
      }
      if (!is_string($token) || !preg_match("#^[a-zA-Z0-9]{32}$#",$token)) {
	return new Alternc_Api_Response( array("code" => self::ERR_INVALID_TOKEN, "message" => "Invalid token") );
      }
      $stmt=$db->prepare("SELECT * FROM token WHERE token=?");
      $stmt->execute(array($token));
       if (  $tok=$stmt->fetch(PDO::FETCH_OBJ)  ) {
        return new Alternc_Api_Token( json_decode($tok->data,true) );
      }
      return new Alternc_Api_Response( array("code" => self::ERR_INVALID_TOKEN, "message" => "Invalid token") );
    }


    /**
     * Generate a new random token
     * @return string
     */
    public function tokenRandom(){
      $chars="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
      $s="";
      for($i=0;$i<32;$i++) 
	$s.=substr($chars,rand(0,61),1);
      return $s;
    }

    
} // class Alternc_Api_Response

