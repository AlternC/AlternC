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

    public $name                        = "apache2";
    const SECTION_VHOSTS                = "vhosts";
    const SECTION_MODULES               = "modules";
    const SECTION_REDIRECTIONS          = "redirections";
    const SECTION_RESPONSES             = "responses";
    
    function run(){        
        
        // Writes the modules 
        $this->writeSectionData (self::SECTION_MODULES,$this->filterRegexp ($this->execCmd("apache2ctl -M"), "/^[\W]*(\w+).*\(.*$/u" ));
        // Writes the vhosts in the form "port servername"
        $this->writeSectionData (self::SECTION_VHOSTS,$this->getVhosts());
        // Writes the redirects
        $this->writeSectionData (self::SECTION_REDIRECTIONS, $this->getRedirects());
        // Writes the tests
        $this->writeSectionData (self::SECTION_RESPONSES,$this->testServers());
        
        return $this->data;
    }

    function getVhosts(){
        $list                           = $this->filterRegexp( $this->execCmd("apache2ctl -S"), "/^[\D]*(\d{2,4}).* (.*) \(\/etc.*$/u");
        $returnArray                    = array();
        foreach( $list as $vhost){
            $returnArray[]              = explode(" ",$vhost);
        }
        return $returnArray;
    }
    
    function getRedirects(){
        $mysqlResource                  = $this->db->query("SELECT domaine as domain, valeur as url from sub_domaines where type='url';");        
        $resultArray                    = array();
        if ($this->db->num_rows()) {
            while(($resultArray[] = mysql_fetch_assoc($mysqlResource)) || array_pop($resultArray));
        }
        return $resultArray;
    }
    
    function testServers(){

        $sockets_max                    = 8;
        $fieldsList                     = array("http_code","url");
        $vhostUrlList                        = array();
        
        // Retrieves and tests local vhosts
        $vhostList                      = $this->data->getSection(self::SECTION_VHOSTS)->getData();
        foreach( $vhostList as $vhostInfo){
            $protocol                   = $vhostInfo[0] == 443 ? "https://":"http://";
            $vhostUrlList[]                  = "{$protocol}{$vhostInfo[1]}";
        }
        $vhostResult                    = $this->curlRequest($vhostUrlList,$fieldsList,$sockets_max);
        
        // Retrieves and tests local redirs
        $redirList                      = $this->data->getSection(self::SECTION_REDIRECTIONS)->getData();
        foreach( $redirList as $redirInfo){
            $redirUrlList[]                  = $redirInfo["url"];
        }
        $redirResult                    = $this->curlRequest($redirUrlList,$fieldsList,$sockets_max);
        
        return array_merge($vhostResult,$redirResult);
        
    }
    
}