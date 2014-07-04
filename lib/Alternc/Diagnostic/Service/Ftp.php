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
    
    /** @var m_ftp */
    protected $ftp;

    /** @var array */
    protected $ftpList;


    const SECTION_LIST                  = "list";
    const SECTION_CHECK_HOMEDIR         = "check_homedir";

    function run(){
        
        
//        global $ftp;
//       
//        $this->ftp                      = $ftp;
        
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
    global $db,$err, $bro;
    $err->log("ftp","get_list");
    $r=array();
    $db->query("SELECT id, name, homedir, enabled FROM ftpusers ORDER BY name;");
    if ($db->num_rows()) {
      while ($db->next_record()) {
	      $r[$db->f("name")]=array(
		        "id"=>$db->f("id"),
		        "login"=>$db->f("name"),
		        "enabled"=>$db->f("enabled"),
		        "dir"=>$db->f("homedir")
		   );
      }
      return $r;
    } else {
      $err->raise("ftp",_("No FTP account found"));
      return array();
    }
  }    
    
}