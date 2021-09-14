<?php

/*
  ----------------------------------------------------------------------
  LICENSE

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License (GPL)
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  To read the license please visit http://www.gnu.org/copyleft/gpl.html
  ----------------------------------------------------------------------
*/

/**
 * MySQL user database management for AlternC.
 * This class manage user's databases in MySQL, and user's MySQL accounts.
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */
class DB_users extends DB_Sql {

    var $Host, $HumanHostname, $User, $Password, $Client;

    /**
     * Creator
     */
    function __construct() { 
        // Sometimes we need to create this object with empty parameters, but by default we fill them with those of the current user's DB
        global $cuid, $db, $msg;
      
        $db->query("select db_servers.* from db_servers, membres where membres.uid= ? and membres.db_server_id=db_servers.id;", array($cuid));
        if (!$db->next_record()) {
            $msg->raise("ERROR", 'db_user', _("There are no databases in db_servers for this user. Please contact your administrator."));
            die();
        }

        // Create the object
        $this->HumanHostname = $db->f('name');
        $this->Host = $db->f('host');
        $this->User = $db->f('login');
        $this->Password = $db->f('password');
        $this->Client = $db->f('client');
        $this->Database = "mysql"; 
      
        parent::__construct("mysql", $db->f('host'), $db->f('login'), $db->f('password') );
      
    }

}

class m_mysql {

    var $dbus;


    /** 
     * Constructor
     * m_mysql([$mid]) Constructeur de la classe m_mysql, initialise le membre concerne
     */
    function __construct() {
        global $cuid;
        if (!empty($cuid)) {
            $this->dbus = new DB_users();
        }
        variable_get('sql_allow_users_backups', 1, 'Set 1 to allow users to configure backup of their databases, 0 if you want do disable this feature. Warning: it will not stop configured backup made by sqlbackup.sh');
    }


    function reload_dbus() {
        $this->dbus = new DB_users();
    }


    function list_db_servers() {
        global $db;
        $db->query("select d.*, IFNULL(count(m.uid),0) as nb_users from db_servers d left join membres m on  d.id = m.db_server_id group by d.id,m.db_server_id order by d.name,d.id;");
        $c = array();
        while ($db->next_record()) {
            $c[] = $db->Record;
        }
        return $c;
    }


    function hook_menu() {
        global $quota;
        $q = $quota->getquota("mysql");

        $obj = array(
            'title' => _("MySQL"),
            'ico' => 'images/mysql.png',
            'link' => 'toggle',
            'pos' => 100,
            'links' => array(),
        );

        $obj['links'][] = array(
            'txt' => _("Databases"),
            'url' => "sql_list.php",
        );
        $obj['links'][] = array(
            'txt' => _("MySQL Users"),
            'url' => "sql_users_list.php",
        );
        if ($q["u"] > 0) {
            $obj['links'][] = array(
                'txt' => _("PhpMyAdmin"),
                'url' => "sql_pma_sso.php",
                'target' => '_blank',
            );
        }
        return $obj;
    }


    /**
     * Password kind used in this class (hook for admin class)
     */
    function alternc_password_policy() {
        return array("mysql" => "MySQL users");
    }


    /**
     * Get the list of the database for the current user.
     * @return array returns an associative array as follow : <br>
     *  "db" => database name "bck" => backup mode for this db 
     *  "dir" => Backup folder.
     *  Returns an array (empty) if no databases
     */
    function get_dblist() {
        global $db, $msg, $bro, $cuid;
        $msg->debug("mysql", "get_dblist");
        $db->free();
        $db->query("SELECT login,pass,db, bck_mode, bck_dir FROM db WHERE uid= ? ORDER BY db;", array($cuid));
        $c = array();
        while ($db->next_record()) {
            list($dbu, $dbn) = split_mysql_database_name($db->f("db"));
            $c[] = array("db" => $db->f("db"), "name" => $db->f('db'), "bck" => $db->f("bck_mode"), "dir" => $db->f("bck_dir"), "login" => $db->f("login"), "pass" => $db->f("pass"));
        }
        return $c;
    }


    /**
     * Get the login and password of the special user able to connect to phpmyadmin
     * @return array returns an associative array with login and password 
     *  Returns FALSE if error
     */
    function php_myadmin_connect() {
        global $db, $cuid, $msg;
        $msg->log("mysql", "php_myadmin_connect");
        $db->query("SELECT count(0) as count from db where uid = ?;", array($cuid));
        $db->next_record();
        if ($db->f('count') == 0) {
            $msg->raise("ERROR", "mysql", _("Cannot connect to PhpMyAdmin, no databases for user {$cuid}"));
            return false;
        }
        $db->query("SELECT dbu.name,dbu.password, dbs.host FROM dbusers dbu, db_servers dbs, membres m WHERE dbu.uid= ? and enable='ADMIN' and dbs.id=m.db_server_id and m.uid= ? ;", array($cuid, $cuid));
        if (!$db->num_rows()) {
            $msg->raise("ERROR", "mysql", _("Cannot connect to PhpMyAdmin, no admin user for uid {$cuid}"));
            return false;
        }
        $db->next_record();
        $info = array(
            "login" => $db->f("name"),
            "pass" => $db->f("password"),
            "host" => $db->f("host")
        );
        return $info;
    }


    /**
     * Returns the details of a user's database.
     * $dbn is the name of the database (after the _) or nothing for the database "$user"
     * @return string returns an associative array as follow : 
     *  "db" => Name of the database 
     *  "bck" => Current bckup mode 
     *  "dir" => Backup directory
     *  "size" => Size of the database (in bytes)
     *  "pass" => Password of the user
     *  "history" => Number of backup we keep
     *  "gzip" => Does we compress the dumps ?
     *  Returns FALSE if the user has no database of if the database does not exist.
     */
    function get_mysql_details($dbn) {
        global $db, $msg, $cuid;
        $root = getuserpath();
        $msg->debug("mysql", "get_mysql_details");
        $pos = strpos($dbn, '_');
        if ($pos === false) {
            $dbname = $dbn;
        } else {
            $dbncomp = explode('_', $dbn);
            $dbname = $dbn;
            $dbn = $dbncomp[1];
        }
        $size = $this->get_db_size($dbname);
        $db->query("SELECT login,pass,db, bck_mode, bck_gzip, bck_dir, bck_history FROM db WHERE uid= ? AND db= ?;", array($cuid, $dbname));
        if (!$db->num_rows()) {
            $msg->raise("ERROR", "mysql", _("Database %s not found"), $dbn);
            return array("enabled" => false);
        }
        $db->next_record();
        list($dbu, $dbn) = split_mysql_database_name($db->f("db"));
        return array("enabled" => true, "login" => $db->f("login"), "db" => $db->f("db"), "name" => $dbn, "bck" => $db->f("bck_mode"), "dir" => substr($db->f("bck_dir"), strlen($root)), "size" => $size, "pass" => $db->f("pass"), "history" => $db->f("bck_history"), "gzip" => $db->f("bck_gzip"));
    }


    /**
     * Create a new database for the current user.
     * @param $dbn string Database name ($user_$dbn is the mysql db name)
     * @return boolean if the database $user_$db has been successfully created, or FALSE if 
     * an error occured, such as over quota user.
     */
    function add_db($dbn) {
        global $db, $msg, $quota, $cuid, $admin;
        $msg->log("mysql", "add_db", $dbn);
        $password_user = "";
        if (!$quota->cancreate("mysql")) {
            $msg->raise("ERROR", "mysql", _("Your databases quota is over. You cannot create more databases"));
            return false;
        }
        $pos = strpos($dbn, '_');
        if ($pos === false) {
            $dbname = $dbn;
        } else {
            $dbncomp = explode('_', $dbn);
            $dbname = $dbn;
            $dbn = $dbncomp[1];
            if (empty($dbn)) { // If nothing after the '_'
                $msg->raise("ERROR", "mysql", _("Database can't have empty suffix"));
                return false;
            }
        }
        if (!preg_match("#^[0-9a-z]*$#", $dbn)) {
            $msg->raise("ERROR", "mysql", _("Database name can contain only letters and numbers"));
            return false;
        }

        $len=variable_get("sql_max_database_length", 64);
        if (strlen($dbname) > $len) {
            $msg->raise("ERROR", "mysql", _("Database name cannot exceed %d characters"), $len);
            return false;
        }
        $db->query("SELECT * FROM db WHERE db= ? ;", array($dbname));
        if ($db->num_rows()) {
            $msg->raise("ERROR", "mysql", _("Database %s already exists"), $dbn);
            return false;
        }
        
        // We prevent the automatic creation of user account longer than the max allowed lenght of a MySQL username 
        $len=variable_get('sql_max_username_length', NULL);
        if (strlen($dbname) <= $len) {
            $db->query("SELECT name from dbusers where name= ? and enable='ACTIVATED' ;", array($dbname));
            if (!$db->num_rows()) {
                // We get the password complexity set in the policy and ensure we have that complexity in the create_pass() call
                $c=$admin->listPasswordPolicies();
                $passwd_classcount = $c['mysql']['classcount'];
                
                $password_user = create_pass(10, $passwd_classcount);
                if ($this->add_user($dbn, $password_user, $password_user)) {
                    $msg->raise("INFO", "mysql", "L'utilisateur '$dbname' a été créé et les droits sur cette base de données lui ont été attribué.");
                } else {
                    $msg->raise("ALERT", "mysql", "L'utilisateur '$dbname' n'a pas pu être créé.<br>Allez à la page 'Utilisateurs Mysql' pour en créer manuellement.<br>Et n'oubliez pas de lui donner les droits sur la base de données.");
                }
            }
        } else {
            $msg->raise("ALERT", "mysql", "L'utilisateur '$dbname' n'a pas été automatiquement créé car il dépasse la limite de taille pour les utilisateurs qui est à $len<br>Allez à la page 'Utilisateurs Mysql' pour en créer un avec le nom que vous voulez.<br>Et n'oubliez pas de lui donner les droits sur la base de données.");
        }
        
        // checking for the phpmyadmin user
        $db->query("SELECT * FROM dbusers WHERE uid= ? AND enable='ADMIN';", array($cuid));
        if ($db->num_rows()) {
            $db->next_record();
            $myadm = $db->f("name");
            $password = $db->f("password");
        } else {
            $msg->raise("ERROR", "mysql", _("There is a problem with the special PhpMyAdmin user. Contact the administrator"));
            return false;
        }

        // Grant the special user every rights.
        if ($this->dbus->exec("CREATE DATABASE $dbname;")) { // secured: dbname is checked against ^[0-9a-z]*$
            $msg->log("mysql", "add_db", "Success: ".$dbn);
            // Ok, database does not exist, quota is ok and dbname is compliant. Let's proceed
            $db->query("INSERT INTO db (uid,login,pass,db,bck_mode) VALUES (?, ?, ?, ? ,0)", array($cuid, $myadm, $password, $dbname));
            $dbuser = $dbname;
            $dbname = str_replace('_', '\_', $dbname);
            $this->grant($dbname, $myadm, "ALL PRIVILEGES", $password);
            if (!empty($password_user)) {
                $this->grant($dbname, $dbuser, "ALL PRIVILEGES", $password_user);
            }
            $this->dbus->query("FLUSH PRIVILEGES;");
            return true;
        } else {
            $msg->log("mysql", "add_db", "Error: ".$dbn);
            $msg->raise("ERROR", "mysql", _("An error occured. The database could not be created"));
            return false;
        }
    }
    

    /**
     * Delete a database for the current user.
     * @param $dbname string Name of the database to delete. The db name is $user_$dbn
     * @return boolean if the database $user_$db has been successfully deleted, or FALSE if 
     *  an error occured, such as db does not exist.
     */
    function del_db($dbname) {
        global $db, $msg, $cuid;
        $msg->log("mysql", "del_db", $dbname);
        $db->query("SELECT uid FROM db WHERE db= ?;", array($dbname));
        if (!$db->next_record()) {
            $msg->raise("ERROR", "mysql", _("The database was not found. I can't delete it"));
            return false;
        }

        // Ok, database exists and dbname is compliant. Let's proceed
        $db->query("DELETE FROM size_db WHERE db= ?;", array($dbname));
        $db->query("DELETE FROM db WHERE uid= ? AND db= ? ;", array($cuid, $dbname));
        $this->dbus->query("DROP DATABASE $dbname;");

        $db_esc = str_replace('_', '\_', $dbname);
        $this->dbus->query("DELETE FROM mysql.db WHERE Db= ? ;",    array($db_esc));

        // We test if the user created with the database is associated with more than 1 database.
        $this->dbus->query("select User from mysql.db where User= ? ;", array($dbname));
        if (($this->dbus->num_rows()) == 0) {
            // If not we can delete it.
            $this->del_user($dbname, false, true );
        }
        return true;
    }


    /**
     * Set the backup parameters for the database $db
     * @param $dbn string database name
     * @param $bck_mode integer Backup mode (0 = none 1 = daily 2 = weekly)
     * @param $bck_history integer How many backup should we keep ?
     * @param $bck_gzip boolean shall we compress the backup ?
     * @param $bck_dir string Directory relative to the user account where the backup will be stored
     * @return boolean true if the backup parameters has been successfully changed, false if not.
     */
    function put_mysql_backup($dbn, $bck_mode, $bck_history, $bck_gzip, $bck_dir) {
        global $db, $msg, $bro, $cuid;
        $msg->log("mysql", "put_mysql_backup");

        if (!variable_get('sql_allow_users_backups')) {
            $msg->raise("ERROR", "mysql", _("User aren't allowed to configure their backups"));
            return false;
        }

        $pos = strpos($dbn, '_');
        if ($pos === false) {
            $dbname = $dbn;
        } else {
            $dbncomp = explode('_', $dbn);
            $dbname = $dbn;
            $dbn = $dbncomp[1];
        }
        if (!preg_match("#^[0-9a-z]*$#", $dbn)) {
            $msg->raise("ERROR", "mysql", _("Database name can contain only letters and numbers"));
            return false;
        }
        $db->query("SELECT * FROM db WHERE uid= ? AND db= ? ;", array($cuid, $dbname));
        if (!$db->num_rows()) {
            $msg->raise("ERROR", "mysql", _("Database %s not found"), $dbn);
            return false;
        }
        $db->next_record();
        $bck_mode = intval($bck_mode);
        $bck_history = intval($bck_history);
        if ($bck_gzip) {
            $bck_gzip = "1";
        } else {
            $bck_gzip = "0";
        }
        if (!$bck_mode) {
            $bck_mode = "0";
        }
        if (!$bck_history) {
            $msg->raise("ALERT", "mysql", _("You have to choose how many backups you want to keep"));
            return false;
        }
        if (($bck_dir = $bro->convertabsolute($bck_dir, 0)) === false) { // return a full path or FALSE
            $msg->raise("ERROR", "mysql", _("Directory does not exist"));
            return false;
        }
        $db->query("UPDATE db SET bck_mode= ? , bck_history= ?, bck_gzip= ?, bck_dir= ? WHERE uid= ? AND db= ? ;", array($bck_mode, $bck_history, $bck_gzip, $bck_dir, $cuid, $dbname));
        return true;
    }


    /** 
     * Change the password of the user in MySQL
     * @param $password string new password (cleartext)
     * @return boolean TRUE if the password has been successfully changed, FALSE else.
     */
    function put_mysql_details($password) {
        global $db, $msg, $cuid, $admin;
        $msg->log("mysql", "put_mysql_details");
        $db->query("SELECT * FROM db WHERE uid= ?;", array($cuid));
        if (!$db->num_rows()) {
            $msg->raise("ERROR", "mysql", _("Database not found"));
            return false;
        }
        $db->next_record();
        $login = $db->f("login");

        if (!$password) {
            $msg->raise("ERROR", "mysql", _("The password is mandatory"));
            return false;
        }

        $len=variable_get("sql_max_username_length", 16);
        if (strlen($password) > $len) {
            $msg->raise("ERROR", "mysql", _("MySQL password cannot exceed %d characters"), $len);
            return false;
        }

        // Check this password against the password policy using common API : 
        if (is_callable(array($admin, "checkPolicy"))) {
            if (!$admin->checkPolicy("mysql", $login, $password)) {
                return false; // The error has been raised by checkPolicy()
            }
        }

        // Update all the "pass" fields for this user : 
        $db->query("UPDATE db SET pass= ? WHERE uid= ?;", array($password, $cuid));
        $this->dbus->query("SET PASSWORD FOR " .$login . "@" . $this->dbus->Client . "  = PASSWORD(?);", array($password));
        return true;
    }


    /**
     * Function used to grant SQL rights to users:
     * @base :database 
     * @user : database user
     * @rights : rights to apply ( optional, every rights apply given if missing
     * @pass : user password ( optional, if not given the pass stays the same, else it takes the new value )
     * @table : sql tables to apply rights
     * */
    function grant($base, $user, $rights = null, $pass = null, $table = '*') {
        global $msg, $db;
        $msg->log("mysql", "grant", $base . "-" . $rights . "-" . $user);

        if (!preg_match("#^[0-9a-z_\\*\\\\]*$#", $base)) {
            $msg->raise("ERROR", "mysql", _("Database name can contain only letters and numbers"));
            return false;
        } elseif (!$this->dbus->query("select db from db where db= ?;", array($base))) {
            $msg->raise("ERROR", "mysql", _("Database not found"));
            return false;
        }

        if ($rights == null) {
            $rights = 'ALL PRIVILEGES';
        } elseif (!preg_match("#^[a-zA-Z,\s]*$#", $rights)) {
            $msg->raise("ERROR", "mysql", _("Databases rights are not correct"));
            return false;
        }

        if (!preg_match("#^[0-9a-z]#", $user)) {
            $msg->raise("ERROR", "mysql", _("The username can contain only letters and numbers."));
            return false;
        }
        $db->query("select name from dbusers where name= ? ;", array($user));

        if (!$db->num_rows()) {
            $msg->raise("ERROR", "mysql", _("Database user not found"));
            return false;
        }

        $grant = "grant " . $rights . " on `" . $base . "`." . $table . " to " . $db->quote($user) . "@" . $db->quote($this->dbus->Client);

        if ($pass) {
            $grant .= " identified by " . $db->quote($pass) . ";";
        } else {
            $grant .= ";";
        }

        if (!$this->dbus->query($grant)) {
            $msg->raise("ERROR", "mysql", _("Could not grant rights"));
            return false;
        }
        return true;
    }


    /** 
     * Restore a sql database.
     * @param $file string The filename, relative to the user root dir, which contains a sql dump
     * @param $stdout boolean shall-we dump the error to stdout ? 
     * @param $id integer The ID of the database to dump to.
     * @return boolean TRUE if the database has been restored, or FALSE if an error occurred
     */
    function restore($file, $stdout, $id) {
        global $msg, $bro;
        if (empty($file)) {
            $msg->raise("ERROR", "mysql", _("No file specified"));
            return false;
        }
        if (!$r = $this->get_mysql_details($id)) {
            return false;
        }
        if (!($fi = $bro->convertabsolute($file, 0))) {
            $msg->raise("ERROR", "mysql", _("File not found"));
            return false;
        }
        if (!file_exists($fi)) {
            $msg->raise("ERROR", "mysql", _("File not found"));
            return false;
        }

        if (substr($fi, -3) == ".gz") {
            $exe = "/bin/gzip -d -c <" . escapeshellarg($fi) . " | /usr/bin/mysql -h" . escapeshellarg($this->dbus->Host) . " -u" . escapeshellarg($r["login"]) . " -p" . escapeshellarg($r["pass"]) . " " . escapeshellarg($r["db"]);
        } elseif (substr($fi, -4) == ".bz2") {
            $exe = "/usr/bin/bunzip2 -d -c <" . escapeshellarg($fi) . " | /usr/bin/mysql -h" . escapeshellarg($this->dbus->Host) . " -u" . escapeshellarg($r["login"]) . " -p" . escapeshellarg($r["pass"]) . " " . escapeshellarg($r["db"]);
        } else {
            $exe = "/usr/bin/mysql -h" . escapeshellarg($this->dbus->Host) . " -u" . escapeshellarg($r["login"]) . " -p" . escapeshellarg($r["pass"]) . " " . escapeshellarg($r["db"]) . " <" . escapeshellarg($fi);
        }
        $exe .= " 2>&1";

        echo "<code><pre>";
        $ret = 0;
        if ($stdout) {
            passthru($exe, $ret);
        } else {
            exec($exe, $ret);
        }
        echo "</pre></code>";
        if ($ret != 0) {
            return false;
        } else {
            return true;
        }
    }


    /** 
     * Get the size of a database
     * @param $dbname name of the database
     * @return integer database size
     * @access private
     */
    function get_db_size($dbname) {
        $this->dbus->query("SHOW TABLE STATUS FROM $dbname;");
        $size = 0;
        while ($this->dbus->next_record()) {
            $size += $this->dbus->f('Data_length') + $this->dbus->f('Index_length');
            if ($this->dbus->f('Engine') != 'InnoDB') {
                $size += $this->dbus->f('Data_free');
            }
        }
        return $size;
    }


    /**
     * Returns the list of database users of an account
     */
    function get_userslist($all = null) {
        global $db, $msg, $cuid;
        $msg->debug("mysql", "get_userslist");
        $c = array();
        if (!$all) {
            $db->query("SELECT name FROM dbusers WHERE uid= ? and enable not in ('ADMIN','HIDDEN') ORDER BY name;", array($cuid));
        } else {
            $db->query("SELECT name FROM dbusers WHERE uid= ? ORDER BY name;", array($cuid));
        }
        while ($db->next_record()) {
            $pos = strpos($db->f("name"), "_");
            if ($pos === false) {
                $c[] = array("name" => ($db->f("name")));
            } else {
                $c[] = array("name" => ($db->f("name")));
                //$c[]=array("name"=>substr($db->f("name"),strpos($db->f("name"),"_")+1));
            }
        }

        return $c;
    }

    
    function get_defaultsparam($dbn) {
        global $db, $msg, $cuid;
        $msg->debug("mysql", "getdefaults");

        $dbu = $dbn;
        $r = array();
        $dbn = str_replace('_', '\_', $dbn);
        $this->dbus->query("Select * from mysql.db where Db= ? and User!= ? ;", array($dbn, $cuid."_myadm"));

        if (!$this->dbus->num_rows()) {
            $msg->raise("ERROR", "mysql",_("Database not found"));
            return false;
        }

        $listRights = array('Select', 'Insert', 'Update', 'Delete', 'Create', 'Drop', 'References', 'Index', 'Alter', 'Create_tmp_table', 'Lock_tables', 'Create_view', 'Show_view', 'Create_routine', 'Alter_routine', 'Execute', 'Event', 'Trigger');
        while ($this->dbus->next_record()) {
            // rTmp is the array where we put the informations from each loop, added to array $r
            $rTmp = array();
            $variable = $this->dbus->Record;
            
            $dbu = $variable['User'];
            
            $rTmp['Host'] = $this->dbus->f('Host');
            $rTmp['Rights']='All';
            
            foreach ($listRights as $v) {
                $right = $v."_priv";
                if ($this->dbus->f($right) !== "Y") {
                    $rTmp['Rights'] = 'NotAll';
                    break;
                }
            }
            
            if (!$db->query("SELECT name,password from dbusers where name= ? ;", array($dbu))) {
                $msg->raise("ERROR", "mysql",_("Database not found")." (3)");
                return false;
            }
            
            if (!$db->num_rows()) {
                $msg->raise("ERROR", "mysql",_("Database not found")." (4)");
                return false;
            }
            
            $db->next_record();
            $rTmp['user'] = $db->f('name');
            $rTmp['password'] = $db->f('password');
            
            $r[] = $rTmp;
            
        } // endwhile
        return $r;
    }
    

    /**
     * Create a new user in MySQL rights tables
     * @param $usern the username (we will add _[alternc-account] to it)
     * @param string $password The password for this username
     * @param string $passconf The password confirmation
     * @return boolean if the user has been created in MySQL or FALSE if an error occurred
     */
    function add_user($usern, $password, $passconf) {
        global $db, $msg, $mem, $cuid, $admin;
        $msg->log("mysql", "add_user", $usern);

        $usern = trim($usern);
        $login = $mem->user["login"];
        if ($login != $usern) {
            $user = $login . "_" . $usern;
        } else {
            $user = $usern;
        }
        if (!$usern) {
            $msg->raise("ALERT", "mysql", _("The username is mandatory"));
            return false;
        }
        if (!$password) {
            $msg->raise("ALERT", "mysql", _("The password is mandatory"));
            return false;
        }
        if (!preg_match("#^[0-9a-z]#", $usern)) {
            $msg->raise("ERROR", "mysql", _("The username can contain only letters and numbers"));
            return false;
        }

        // We check the length of the COMPLETE username, not only the part after _
        $len=variable_get("sql_max_username_length", 16);
        if (strlen($user) > $len) {
            $msg->raise("ERROR", "mysql", _("MySQL username cannot exceed %d characters"), $len);
            return false;
        }
        $db->query("SELECT * FROM dbusers WHERE name= ? ;", array($user));
        if ($db->num_rows()) {
            $msg->raise("ERROR", "mysql", _("The database user already exists"));
            return false;
        }
        if ($password != $passconf || !$password) {
            $msg->raise("ERROR", "mysql", _("The passwords do not match"));
            return false;
        }

        // Check this password against the password policy using common API : 
        if (is_callable(array($admin, "checkPolicy"))) {
            if (!$admin->checkPolicy("mysql", $user, $password)) {
                return false; // The error has been raised by checkPolicy()
            }
        }

        // We add him to the user table 
        $db->query("INSERT INTO dbusers (uid,name,password,enable) VALUES( ?, ?, ?, 'ACTIVATED');", array($cuid, $user, $password));

        $this->grant("*", $user, "USAGE", $password);
        return true;
    }


    /**
     * Change a user's MySQL password
     * @param $usern the username 
     * @param $password The password for this username
     * @param $passconf The password confirmation
     * @return boolean if the password has been changed in MySQL or FALSE if an error occurred
     */
    function change_user_password($usern, $password, $passconf) {
        global $db, $msg, $cuid, $admin;
        $msg->log("mysql", "change_user_pass", $usern);

        $usern = trim($usern);
        if ($password != $passconf || !$password) {
            $msg->raise("ERROR", "mysql", _("The passwords do not match"));
            return false;
        }

        // Check this password against the password policy using common API : 
        if (is_callable(array($admin, "checkPolicy"))) {
            if (!$admin->checkPolicy("mysql", $usern, $password)) {
                return false; // The error has been raised by checkPolicy()
            }
        }
        $this->dbus->query("SET PASSWORD FOR " . $db->quote($usern) . "@" . $db->quote($this->dbus->Client) . " = PASSWORD(?);", array($password));
        $db->query("UPDATE dbusers set password= ? where name= ? and uid= ? ;", array($password, $usern, $cuid));
        return true;
    }


    /**
     * Delete a user in MySQL rights tables
     * @param $user the username (we will add "[alternc-account]_" to it) to delete
     * @param integer $all
     * @return boolean if the user has been deleted in MySQL or FALSE if an error occurred
     */
    function del_user($user, $all = false, $caller_is_deldb = false) {
        global $db, $msg, $cuid;
        $msg->log("mysql", "del_user", $user);
        if (!preg_match("#^[0-9a-z]#", $user)) {
            $msg->raise("ERROR", "mysql", _("The username can contain only letters and numbers"));
            return false;
        }
        if (!$all) {
            $db->query("SELECT name FROM dbusers WHERE name= ? and enable not in ('ADMIN','HIDDEN');", array($user));
        } else {
            $db->query("SELECT name FROM dbusers WHERE uid= ? ;", array($cuid));
        }

        if (!$db->num_rows()) {
            if (! $caller_is_deldb )
                $msg->raise("ERROR", "mysql", _("The username was not found"));

            return false;
        }
        $db->next_record();
        $login = $db->f("name");

        // Ok, database exists and dbname is compliant. Let's proceed
        $this->dbus->query("REVOKE ALL PRIVILEGES ON *.* FROM " . $db->quote($user) . "@" . $db->quote($this->dbus->Client) . ";");
        $this->dbus->query("DELETE FROM mysql.db WHERE User= ? AND Host= ? ;", array($user, $this->dbus->Client));
        $this->dbus->query("DELETE FROM mysql.user WHERE User= ? AND Host= ? ;", array($user, $this->dbus->Client));
        $this->dbus->query("FLUSH PRIVILEGES");

        $db->query("DELETE FROM dbusers WHERE uid= ? AND name= ? ;", array($cuid, $user));

        if ( $caller_is_deldb )
            $msg->raise("INFO", "mysql", _("The user '%s' has been successfully deleted"), $user);
        
        return true;
    }


    /**
     * Return the list of the database rights of user $user
     * @param $user the username 
     * @return array An array of database name and rights
     */
    function get_user_dblist($user) {
        global $db, $msg;

        $this->dbus->query("SELECT * FROM mysql.user WHERE User= ? AND Host= ? ;", array($user, $this->dbus->Client));
        if (!$this->dbus->next_record()) {
            $msg->raise("ERROR", 'mysql', _("This user does not exist in the MySQL/User database"));
            return false;
        }

        $r = array();
        $db->free();
        $dblist = $this->get_dblist();
        foreach ($dblist as $tab) {
            $dbname = str_replace('_', '\_', $tab["db"]);
            $this->dbus->query("SELECT * FROM mysql.db WHERE User= ? AND Host= ? AND Db= ? ;", array($user, $this->dbus->Client, $dbname));
            if ($this->dbus->next_record()) {
                $r[] = array("db" => $tab["db"], "select" => $this->dbus->f("Select_priv"), "insert" => $this->dbus->f("Insert_priv"), "update" => $this->dbus->f("Update_priv"), "delete" => $this->dbus->f("Delete_priv"), "create" => $this->dbus->f("Create_priv"), "drop" => $this->dbus->f("Drop_priv"), "references" => $this->dbus->f("References_priv"), "index" => $this->dbus->f("Index_priv"), "alter" => $this->dbus->f("Alter_priv"), "create_tmp" => $this->dbus->f("Create_tmp_table_priv"), "lock" => $this->dbus->f("Lock_tables_priv"),
                "create_view" => $this->dbus->f("Create_view_priv"),
                "show_view" => $this->dbus->f("Show_view_priv"),
                "create_routine" => $this->dbus->f("Create_routine_priv"),
                "alter_routine" => $this->dbus->f("Alter_routine_priv"),
                "execute" => $this->dbus->f("Execute_priv"),
                "event" => $this->dbus->f("Event_priv"),
                "trigger" => $this->dbus->f("Trigger_priv")
                );
            } else {
                $r[] = array("db" => $tab['db'], "select" => "N", "insert" => "N", "update" => "N", "delete" => "N", "create" => "N", "drop" => "N", "references" => "N", "index" => "N", "alter" => "N", "create_tmp" => "N", "lock" => "N", "create_view" => "N", "show_view" => "N", "create_routine" => "N", "alter_routine" => "N", "execute" => "N", "event" => "N", "trigger" => "N");
            }
        }
        return $r;
    }


    /**
     * Set the access rights of user $user to database $dbn to be rights $rights
     * @param $user the username to give rights to
     * @param $dbn The database to give rights to
     * @param $rights The rights as an array of MySQL keywords (insert, select ...)
     * @return boolean TRUE if the rights has been applied or FALSE if an error occurred
     */
    function set_user_rights($user, $dbn, $rights) {
        global $msg;
        $msg->log("mysql", "set_user_rights");

        // We generate the rights array depending on the rights list:
        $strrights = "";
        for ($i = 0; $i < count($rights); $i++) {
            switch ($rights[$i]) {
            case "select":
                $strrights.="SELECT,";
                break;
            case "insert":
                $strrights.="INSERT,";
                break;
            case "update":
                $strrights.="UPDATE,";
                break;
            case "delete":
                $strrights.="DELETE,";
                break;
            case "create":
                $strrights.="CREATE,";
                break;
            case "drop":
                $strrights.="DROP,";
                break;
            case "references":
                $strrights.="REFERENCES,";
                break;
            case "index":
                $strrights.="INDEX,";
                break;
            case "alter":
                $strrights.="ALTER,";
                break;
            case "create_tmp":
                $strrights.="CREATE TEMPORARY TABLES,";
                break;
            case "lock":
                $strrights.="LOCK TABLES,";
                break;
            case "create_view":
                $strrights.="CREATE VIEW,";
                break;
            case "show_view":
                $strrights.="SHOW VIEW,";
                break;
            case "create_routine":
                $strrights.="CREATE ROUTINE,";
                break;
            case "alter_routine":
                $strrights.="ALTER ROUTINE,";
                break;
            case "execute":
                $strrights.="EXECUTE,";
                break;
            case "event":
                $strrights.="EVENT,";
                break;
            case "trigger":
                $strrights.="TRIGGER,";
                break;
            }
        }

        // We reset all user rights on this DB : 
        $dbname = str_replace('_', '\_', $dbn);
        $this->dbus->query("SELECT * FROM mysql.db WHERE User = ? AND Db = ?;", array($user, $dbname));

        if ($this->dbus->num_rows()) {
            $this->dbus->query("REVOKE ALL PRIVILEGES ON `".$dbname."`.* FROM ".$this->dbus->quote($user)."@" . $this->dbus->quote($this->dbus->Client) . ";");
        }
        if ($strrights) {
            $strrights = substr($strrights, 0, strlen($strrights) - 1);
            $this->grant($dbname, $user, $strrights);
        }
        $this->dbus->query("FLUSH PRIVILEGES");
        return TRUE;
    }

    /** 
     * list of all possible SQL rights
     */
    function available_sql_rights() {
        return Array('select', 'insert', 'update', 'delete', 'create', 'drop', 'references', 'index', 'alter', 'create_tmp', 'lock', 'create_view', 'show_view', 'create_routine', 'alter_routine', 'execute', 'event', 'trigger');
    }


    /** 
     * Hook function called by the lxc class to set mysql_host and port 
     * parameters 
     * @access private
     */
    function hook_lxc_params($params) {
        global $msg;
        $msg->log("mysql", "alternc_get_quota");
        $p = array();
        if (isset($this->dbus["Host"]) && $this->dbus["Host"] != "") {
            $p["mysql_host"] = $this->dbus["Host"];
            $p["mysql_port"] = 3306;
        }
        return $p;
    }


    /** 
     * Hook function called by the quota class to compute user used quota
     * Returns the used quota for the $name service for the current user.
     * @param $name string name of the quota
     * @return integer the number of service used or false if an error occured
     * @access private
     */
    function hook_quota_get() {
        global $msg, $mem, $quota;
        $msg->debug("mysql", "alternc_get_quota");
        $q = Array("name" => "mysql", "description" => _("MySQL Databases"), "used" => 0);
        $c = $this->get_dblist();
        if (is_array($c)) {
            $q['used'] = count($c);
            $q['sizeondisk'] = $quota->get_size_db_sum_user($mem->user["login"])/1024;
        }
        return $q;
    }


    /** 
     * Hook function called when a user is created.
     * AlternC's standard function that create a member
     * @access private
     */
    function alternc_add_member() {
        global $db, $msg, $cuid, $mem;
        $msg->log("mysql", "alternc_add_member");
        // checking for the phpmyadmin user
        $db->query("SELECT name,password FROM dbusers WHERE uid= ? AND enable='ADMIN';", array($cuid));
        if ($db->num_rows()) {
            $myadm = $db->f("name");
            $password = $db->f("password");
        } else {
            $myadm = $cuid . "_myadm";
            $password = create_pass();
        }

        $db->query("INSERT INTO dbusers (uid,name,password,enable) VALUES (?, ?, ?, 'ADMIN');", array($cuid, $myadm, $password));

        return true;
    }


    /** 
     * Hook function called when a user is deleted.
     * AlternC's standard function that delete a member
     * @access private
     */
    function alternc_del_member() {
        global $msg;
        $msg->log("mysql", "alternc_del_member");
        $c = $this->get_dblist();
        if (is_array($c)) {
            for ($i = 0; $i < count($c); $i++) {
                $this->del_db($c[$i]["name"]);
            }
        }
        $d = $this->get_userslist(1);
        if (!empty($d)) {
            for ($i = 0; $i < count($d); $i++) {
                $this->del_user($d[$i]["name"], 1,true);
            }
        }
        return true;
    }


    /** 
     * Hook function called when a user is logged out.
     * We just remove the cookie created in admin/sql_admin.php
     * a @access private
    */
    function alternc_del_session() {
        $_SESSION['PMA_single_signon_user'] = '';
        $_SESSION['PMA_single_signon_password'] = '';
        $_SESSION['PMA_single_signon_host'] = '';
    }


    /**
     * Exports all the mysql information of an account
     * @access private
     * EXPERIMENTAL 'sid' function ;) 
     */
    function alternc_export_conf() {
        // TODO don't work with separated sql server for dbusers
        global $db, $msg, $cuid;
        $msg->log("mysql", "export");
        $db->query("SELECT login, pass, db, bck_mode, bck_dir, bck_history, bck_gzip FROM db WHERE uid= ? ;", array($cuid));
        $str = "";
        if ($db->next_record()) {
            $str.=" <sql>\n";
            $str.="   <login>" . $db->Record["login"] . "</login>\n";
            $str.="   <pass>" . $db->Record["pass"] . "</pass>\n";
            do {
                $filename = $tmpdir . "/mysql." . $db->Record["db"] . ".sql.gz"; // FIXME not used
                $str.="   <database>" . ($db->Record["db"]) . "</database>\n";
                $str.="   <password>" . ($db->Record["pass"]) . "</password>\n";
                if ($s["bck_mode"] != 0) { // FIXME what is $s ?
                    $str.="   <backup-mode>" . ($db->Record["bck_mode"]) . "</backup-mode>\n";
                    $str.="   <backup-dir>" . ($db->Record["bck_dir"]) . "</backup-dir>\n";
                    $str.="   <backup-history>" . ($db->Record["bck_history"]) . "</backup-history>\n";
                    $str.="   <backup-gzip>" . ($db->Record["bck_gzip"]) . "</backup-gzip>\n";
                }
            } while ($db->next_record());
            $str.=" </sql>\n";
        }
        return $str;
    }


    /**
     * Exports all the mysql databases a of give account to $dir directory
     * @access private
     * EXPERIMENTAL 'sid' function ;) 
     */
    function alternc_export_data($dir) {
        global $db, $msg, $cuid;
        $msg->log("mysql", "export_data");
        $db->query("SELECT db.login, db.pass, db.db, dbusers.name FROM db,dbusers WHERE db.uid= ?  AND dbusers.uid=db.uid;", array($cuid));
        $dir.="sql/";
        if (!is_dir($dir)) {
            if (!mkdir($dir)) {
                $msg->raise("ERROR", 'mysql', _("The directory could not be created"));
            }
        }
        // on exporte toutes les bases utilisateur.
        while ($db->next_record()) {
            $filename = $dir . "mysql." . $db->Record["db"] . "." . date("H:i:s") . ".sql.gz";
            exec("/usr/bin/mysqldump --defaults-file=/etc/alternc/my.cnf --add-drop-table --allow-keywords -Q -f -q -a -e " . escapeshellarg($db->Record["db"]) . " |/bin/gzip >" . escapeshellarg($filename));
        }
    }


    /**
     * Return the size of each databases in a SQL Host given in parameter
     * @param $db_name the human name of the host
     * @param $db_host the host hosting the SQL databases
     * @param $db_login the login to access the SQL db
     * @param $db_password the password to access the SQL db
     * @param $db_client the client to access the SQL db
     * @return an array associating the name of the databases to their sizes : array(dbname=>size)
     */
    function get_dbus_size($db_name, $db_host, $db_login, $db_password, $db_client) {
        global $msg;
        $msg->debug("mysql", "get_dbus_size", $db_host);

        $this->dbus = new DB_Sql("mysql",$db_host,$db_login,$db_password);

        $this->dbus->query("SHOW DATABASES;");
        $alldb=array();
        while ($this->dbus->next_record()) {
            $alldb[] = $this->dbus->f("Database");
        }

        $res = array();
        foreach($alldb as $dbname) {
            $c = $this->dbus->query("SHOW TABLE STATUS FROM $dbname;");
            $size = 0;
            while ($this->dbus->next_record()) {
                $size+=$this->dbus->f("Data_length") + $this->dbus->f("Index_length");
            }
            $res["$dbname"] = "$size";
        }
        return $res;
    }

} /* Class m_mysql */
