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
    
    /**
     * Reads an array of URL and returns the CURL results
     * 
     * @param array $urlList
     * @param array $fieldsList curlInfo array keys
     * @param int $sockets_max
     * @return array
     */
    function curlRequest($urlList,$fieldsList = array("http_code","url"),$sockets_max = 8){
        $returnArray                    = array();
        
        // Attempts to retrive a multi connection curl handle
        $multiCurlHandle                = curl_multi_init();
        for ($index = 0; $index < $sockets_max; $index++) {
            $ch                         = "ch".$index;
            $$ch                        = curl_init();
            curl_setopt($$ch, CURLOPT_HEADER,         1);
            curl_setopt($$ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($$ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($$ch, CURLOPT_TIMEOUT,        3);
            curl_setopt($$ch, CURLOPT_NOBODY,         1);
            curl_multi_add_handle($multiCurlHandle,$$ch);
        }
        
        $url_count                      = count($urlList);
        $url_pointer                    = 0;

        while( $url_pointer < $url_count){
            $sockets                    = $url_count - $url_pointer > $sockets_max ? $sockets_max : $url_count - $url_pointer ;
            $loopUrlList                = array();
            for ($index2 = 0; $index2 < $sockets; $index2++) {
                $ch                     = "ch".$index2;
                $url                    = $urlList[$url_pointer];
                $loopUrlList[$index2]   = $url;
                curl_setopt($$ch, CURLOPT_URL, $url);
                $url_pointer++;
            }
            
            do {
                curl_multi_exec($multiCurlHandle, $running);
                curl_multi_select($multiCurlHandle);
            } while ($running > 0);
            
            for ($index3 = 0; $index3 < $sockets; $index3++) {
                $ch                     = "ch".$index3;
                $url                    = $loopUrlList[$index3];
                $curlInfo               = curl_getinfo($$ch);
                $urlInfo                = array();
                foreach ($fieldsList as $field) {
                    $urlInfo[$field]    = $curlInfo[$field];
                }
                $returnArray[]          = $urlInfo;
            }
            
        }
        
        //close the handles
        curl_multi_close($multiCurlHandle);
        for ($index = 0; $index < $sockets_max; $index++) {
            $ch                         = "ch".$index;
            curl_close($$ch);
        }
        
        return $returnArray;
        
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