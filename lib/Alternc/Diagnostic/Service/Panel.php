<?php 

/**
 * Lists members
 */
class Alternc_Diagnostic_Service_Panel 
    extends Alternc_Diagnostic_Service_Abstract
    implements Alternc_Diagnostic_Service_Interface
{

    public $name                        = "panel";
    function run(){
        return $this->data;
    }

}