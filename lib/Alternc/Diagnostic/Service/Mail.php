<?php 

/**
 * Lists emails
 * Stats pop / alias
 * Checks SMTP / SIEVE
 */
class Alternc_Diagnostic_Service_Mail 
    extends Alternc_Diagnostic_Service_Abstract
    implements Alternc_Diagnostic_Service_Interface
{

    public $name                        = "email";

    protected $mailList                 = array();

    const SECTION_LIST                  = "list";
    const SECTION_STAT_ALIAS            = "stat_alias";
    const SECTION_CHECK_STORAGE         = "check_storage";
    
    function run(){
        
        // Attempts to retrieve all email addresses
        $this->mailList                 = $this->getList();
        
        // Writes the domains list 
	$this->writeSectionData (self::SECTION_LIST,$this->getList());

        // Writes the alias stats
	$this->writeSectionData (self::SECTION_STAT_ALIAS,$this->getStatAlias());

        // Writes the quota stats 
	$this->writeSectionData (self::SECTION_CHECK_STORAGE,$this->getCheckStorage());

        
        return $this->data;
    }
    
    /**
     * Gets list from db
     * 
     * @return array
     */
    function getList(){

        $returnArray                    = array();
        // Check the availability
        $this->db->query('
            SELECT CONCAT(a.address,"@",d.domaine) as email, a.type, a.enabled, r.recipients, m.path,m.quota,m.bytes/(1024*1024) as size_mo,m.messages
            FROM address a
            LEFT JOIN recipient r ON r.address_id = a.id
            LEFT JOIN mailbox m ON m.address_id = a.id
            JOIN domaines d ON a.domain_id = d.id;');
        if ($this->db->num_rows()) {
            while ($this->db->next_record()) {
                $email                  = $this->db->f("email");
                $returnArray[$email] = array(
                    "enabled"       => $this->db->f("enabled"),
                    "type"          => $this->db->f("type"),
                    "recipients"    => $this->db->f("recipients"),
                    "path"          => $this->db->f("path"),
                    "quota"         => $this->db->f("quota"),
                    "size_mo"       => $this->db->f("size_mo"),
                    "messages"      => $this->db->f("messages"),
               );
          }
        }
        return $returnArray;
    }
    /**
     * Searches mails with alias
     * 
     * @return array
     */    
    function getStatAlias() {
        $returnArray                    = array();
        foreach ($this->mailList as $email => $emailInfo) {
            $is_alias               = false;
            if( !is_null($emailInfo["recipients"])){
                $is_alias           = true;
            }
            $returnArray[$email]    = $is_alias;
        }
        return $returnArray;
        
    }
    
    /**
     * Checks the box oversize and effective existence
     * 
     * @return array
     */
    function getCheckStorage() {
        $returnArray                    = array();
        foreach ($this->mailList as $email => $emailInfo) {
            // Only account real boxes
            if( ! $emailInfo["path"]){
                continue;
            }
            $quota_pct              = "0";
            $box_exists             = true;
            $quota                  = $emailInfo["quota"];
            $size_mo                = $emailInfo["size_mo"] ;
            if( $quota > 0  ){
                $quota_pct          = $size_mo * 100 / $quota;
            }
            if( !is_dir($emailInfo["path"]) ){
                $box_exists         = false;
            }
            $returnArray[$email]    = array(
                "percent"       => number_format($quota_pct, 2),
                "box_exists"    => $box_exists
                );
        }
        return $returnArray;
        
    }
    
}