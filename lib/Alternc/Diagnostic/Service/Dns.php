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
    
    /** @var array */
    protected $domainList;
    
    /** var system_bind */
    protected $bind;
    const SECTION_LIST                  = "list";
    const SECTION_HOST                  = "host";
    const SECTION_NAMESERVERS           = "nameservers";
    const SECTION_ZONES                 = "zones";
    const SECTION_ZONES_LOCKED          = "zones_locked";
    const SECTION_SLAVES                = "slaves";
    public function __construct() {
        parent::__construct();
        $this->bind                     = new system_bind();
    }
    
    function run(){
        
        /** @var m_dom */
        global $dom;
        
        $this->domainList               = $dom->get_domain_all_summary();
        
        // Writes the domains list 
        $this->writeSectionData (self::SECTION_LIST,$this->domainList);
        // Writes the domains hosts 
        $this->writeSectionData (self::SECTION_HOST,  $this->getHosts());
        // Writes the domains nameservers
        $this->writeSectionData (self::SECTION_NAMESERVERS,$this->getNameservers());
        
        // Writes the domains zones
        $this->writeSectionData (self::SECTION_ZONES,$this->getZones());
        // Writes the domains zones locked
        $this->writeSectionData (self::SECTION_ZONES_LOCKED,$this->getZonesLocked());
        // Writes the dns slaves
        $this->writeSectionData (self::SECTION_SLAVES,$this->getSlaves());
        return $this->data;
    }
    
    function getHosts(){
        $resultArray                    = array();
        foreach ($this->domainList as $domain => $domainInfo) {
            try{
                $resultArray[$domain]       = $this->execCmd("host {$domain}");
            }catch( \Exception $e){
                echo $e->getMessage()."\n";
            }
        }
        return $resultArray;
    }
    
    function getNameservers(){
        $resultArray                    = array();
        foreach ($this->domainList as $domain => $domainInfo) {
            try{
                $resultArray[$domain] = $this->execCmd("dig NS {$domain} +short");
            }catch( \Exception $e){
                echo $e->getMessage()."\n";
            }
        }
        return $resultArray;
    }
    
    function getZones(){
        $resultArray                    = array();
        foreach ($this->domainList as $domain => $domainInfo) {
            try{
                $file_path              = $this->bind->get_zone_file($domain);
                $file_content           = "";
                if( is_file($file_path)){
                    $file_content       .= file_get_contents($file_path);
                }
                $resultArray[$domain]   = $file_content;
            }catch( \Exception $e){
                echo $e->getMessage()."\n";
            }
        }
        return $resultArray;        
    }
    
    function getZonesLocked(){
        $resultArray                    = array();
        foreach ($this->domainList as $domain => $domainInfo) {
            try{
                $resultArray[$domain] = $this->bind->is_locked($domain);
            }catch( \Exception $e){
                echo $e->getMessage()."\n";
            }
        }
        return $resultArray;
    }
    
    function getSlaves(){
        return $this->dom->enum_slave_account();
    }
    
}