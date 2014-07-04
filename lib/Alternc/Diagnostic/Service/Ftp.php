<?php 

/**
 * Lists accounts
 * Checks root
 */
class Alternc_Diagnostic_Service_Ftp 
    extends Alternc_Diagnostic_Service_Abstract
    implements Alternc_Diagnostic_Service_Interface
{

    public $name                        = "ftp";
    
//    /** @var m_ftp */
//    protected $ftp;

    /** @var array */
    protected $ftpList;


    const SECTION_LIST                  = "list";
    const SECTION_CHECK_HOMEDIR         = "check_homedir";

    function run(){
        
        
        $this->ftpList                  = $this->get_list();
        
        // Writes the domains list 
	$this->writeSectionData (self::SECTION_LIST,$this->ftpList);

        // Checks the homedir existence
	$this->writeSectionData (self::SECTION_CHECK_HOMEDIR,  $this->checkHomeDir());
        
        return $this->data;
    }
    
    function checkHomeDir() {
        
        $returnArray                = array();
        foreach( $this->ftpList as $login => $ftpData){
            $exists                 = false;
            $homedir                = $ftpData["dir"];
            if(is_dir($homedir) ){
                $exists             = true;
            }
            $returnArray[$login]    = $exists;
            
        }
        return $returnArray;
    }

    
  function get_list() {
    $returnArray                    = array();
    $this->db->query("SELECT id, name, homedir, enabled FROM ftpusers ORDER BY name;");
    if ($this->db->num_rows()) {
      while ($this->db->next_record()) {
        $returnArray[$this->db->f("name")]=array(
                  "enabled"   => $this->db->f("enabled"),
                  "dir"       => $this->db->f("homedir")
             );
      }
    }
    return $returnArray;
    
  }    
    
}