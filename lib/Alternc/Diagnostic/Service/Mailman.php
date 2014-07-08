<?php 

/**
 * Lists mailing lists
 */
class Alternc_Diagnostic_Service_Mailman 
    extends Alternc_Diagnostic_Service_Abstract
    implements Alternc_Diagnostic_Service_Interface
{

    public $name                        = "mailman";
    function run(){
        return $this->data;
    }

}