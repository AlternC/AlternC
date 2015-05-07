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
    
    /** @var array */
    protected $zonesList;
    
    
    /** var system_bind */
    protected $bind;
    const SECTION_LIST                  = "list";
    const SECTION_HOST                  = "host";
    const SECTION_NAMESERVERS           = "nameservers";
    const SECTION_ZONES                 = "zones";
    const SECTION_ZONES_LOCKED          = "zones_locked";
    const SECTION_SLAVES                = "slaves";
    
    function run(){
        
        /** @var m_dom */
        global $dom;
      
	$version			= $this->service->version;
	if( $version < 3.2 ) {
	    $this->bind                     = new system_bind(array(
		"zone_file_directory"       => "/var/alternc/bind/zones/"));
	    $this->domainList			= $this->get_domain_all_summary();
	}else{
	    $this->bind                     = new system_bind();
	    $this->domainList               = $dom->get_domain_all_summary();
	}
        
        // Attempts to retrieve zone list
        $this->zonesList                    = $this->getZonesList();
        
        // Writes the dns slaves
        $this->writeSectionData (self::SECTION_SLAVES,$this->getSlaves());
        
	// Writes the domains list 
	$this->writeSectionData (self::SECTION_LIST,$this->domainList);
        
        // Writes the domains hosts 
        $this->writeSectionData (self::SECTION_HOST,  $this->getHosts());
        
        // Writes the domains nameservers
        $this->writeSectionData (self::SECTION_NAMESERVERS,$this->getNameservers());
        
        // Writes the domains zones
        $this->writeSectionData (self::SECTION_ZONES,$this->zonesList);

        // Writes the domains zones locked
        $this->writeSectionData (self::SECTION_ZONES_LOCKED,$this->getZonesLocked());
        
        // Writes the domains zones with custom records
        $this->writeSectionData (self::SECTION_ZONES_LOCKED,$this->getZonesCustomRecords());
        
        return $this->data;
    }
   
    /**
     *  Local override if not available (1.0)
     * @return array
     */
    function get_domain_all_summary() {
        global $db, $err;
        $res = array();
        $db->query("SELECT domaine, gesdns, gesmx, dns_action FROM domaines ORDER BY domaine");
        while ($db->next_record()) {
            $res[$db->f("domaine")] = array(
                "gesdns" => $db->f("gesdns"),
                "gesmx" => $db->f("gesmx"),
                "dns_action" => $db->f("dns_action"),
            );
        }
        return $res;
    }
 
    /**
     * Lists domains `host $DOMAIN` data 
     * @return array
     */
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
    
    /**
     * Lists domains NS
     * 
     * @return array
     */
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
    
    /**
     * Lists zones content
     * 
     * @return array
     */
    function getZonesList(){
        $resultArray                    = array();
        foreach ($this->domainList as $domain => $domainInfo) {
            try{
                $resultArray[$domain]   = $this->bind->get_zone_file($domain);
            }catch( \Exception $e){
                echo $e->getMessage()."\n";
            }
        }
        return $resultArray;        
    }
    
    /**
     * Lists which domains zones are locked
     * 
     * @return array
     */
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
    
    /**
     * Lists which domains zones have custom records
     * 
     * @return array
     */
    function getZonesCustomRecords(){
        $resultArray                    = array();
        $regexp                         = ";;; END ALTERNC AUTOGENERATE CONFIGURATION\n(.+\w+.+)";
        foreach ($this->zonesList as $domain => $zone) {
            $is_custom                  = false;
            try{
                if(preg_match("/$regexp/ms", $zone, $matches)){
                    $is_custom          = $matches[1];
                }
            }catch( \Exception $e){
                echo $e->getMessage()."\n";
            }
            $resultArray[$domain]       = $is_custom;
        }
        return $resultArray;
    }

    /**
     * Lists servers DNS slaves accounts
     * 
     * @return array
     */
    function getSlaves(){
        return $this->dom->enum_slave_account();
    }
    
}
