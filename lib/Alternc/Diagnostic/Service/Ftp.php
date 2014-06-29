<?php 

/**
 * Lists accounts
 * Checks root
 */
class Alternc_Diagnostic_Service_Ftp 
    extends Alternc_Diagnostic_Service_Abstract
    implements Alternc_Diagnostic_Service_Interface
{

    public $name                        = "ftp";
    function run(){
        return $this->data;
    }
}