<?php 

/**
 * List domains
 * Check domains 
 *      domains response
 *      zones locked
 *      slaves
 */
class Alternc_Diagnostic_Service_Dns 
    extends Alternc_Diagnostic_Service_Abstract
    implements Alternc_Diagnostic_Service_Interface
{

    public $name                        = "dns";
    function run(){
        return $this->data;
    }

}