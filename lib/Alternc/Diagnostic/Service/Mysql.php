<?php 

/**
 * Lists databases
 * Lists users
 */
class Alternc_Diagnostic_Service_Mysql 
    extends Alternc_Diagnostic_Service_Abstract 
    implements Alternc_Diagnostic_Service_Interface {

    public $name                        = "mysql";

    protected $dbList;
    protected $usersList;
    protected $serversList;

    const SECTION_DB_LIST               = "dbs_list";
    const SECTION_USER_LIST             = "users_list";
    const SECTION_SERVERS_LIST          = "servers_list";
    const SECTION_STAT_SIZE             = "stat_size";
    const SECTION_CHECK_ACCESS          = "check_access";

    /**
     * 
     * @inherit
     */
    function run() {


        $this->dbList                   = $this->getDbList();
        $this->usersList                = $this->getUsersList();
        $this->serversList              = $this->getServersList();

        // Writes the mysql db list 
        $this->writeSectionData(self::SECTION_DB_LIST, $this->dbList);

        // Writes the mysql user list 
        $this->writeSectionData(self::SECTION_USER_LIST, $this->usersList);

        // Writes the mysql servers list 
        $this->writeSectionData(self::SECTION_SERVERS_LIST, $this->serversList);

        // Writes the mysql size stats
        $this->writeSectionData(self::SECTION_STAT_SIZE, $this->getSizeStats());
        
        // Writes the mysql access check
        $this->writeSectionData(self::SECTION_CHECK_ACCESS, $this->getCheckAccess());
        
        return $this->data;
    }

    /**
     * Returns a db_name -> dbInfo array
     * 
     * @return array
     */
    function getDbList() {
        $returnArray                    = array();
        $this->db->query("SELECT login,pass,db, bck_mode, bck_dir FROM db ORDER BY db;");
        if ($this->db->num_rows()) {
            while ($this->db->next_record()) {
                $db                     = $this->db->f("db");
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

    /**
     * Returns a db_user_name -> dbUserInfo array
     * 
     * @return array
     */
    function getUsersList() {
        $returnArray                    = array();
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

    /**
     * Returns a server_name -> serverInfo array
     * 
     * @return array
     */
    function getServersList() {
        $returnArray                    = array();
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
    
    /**
     * Returns a db_name -> db_size array
     * 
     * @return array
     */
    function getSizeStats(){
        $returnArray                    = array();
        global $L_MYSQL_LOGIN, $L_MYSQL_HOST, $L_MYSQL_PWD;
        $db                             = new DB_Sql();
        $db->Host                       = $L_MYSQL_HOST;
        $db->User                       = $L_MYSQL_LOGIN;
        $db->Password                   = $L_MYSQL_PWD;
        $db->Database                   = "mysql";
        foreach ($this->dbList as $dbname => $dbInfo) {
            $db->query("SHOW TABLE STATUS FROM `$dbname`;");
            $size = 0;
            while ($db->next_record()) {
              $size += $db->f('Data_length') + $db->f('Index_length');
              if ( $db->f('Engine') != 'InnoDB') $size += $db->f('Data_free');
            }
            $returnArray[$dbname]             = $size;
        }
        return $returnArray;

        
    }
    /**
     * Returns a user_name -> has_access array
     * 
     * @return array
     */
    function getCheckAccess(){
        $returnArray                    = array();
        global $L_MYSQL_LOGIN, $L_MYSQL_HOST, $L_MYSQL_PWD;
        $db                             = new DB_Sql();
        $db->Host                       = $L_MYSQL_HOST;
        $db->User                       = $L_MYSQL_LOGIN;
        $db->Password                   = $L_MYSQL_PWD;
        $db->Database                   = "mysql";
        foreach ($this->usersList as $user => $userInfo) {
            $has_access                 = false;
            $password                   = $userInfo["password"];
            $db->query("SELECT FROM user where user='".$user."' and password=PASSWORD('".$password."') ORDER BY host;");
            if ($this->db->num_rows()) {
                $has_access             = true;
            }
            $returnArray[$user]             = $has_access;
        }
        return $returnArray;
        
    }
    

}