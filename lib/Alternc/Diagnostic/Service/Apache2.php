<?php 

/**
 * Lists vhosts 
 * Lists redirections
 * Checks vhosts
 * Checks redirections
 */
class Alternc_Diagnostic_Service_Apache2
    extends Alternc_Diagnostic_Service_Abstract
    implements Alternc_Diagnostic_Service_Interface
{

    public $name                        = "web";
    const SECTION_APACHE2_VHOSTS        = "apache2 vhosts";
    const SECTION_APACHE2_MODULES       = "apache2 modules";
    function run(){        
        
        $this->addDataSection (self::SECTION_APACHE2_VHOSTS,$this->filterRegexp("/^[\D]*(\d{2,4}).* (.*) \(\/etc.*$/u", $this->execCmd("apache2ctl -S")));
        $this->addDataSection (self::SECTION_APACHE2_MODULES,$this->filterRegexp("/^[\W]*(\w+).*\(.*$/u", $this->execCmd("apache2ctl -M")));
        $this->addDataSection (self::SECTION_APACHE2_REDIRECTION,$this->mysql->query("SELECT domaine, valeur from sub_domaines where type='url';"));
        
        return $this->data;
    }

}