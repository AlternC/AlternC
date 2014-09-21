<?php

/**
 * Standard Response object for the AlternC API
 * 
 */
class Alternc_Api_Response {

  /** 
   * Error codes
   */
  const ERR_DISABLED_ACCOUNT = 221801; 
  const ERR_INVALID_AUTH = 221802; 


    /**
     * Result code. 0 means success
     *
     * @var int
     */
    public $code; 
    
    /**
     * Result message. May be empty
     * 
     * @var string
     */
    public $message;
    
    /**
     * Result data
     * 
     * @var array
     */
    public $content;
    
    /**
     * Result metadata
     * 
     * @var array
     */
    public $metadata; 
    

    /**
     * initialize a response object
     * @param options any of the public above
     */
    public function __construct($options=array()) {
      $os=array("code","message","content","metadata");
      foreach ($os as $o) {
	if (isset($options[$o])) $this->$o=$options[$o];
      }
    }


    /**
     * Formats response to json
     * 
     * @return string
     */
    public function toJson (){       
        return json_encode(get_object_vars($this));
    }



    
} // class Alternc_Api_Response

