<?php 

/**
 * Lists databases
 * Lists users
 */
class Alternc_Diagnostic_Service_Mysql 
    extends Alternc_Diagnostic_Service_Abstract
    implements Alternc_Diagnostic_Service_Interface
{

    public $name                        = "mysql";
    function run(){
        return $this->data;
    }

}