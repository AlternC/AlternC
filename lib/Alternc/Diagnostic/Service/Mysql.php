<?php 

/**
 * Lists databases
 * Lists users
 */
class Alternc_Diagnostic_Service_Mysql 
    extends Alternc_Diagnostic_Service_Abstract 
    implements Alternc_Diagnostic_Service_Interface {

    public $name = "mysql";

    protected $dbList;
    protected $usersList;
    protected $serversList;

    const SECTION_DB_LIST           = "dbs_list";
    const SECTION_USER_LIST         = "users_list";
    const SECTION_SERVERS_LIST      = "servers_list";

    function run() {


        $this->dbList               = $this->getDbList();
        $this->usersList            = $this->getUsersList();
        $this->serversList          = $this->getServersList();

        // Writes the mysql db list 
        $this->writeSectionData(self::SECTION_DB_LIST, $this->dbList);

        // Writes the mysql user list 
        $this->writeSectionData(self::SECTION_USER_LIST, $this->usersList);

        // Writes the mysql servers list 
        $this->writeSectionData(self::SECTION_SERVERS_LIST, $this->serversList);

        return $this->data;
    }


    function getDbList() {
        $returnArray = array();
        $this->db->query("SELECT login,pass,db, bck_mode, bck_dir FROM db ORDER BY db;");
        if ($this->db->num_rows()) {
            while ($this->db->next_record()) {
                $db                         = $this->db->f("db");
                list($dbu,$dbn)             = split_mysql_database_name($db);
                $returnArray[$db]           = array(
                    "user"      => $dbu,
                    "bck_mode"  => $this->db->f("bck_mode"), 
                    "bck_dir"   => $this->db->f("bck_dir"), 
                    "login"     => $this->db->f("login"), 
                    "pass"      => $this->db->f("pass")
                );
            }
        }
        return $returnArray;
    }    

    function getUsersList() {
        $returnArray = array();
        $this->db->query("SELECT name, password, enable FROM dbusers ORDER BY name;");
        if ($this->db->num_rows()) {
            while ($this->db->next_record()) {
                $returnArray[$this->db->f("name")] = array(
                    "enable" => $this->db->f("enable"),
                    "password" => $this->db->f("password")
                );
            }
        }
        return $returnArray;
    }    

    function getServersList() {
        $returnArray = array();
        $this->db->query("SELECT name, host, login, password FROM db_servers ORDER BY host;");
        if ($this->db->num_rows()) {
            while ($this->db->next_record()) {
                $returnArray[$this->db->f("name")] = array(
                    "host" => $this->db->f("host"),
                    "login" => $this->db->f("login"),
                    "password" => $this->db->f("password")
                );
            }
        }
        return $returnArray;
    }    
}