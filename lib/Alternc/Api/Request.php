<?php

/**
 * Standard Request object for the AlternC API
 * 
 * Helps streamlining the calls by checking parameters
 */
class Alternc_Api_Request {

    /**
     *
     * @var Alternc_Api_Token
     */
    public $token;
    
    /**
     * must link to a Alternc_Api_Object_Interface
     * 
     * @var string
     */
    public $object;
    /**
     * must link to a Alternc_Api_Object_Interface method
     * 
     * @var string
     */
    public $action;
    /**
     * bag of data
     * 
     * @var array
     */
    public $options;
    /**
     *
     *  Bag of data
     * 
     * @var array
     */
    public $metadata;

    
    const ERR_MISSING_PARAMETER             =  111801;
    
    function __construct($options) {
        
       
        // Attempts to retrieve object
        if (isset($options["object"]) && !is_null($options["object"])) {
            $this->object = $options["object"];
        } else {
            throw new \Exception("Missing parameter object", self::ERR_MISSING_PARAMETER);
        }
        
        // Attempts to retrieve token
        if (isset($options["token"]) && is_a( $options["token"], Alternc_Api_Token)) {
            $this->token = $options["token"];
        } else {
            throw new \Exception("Missing parameter token", self::ERR_MISSING_PARAMETER);
        }

        // Attempts to retrieve action
        if (isset($options["action"]) && $var ) {
            $this->action = $options["action"];
        } else {
            throw new \Exception("Missing parameter action", self::ERR_MISSING_PARAMETER);
        }
        
        // Attempts to retrieve options
        if (isset($options["options"]) && is_array($options)) {
            $this->options = $options["options"];
        } else {
            throw new \Exception("Missing parameter options", self::ERR_MISSING_PARAMETER);
        }
        
        // Attempts to retrieve metadata
        if (isset($options["metadata"])) {
            $this->metadata = $options["metadata"];
        } 
        
        
        
    }
}