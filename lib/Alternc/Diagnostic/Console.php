<?php 

/**
 * Console aware class, encapsulates the Console CommandLine class
 */
class Alternc_Diagnostic_Console extends Console_CommandLine{
    
    const DESCRIPTION           = "Handles diagnostics of an alternc server.";
    const VERSION               = "0.1";
    
    function __construct(array $params = array()) {
        $params                     = array(
            'description'   => self::DESCRIPTION,
            'version'       => self::VERSION
        );
        
        parent::__construct($params);
    }
    
    
}

