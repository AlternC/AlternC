<?php

/**
 * Standard Request object for the AlternC API
 * 
 * Helps streamlining the calls by checking parameters
 */
class Alternc_Api_Request {

    /**
     *
     * @var Alternc_Api_Token object
     */
    public $token;

    /**
     *
     * @var string a token hash (to be authenticated)
     */
    public $token_hash;

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

    const ERR_MISSING_PARAMETER = 111801;

    function __construct($options) {


        // Attempts to retrieve object
        if (isset($options["object"]) && is_string($options["object"])) {
            $this->object = $options["object"];
        } else {
            throw new \Exception("Missing parameter object", self::ERR_MISSING_PARAMETER);
        }

        // Attempts to retrieve action
        if (isset($options["action"]) && is_string($options["action"])) {
            $this->action = $options["action"];
        } else {
            throw new \Exception("Missing parameter action", self::ERR_MISSING_PARAMETER);
        }

        // Attempts to retrieve options
        if (isset($options["options"])) {
            if (is_array($options)) {
                $this->options = $options["options"];
            } else {
                throw new \Exception("Missing parameter options", self::ERR_MISSING_PARAMETER);
            }
        } else {
            $this->options = array();
        }

        // Attempts to retrieve token
        if (isset($options["token"])) {
            if (is_a($options["token"], Alternc_Api_Token)) {
                $this->token = $options["token"];
            } else {
                throw new \Exception("Bad parameter token", self::ERR_MISSING_PARAMETER);
            }
        } else {
            // Attempts to retrieve token_hash then
            if (isset($options["token_hash"]) && is_string($options["token_hash"])) {
                $this->token_hash = $options["token_hash"];
            } else {
                throw new \Exception("Missing parameter token OR token_hash", self::ERR_MISSING_PARAMETER);
            }
        }

        // Attempts to retrieve metadata (eg: API version)
        if (isset($options["metadata"])) {
            $this->metadata = $options["metadata"];
        }
    }

}
