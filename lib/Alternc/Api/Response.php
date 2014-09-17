<?php

/**
 * Standard Response object for the AlternC API
 * 
 */
class Alternc_Api_Response {

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
     * Formats response to json
     * 
     * @return string
     */
    public function toJson (){
        
        return json_encode(get_object_vars($this));
        
    }

    
}