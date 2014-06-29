<?php 

/**
 * Lists versions : php mysql posfix dovecot roundcubke squirrelmail courier mailman alternc-* acl quota sasl
 * 
 */
class Alternc_Diagnostic_Service_System 
    extends Alternc_Diagnostic_Service_Abstract
    implements Alternc_Diagnostic_Service_Interface
{

    public $name                        = "system";
    function run(){
        $this->addDataSection("ip list", $this->execCmd("ip a"));
        return $this->data;
    }
}