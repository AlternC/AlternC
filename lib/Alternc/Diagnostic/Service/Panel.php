<?php 

/**
 * Lists members
 */
class Alternc_Diagnostic_Service_Panel 
    extends Alternc_Diagnostic_Service_Abstract
    implements Alternc_Diagnostic_Service_Interface
{

    public $name                        = "panel";
    
    protected $membersList;

    const SECTION_MEMBERS_LIST          = "members_list";

    function run(){
        
        $this->membersList              = $this->getMembersList();

        // Writes the members list 
        $this->writeSectionData(self::SECTION_MEMBERS_LIST, $this->membersList);

        return $this->data;
    }
    
    function getMembersList(){
        $returnArray                    = array();
        $this->db->query("SELECT uid,login,enabled,su,mail,creator,db_server_id,created FROM alternc.membres;");
        if ($this->db->num_rows()) {
            while ($this->db->next_record()) {
                $returnArray[$this->db->f("uid")] = array(
                    "uid"           => $this->db->f("uid"),
                    "login"         => $this->db->f("login"),
                    "enabled"       => $this->db->f("enabled"),
                    "su"            => $this->db->f("su"),
                    "mail"          => $this->db->f("mail"),
                    "creator"       => $this->db->f("creator"),
                    "db_server_id"  => $this->db->f("db_server_id"),
                    "created"       => $this->db->f("created"),
                );
            }
        }
        return $returnArray;
    }

}