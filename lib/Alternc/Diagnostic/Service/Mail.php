<?php 

/**
 * Lists emails
 * Stats pop / alias
 * Checks SMTP / SIEVE
 */
class Alternc_Diagnostic_Service_Mail 
    extends Alternc_Diagnostic_Service_Abstract
    implements Alternc_Diagnostic_Service_Interface
{

    public $name                        = "email";
    function run(){
        return $this->data;
    }

}