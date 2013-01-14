<?php
/*
   $Id: m_mysql.php,v 1.35 2005/12/18 09:51:32 benjamin Exp $
   ----------------------------------------------------------------------
   AlternC - Web Hosting System
   Copyright (C) 2002 by the AlternC Development Team.
   http://alternc.org/
   ----------------------------------------------------------------------
   Based on:
   Valentin Lacambre's web hosting softwares: http://altern.org/
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
   Original Author of file: Benjamin Sonntag
   Purpose of file: Manage mysql database for users.
   ----------------------------------------------------------------------
 */
/**
 * MySQL user database management for AlternC.
 * This class manage user's databases in MySQL, and user's MySQL accounts.
 * 
 * @copyright    AlternC-Team 2002-2005 http://alternc.org/
 */

class DB_users extends DB_Sql {
  var $Host,$HumanHostname,$User,$Password,$Client;

  /**
   * Creator
   */
  function DB_users() {

# Use the dbusers file if exist, else use default alternc configuration
    if ( is_readable("/etc/alternc/dbusers.cnf") ) {
      $mysqlconf=file_get_contents("/etc/alternc/dbusers.cnf");
    } else {
      $mysqlconf=file_get_contents("/etc/alternc/my.cnf");
    }
    $mysqlconf=explode("\n",$mysqlconf);

# Read the configuration
    foreach ($mysqlconf as $line) {
# First, read the "standard" configuration
      if (preg_match('/^([A-Za-z0-9_]*) *= *"?(.*?)"?$/', trim($line), $regs)) {
        switch ($regs[1]) {
          case "user":
            $user = $regs[2];
          break;
          case "password":
            $password = $regs[2];
          break;
          case "host":
            $host = $regs[2];
          break;
        }
      }
# Then, read specific alternc configuration
      if (preg_match('/^#alternc_var ([A-Za-z0-9_]*) *= *"?(.*?)"?$/', trim($line), $regs)) {
        $$regs[1]=$regs[2];
      }
    }

# Set value of human_host if unset
    if (! isset($human_hostname) || empty($human_hostname)) {
      if ( checkip($host) || checkipv6($host) ) {
        $human_hostname = gethostbyaddr($host);
      } else {
        $human_hostname = $host;
      }
    }

# Create the object
    $this->Host     = $host;
    $this->User     = $user;
    $this->Password = $password;
    $this->Client   = $GLOBALS['L_MYSQL_CLIENT'];
    // TODO BUG BUG BUG
    // c'est pas étanche : $db se retrouve avec Database de $sql->dbu . Danger, faut comprendre pourquoi
    // Si on veux que ca marche, il faut Database=alternc.
    //$this->Database = "mysql";
    $this->Database = "alternc";
    $this->HumanHostname = $human_hostname;

  }
}


class m_mysql {
  var $dbus;

  /*---------------------------------------------------------------------------*/
  /** Constructor
   * m_mysql([$mid]) Constructeur de la classe m_mysql, initialise le membre concerne
   */
  function m_mysql() {
    $this->dbus = new DB_users();
  }


  /* ----------------------------------------------------------------- */
  /**
   * Password kind used in this class (hook for admin class)
   */
  function alternc_password_policy() {
    return array("mysql"=>"MySQL users");
  }


  /*---------------------------------------------------------------------------*/
  /** Get the list of the database for the current user.
   * @return array returns an associative array as follow : <br>
   *  "db" => database name "bck" => backup mode for this db 
   *  "dir" => Backup folder.
   *  Returns an array (empty) if no databases
   */
  function get_dblist() {
    global $db,$err,$bro,$cuid;
    $err->log("mysql","get_dblist");
    $db->free();
    $db->query("SELECT login,pass,db, bck_mode, bck_dir FROM db WHERE uid='$cuid' ORDER BY db;");
    $c=array();
    while ($db->next_record()) {
      list($dbu,$dbn)=split_mysql_database_name($db->f("db"));
      $c[]=array("db"=>$db->f("db"), "name"=>$db->f('db'),"bck"=>$db->f("bck_mode"), "dir"=>$db->f("bck_dir"), "login"=>$db->f("login"), "pass"=>$db->f("pass"));
    }
    return $c;
  }

  /*---------------------------------------------------------------------------*/
  /** Get the login and password of the special user able to connect to phpmyadmin
   * @return array returns an associative array with login and password 
   *  Returns FALSE if error
   */
  function php_myadmin_connect(){
    global $db,$cuid,$err;
    $err->log("mysql","php_myadmin_connect");
    $db->query("SELECT  name,password FROM dbusers WHERE uid='$cuid' and enable='ADMIN';");
    if (!$db->num_rows()) {
      $err->raise("mysql",_("Cannot connect to PhpMyAdmin"));
      return false;
    }
    $db->next_record();
    $info=array();
    $info[]=array(
        "login"=>$db->f("name"),
        "pass"=>$db->f("password")
        );
    return $info;
  }


  /*---------------------------------------------------------------------------*/
  /** Returns the details of a user's database.
   * $dbn is the name of the database (after the _) or nothing for the database "$user"
   * @return array returns an associative array as follow : 
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
    global $db,$err,$bro,$mem,$cuid;
    $root=getuserpath();
    $err->log("mysql","get_mysql_details");
    $pos=strpos($dbn,'_');
    if($pos === false){
      $dbname=$dbn;
    }else{
      $dbncomp=explode('_',$dbn);
      $dbname=$dbn;
      $dbn=$dbncomp[1];
    }
    $size=$this->get_db_size($dbname);
    $db->query("SELECT login,pass,db, bck_mode, bck_gzip, bck_dir, bck_history FROM db WHERE uid='$cuid' AND db='$dbname';");
    if (!$db->num_rows()) {
      $err->raise("mysql",_("Database %s not found"),$dbn);
      return array("enabled"=>false);
    }
    $c=array();
    $db->next_record();
    list($dbu,$dbn)=split_mysql_database_name($db->f("db"));
    return array("enabled"=>true,"login"=>$db->f("login"),"db"=>$db->f("db"), "name"=>$dbn,"bck"=>$db->f("bck_mode"), "dir"=>substr($db->f("bck_dir"),strlen($root)), "size"=>$size, "pass"=>$db->f("pass"), "history"=>$db->f("bck_history"), "gzip"=>$db->f("bck_gzip"));
  }

  function test_get_param($dbname){
    global $db,$err,$cuid;
    $db->query("SELECT ");


  }

  /*---------------------------------------------------------------------------*/
  /** Create a new database for the current user.
   * @param $dbn string Database name ($user_$dbn is the mysql db name)
   * @return TRUE if the database $user_$db has been successfully created, or FALSE if 
   * an error occured, such as over quota user.
   */
  function add_db($dbn) {
    global $db,$err,$quota,$mem,$cuid,$admin;
    $err->log("mysql","add_db",$dbn);
    $password_user="";
    if (!$quota->cancreate("mysql")) {
      $err->raise("mysql",_("Your databases quota is over. You cannot create more databases"));
      return false;
    }
    $pos=strpos($dbn,'_');
    if($pos === false){
      $dbname=$dbn;
    }else{
      $dbncomp=explode('_',$dbn);
      $dbname=$dbn;
      $dbn=$dbncomp[1];
      if (empty($dbn)) { // If nothing after the '_'
        $err->raise("mysql",_("Database can't have empty suffix"));
        return false;
      }
    }
    if (!preg_match("#^[0-9a-z]*$#",$dbn)) {
      $err->raise("mysql",_("Database name can contain only letters and numbers"));
      return false;
    }


    if (strlen($dbname) > 64) {
      $err->raise("mysql",_("Database name cannot exceed 64 characters"));
      return false;
    }
    $db->query("SELECT * FROM db WHERE db='$dbname';");
    if ($db->num_rows()) {
      $err->raise("mysql",_("Database %s already exists"),$dbn);
      return false;
    }

    $db->query("SELECT name from dbusers where name='".$dbname."' and enable='ACTIVATED' ;");
    if(!$db->num_rows()){
      $password_user=create_pass(8);
      if(!$this->add_user($dbn,$password_user,$password_user)){
      }
    }

    //checking for the phpmyadmin user
    $db->query("SELECT * FROM dbusers WHERE uid=$cuid AND enable='ADMIN';");
    if ($db->num_rows()) {
      $db->next_record();
      $myadm=$db->f("name");  
      $password=$db->f("password");  
    }else{
      $err->raise("mysql",_("There is a problem with the special PhpMyAdmin user. Contact the administrator"));
      return false;
    }

    //Grant the special user every rights.
    if ($this->dbus->query("CREATE DATABASE `$dbname`;")) {
      $err->log("mysql","add_db_succes",$dbn);
      // Ok, database does not exist, quota is ok and dbname is compliant. Let's proceed
      $db->query("INSERT INTO db (uid,login,pass,db,bck_mode) VALUES ('$cuid','$myadm','$password','$dbname',0);");
      $dbuser=$dbname;
      $dbname=str_replace('_','\_',$dbname);
      $this->grant($dbname,$myadm,"ALL PRIVILEGES",$password);
      if(!empty($password_user)){
        $this->grant($dbname,$dbuser,"ALL PRIVILEGES",$password_user);
      }
      $this->dbus->query("FLUSH PRIVILEGES;");
      return true;
    } else {
      $err->log("mysql","add_db",$dbn);
      $err->raise("mysql",_("An error occured. The database could not be created"));
      return false;
    }
  }


  /*---------------------------------------------------------------------------*/
  /** Delete a database for the current user.
   * @param $dbn string Name of the database to delete. The db name is $user_$dbn
   * @return TRUE if the database $user_$db has been successfully deleted, or FALSE if 
   *  an error occured, such as db does not exist.
   */
  function del_db($dbn) {
    global $db,$err,$mem,$cuid;
    $err->log("mysql","del_db",$dbn);
    $dbname=addslashes($dbn);
    $db->query("SELECT uid FROM db WHERE db='$dbname';");
    if (!$db->num_rows()) {
      $err->raise("mysql",_("The database was not found. I can't delete it"));
      return false;
    }
    $db->next_record();

    // Ok, database exists and dbname is compliant. Let's proceed
    $db->query("DELETE FROM size_db WHERE db='$dbname';");
    $db->query("DELETE FROM db WHERE uid='$cuid' AND db='$dbname';");
    $this->dbus->query("DROP DATABASE `$dbname`;");

    $db_esc=str_replace('_','\_',$dbname);
    $db->query("select User from mysql.db where User='".$dbname."' and Db='".$db_esc."' and (Select_priv='Y' or Insert_priv='Y' or Update_priv='Y' or Delete_priv='Y' or Create_priv='Y' or Drop_priv='Y' or References_priv='Y' or Index_priv='Y' or Alter_priv='Y' or Create_tmp_table_priv='Y' or Lock_tables_priv='Y');");
    if(!$db->num_rows()){
      $this->del_user($dbname);
    }
    return true;
  }




  /*---------------------------------------------------------------------------*/
  /** Set the backup parameters for the database $db
   * @param $db string database name
   * @param $bck_mode integer Backup mode (0 = none 1 = daily 2 = weekly)
   * @param $bck_history integer How many backup should we keep ?
   * @param $bck_gzip boolean shall we compress the backup ?
   * @param $bck_dir string Directory relative to the user account where the backup will be stored
   * @return boolean true if the backup parameters has been successfully changed, false if not.
   */
  function put_mysql_backup($dbn,$bck_mode,$bck_history,$bck_gzip,$bck_dir) {
    global $db,$err,$mem,$bro,$cuid;
    $err->log("mysql","put_mysql_backup");
    $pos=strpos($dbn,'_');
    if($pos === false){
      $dbname=$dbn;
    }else{
      $dbncomp=explode('_',$dbn);
      $dbname=$dbn;
      $dbn=$dbncomp[1];
    }
    if (!preg_match("#^[0-9a-z]*$#",$dbn)) {
      $err->raise("mysql",_("Database name can contain only letters and numbers"));
      return false;
    }
    $db->query("SELECT * FROM db WHERE uid='$cuid' AND db='$dbname';");
    if (!$db->num_rows()) {
      $err->raise("mysql",_("Database %s not found"),$dbn);
      return false;
    }
    $db->next_record();
    $bck_mode=intval($bck_mode);
    $bck_history=intval($bck_history);
    if ($bck_gzip)
      $bck_gzip="1";
    else
      $bck_gzip="0";
    if (!$bck_mode)
      $bck_mode="0";
    if (!$bck_history) {
      $err->raise("mysql",_("You have to choose how many backups you want to keep"));
      return false;
    }
    if (($bck_dir=$bro->convertabsolute($bck_dir,0))===false) { // return a full path or FALSE
      $err->raise("mysql",_("Directory does not exist"));
      return false;
    }
    $db->query("UPDATE db SET bck_mode='$bck_mode', bck_history='$bck_history', bck_gzip='$bck_gzip', bck_dir='$bck_dir' WHERE uid='$cuid' AND db='$dbname';");
    return true;
  }


  /*---------------------------------------------------------------------------*/
  /** Change the password of the user in MySQL
   * @param $password string new password (cleartext)
   * @return boolean TRUE if the password has been successfully changed, FALSE else.
   */
  function put_mysql_details($password) {
    global $db,$err,$mem,$cuid,$admin;
    $err->log("mysql","put_mysql_details");
    $db->query("SELECT * FROM db WHERE uid='$cuid';");
    if (!$db->num_rows()) {
      $err->raise("mysql",_("Database not found"));
      return false;
    }
    $db->next_record();
    $login=$db->f("login");

    if (!$password) {
      $err->raise("mysql",_("The password is mandatory"));
      return false;      
    }

    if (strlen($password)>16) {
      $err->raise("mysql",_("MySQL password cannot exceed 16 characters"));
      return false;
    }

    // Check this password against the password policy using common API : 
    if (is_callable(array($admin,"checkPolicy"))) {
      if (!$admin->checkPolicy("mysql",$login,$password)) {
        return false; // The error has been raised by checkPolicy()
      }
    }

    // Update all the "pass" fields for this user : 
    $db->query("UPDATE db SET pass='$password' WHERE uid='$cuid';");
    $this->dbus->query("SET PASSWORD FOR ".$login."@".$this->dbus->Client." = PASSWORD('$password');");
    return true;
  }

  /**
   * Function used to grant SQL rights to users:
   * @base :database 
   * @user : database user
   * @rights : rights to apply ( optional, every rights apply given if missing
   * @pass : user password ( optional, if not given the pass stays the same, else it takes the new value )
   * @table : sql tables to apply rights
   **/
  function grant($base,$user,$rights=null,$pass=null,$table='*'){
    global $err,$db;
    $err->log("mysql","grant",$base."-".$user);

    if(!preg_match("#^[0-9a-z_\\*\\\\]*$#",$base)){
      $err->raise("mysql",_("Database name can contain only letters and numbers"));
      return false;
    } elseif (!$db->query("select db from db where db='$base';")){
      $err->raise("mysql",_("Database not found"));
      return false; 
    }

    if($rights==null){
      $rights='ALL PRIVILEGES';
    }elseif(!preg_match("#^[a-zA-Z,\s]*$#",$rights)){
      $err->raise("mysql",_("Databases rights are not correct"));
      return false;
    }

    if (!preg_match("#^[0-9a-z]#",$user)) {
      $err->raise("mysql",_("The username can contain only letters and numbers."));
      return false;
    }
    $db->query("select name from dbusers where name='".$user."' ;");

    if(!$db->num_rows()){
      $err->raise("mysql",_("Database user not found"));
      return false;
    }
    if($rights == "FILE"){
      $grant="grant ".$rights." on ".$base.".".$table." to '".$user."'@'".$this->dbus->Client."'" ;
    }else{
      $grant="grant ".$rights." on `".$base."`.".$table." to '".$user."'@'".$this->dbus->Client."'" ;
    }

    if($pass){
      $grant .= " identified by '".$pass."';";
    }else{
      $grant .= ";";
    }
    if(!$this->dbus->query($grant)){
      $err->raise("mysql",_("Could not grant rights"));
      return false;
    }
    return true;

  }


  /* ----------------------------------------------------------------- */
  /** Restore a sql database.
   * @param $file string The filename, relative to the user root dir, which contains a sql dump
   * @param $stdout boolean shall-we dump the error to stdout ? 
   * @param $id integer The ID of the database to dump to.
   * @return boolean TRUE if the database has been restored, or FALSE if an error occurred
   */
  function restore($file,$stdout,$id) { 
    // TODO don't work with the separated sql serveur for dbusers
    global $err,$bro,$mem,$L_MYSQL_HOST;
    if (!$r=$this->get_mysql_details($id)) { 
      return false; 
    } 
    if (!($fi=$bro->convertabsolute($file,0))) {
      $err->raise("mysql",_("File not found"));
      return false; 
    }
    if (substr($fi,-3)==".gz") {
      $exe="/bin/gzip -d -c <".escapeshellarg($fi)." | /usr/bin/mysql -h".escapeshellarg($L_MYSQL_HOST)." -u".escapeshellarg($r["login"])." -p".escapeshellarg($r["pass"])." ".escapeshellarg($r["db"]); 
    } elseif (substr($fi,-4)==".bz2") { 
      $exe="/usr/bin/bunzip2 -d -c <".escapeshellarg($fi)." | /usr/bin/mysql -h".escapeshellarg($L_MYSQL_HOST)." -u".escapeshellarg($r["login"])." -p".escapeshellarg($r["pass"])." ".escapeshellarg($r["db"]); 
    } else { 
      $exe="/usr/bin/mysql -h".escapeshellarg($L_MYSQL_HOST)." -u".escapeshellarg($r["login"])." -p".escapeshellarg($r["pass"])." ".escapeshellarg($r["db"])." <".escapeshellarg($fi); 
    }
    $exe .= " 2>&1";

    echo "<code><pre>" ;
    if ($stdout) {
      passthru($exe,$ret);
    } else {
      exec ($exe,$ret);
    }
    echo "</pre></code>" ;
    if ($ret != 0) {
      return false ;
    } else {
      return true ;
    }
  }


  /* ----------------------------------------------------------------- */
  /** Get the size of a database
   * @param $dbname name of the database
   * @return integer database size
   * @access private
   */
  function get_db_size($dbname) {
    global $db,$err;

    $this->dbus->query("SHOW TABLE STATUS FROM `$dbname`;");
    $size = 0;
    while ($db->next_record()) {
      $size += $db->f('Data_length') + $db->f('Index_length');
      if ( $db->f('Engine') != 'InnoDB') $size += $db->f('Data_free');
    }
    return $size;
  }


  /* ------------------------------------------------------------ */
  /** 
   * Returns the list of database users of an account
   **/
  function get_userslist($all=null) {
    global $db,$err,$bro,$cuid;
    $err->log("mysql","get_userslist");
    $c=array();
    if(!$all){
   		$db->query("SELECT name FROM dbusers WHERE uid='$cuid' and enable not in ('ADMIN','HIDDEN') ORDER BY name;");
		}else{
   		$db->query("SELECT name FROM dbusers WHERE uid='$cuid' ORDER BY name;");
		}	
    while ($db->next_record()) {
      $pos=strpos($db->f("name"),"_");
      if($pos === false){
        $c[]=array("name"=>($db->f("name")));
      }else{
        $c[]=array("name"=>($db->f("name")));
        //$c[]=array("name"=>substr($db->f("name"),strpos($db->f("name"),"_")+1));
      }
    }

    return $c;
  }

  function get_defaultsparam($dbn){
    global $db,$err,$bro,$cuid;
    $err->log("mysql","getdefaults");

    $dbu=$dbn;
    $r=array();
    $dbn=str_replace('_','\_',$dbn);
    $q=$this->dbus->query("Select * from mysql.db where Db='".$dbn."' and User!='".$cuid."_myadm';");

    if(!$db->num_rows()){
      return $r;
    }
    while ($db->next_record()) {       
      $variable = $db->Record;     
      if($variable['User'] == $dbu){
        $r['Host']=$db->f('Host');		

        if($db->f('Select_priv') !== "Y"){
          return $r;
        } 
        if($db->f('Insert_priv') !== "Y"){
          return $r;
        } 
        if($db->f('Update_priv') !== "Y"){
          return $r;
        } 
        if($db->f('Delete_priv') !== "Y"){
          return $r;
        } 
        if($db->f('Create_priv') !== "Y"){
          return $r;
        } 
        if($db->f('Drop_priv') !== "Y"){
          return $r;
        } 
        if($db->f('References_priv') !== "Y"){
          return $r;
        } 
        if($db->f('Index_priv') !== "Y"){
          return $r;
        } 
        if($db->f('Alter_priv') !== "Y"){
          return $r;
        } 
        if($db->f('Create_tmp_table_priv') !== "Y"){
          return $r;
        } 
        if($db->f('Lock_tables_priv') !== "Y"){
          return $r;
        } 
        if($db->f('Create_view_priv') !== "Y"){
          return $r;
        } 
        if($db->f('Show_view_priv') !== "Y"){
          return $r;
        } 
        if($db->f('Create_routine_priv') !== "Y"){
          return $r;
        } 
        if($db->f('Alter_routine_priv') !== "Y"){
          return $r;
        } 
        if($db->f('Execute_priv') !== "Y"){
          return $r;
        } 
        if($db->f('Event_priv') !== "Y"){
          return $r;
        } 
        if($db->f('Trigger_priv') !== "Y"){
          return $r;
        } 
      }
    }//endwhile
    if(!$db->query("SELECT name,password from dbusers where name='".$dbu."';")){
      return $r;
    }

    if(!$db->num_rows()){
      return $r;
    }
    $db->next_record();
    $r['user']=$db->f('name');
    $r['password']=$db->f('password');
    return $r;

  }


  /* ------------------------------------------------------------ */
  /** 
   * Create a new user in MySQL rights tables
   * @param $usern the username (we will add _[alternc-account] to it)
   * @param $password The password for this username
   * @param $passconf The password confirmation
   * @return TRUE if the user has been created in MySQL or FALSE if an error occurred
   **/
  function add_user($usern,$password,$passconf) {
    global $db,$err,$quota,$mem,$cuid,$admin;
    $err->log("mysql","add_user",$usern);

    $usern=trim($usern);
    $login=$mem->user["login"];
    if($login != $usern){ 
      $user=addslashes($login."_".$usern);
    }else{
      $user=$usern;
    }
    $pass=addslashes($password);

    if (!$usern) {
      $err->raise("mysql",_("The username is mandatory"));
      return false;
    }
    if (!$pass) {
      $err->raise("mysql",_("The password is mandatory"));
      return false;
    }
    if (!preg_match("#^[0-9a-z]#",$usern)) {
      $err->raise("mysql",_("The username can contain only letters and numbers"));
      return false;
    }


    // We check the length of the COMPLETE username, not only the part after _
    if (strlen($user) > 16) {
      $err->raise("mysql",_("MySQL username cannot exceed 16 characters"));
      return false;
    }
    $db->query("SELECT * FROM dbusers WHERE name='$user';");
    if ($db->num_rows()) {
      $err->raise("mysql",_("The database user already exists"));
      return false;
    }
    if ($password != $passconf || !$password) {
      $err->raise("mysql",_("The passwords do not match"));
      return false;
    }

    // Check this password against the password policy using common API : 
    if (is_callable(array($admin,"checkPolicy"))) {
      if (!$admin->checkPolicy("mysql",$user,$password)) {
        return false; // The error has been raised by checkPolicy()
      }
    }

    // We add him to the user table 
    $db->query("INSERT INTO dbusers (uid,name,password,enable) VALUES($cuid,'$user','$password','ACTIVATED');");
    // We create the user account (the "file" right is the only one we need globally to be able to use load data into outfile)
    $this->grant("*",$user,"FILE",$pass);
    return true;
  }

  /* ------------------------------------------------------------ */
  /** 
   * Change a user's MySQL password
   * @param $usern the username 
   * @param $password The password for this username
   * @param $passconf The password confirmation
   * @return TRUE if the password has been changed in MySQL or FALSE if an error occurred
   **/
  function change_user_password($usern,$password,$passconf) {
    global $db,$err,$quota,$mem,$cuid,$admin;
    $err->log("mysql","change_user_pass",$usern);

    $usern=trim($usern);
    $user=addslashes($usern);
    $pass=addslashes($password);
    if ($password != $passconf || !$password) {
      $err->raise("mysql",_("The passwords do not match"));
      return false;
    }

    // Check this password against the password policy using common API : 
    if (is_callable(array($admin,"checkPolicy"))) {
      if (!$admin->checkPolicy("mysql",$user,$password)) {
        return false; // The error has been raised by checkPolicy()
      }
    }
    $db->query("SET PASSWORD FOR '".$user."'@'".$this->dbus->Client."' = PASSWORD('".$pass."');");
    $db->query("UPDATE dbusers set password='".$pass."' where name='".$usern."' and uid=$cuid ;");
    return true;
  }



  /* ------------------------------------------------------------ */
  /** 
   * Delete a user in MySQL rights tables
   * @param $user the username (we will add "[alternc-account]_" to it) to delete
   * @return TRUE if the user has been deleted in MySQL or FALSE if an error occurred
   **/
  function del_user($user,$all=null) {
    global $db,$err,$mem,$cuid,$L_MYSQL_DATABASE;
    $err->log("mysql","del_user",$user);
    if (!preg_match("#^[0-9a-z]#",$user)) {
      $err->raise("mysql",_("The username can contain only letters and numbers"));
      return false;
    }
		if(!$all){
    	$db->query("SELECT name FROM dbusers WHERE name='".$user."' and enable not in ('ADMIN','HIDDEN');");
		}else{
    	$db->query("SELECT name FROM dbusers WHERE name='".$user."' ;");
		}
    
    if (!$db->num_rows()) {
      $err->raise("mysql",_("The username was not found"));
      return false;
    }
    $db->next_record();
    $login=$db->f("name");

    // Ok, database exists and dbname is compliant. Let's proceed
    $db->query("REVOKE ALL PRIVILEGES ON *.* FROM '".$user."'@'".$this->dbus->Client."';");
    $db->query("DELETE FROM mysql.db WHERE User='".$user."' AND Host='".$this->dbus->Client."';");
    $db->query("DELETE FROM mysql.user WHERE User='".$user."' AND Host='".$this->dbus->Client."';");
    $db->query("FLUSH PRIVILEGES");
    $db->query("DELETE FROM dbusers WHERE uid='$cuid' AND name='".$user."';");
    return true;
  }


  /* ------------------------------------------------------------ */
  /** 
   * Return the list of the database rights of user $user
   * @param $user the username 
   * @return array An array of database name and rights
   **/

  function get_user_dblist($user){
    global $db,$err,$mem,$cuid;

    $r=array();
    $db->free();
    $dblist=$this->get_dblist();
    foreach($dblist as $tab){
      $pos=strpos($tab['db'],"_");
      if($pos === false){
        $this->dbus->query("SELECT * FROM mysql.db WHERE User='".$user."' AND Host='".$this->dbus->Client."' AND Db='".$tab["db"]."';");
      }else{
        $dbname=str_replace('_','\_',$tab['db']);
        $this->dbus->query("SELECT * FROM mysql.db WHERE User='".$user."' AND Host='".$this->dbus->Client."' AND Db='".$dbname."';");
      }	
      if ($this->dbus->next_record()){
        $r[]=array("db"=>$tab["db"], "select"=>$this->dbus->f("Select_priv"), "insert"=>$this->dbus->f("Insert_priv"),	"update"=>$this->dbus->f("Update_priv"), "delete"=>$this->dbus->f("Delete_priv"), "create"=>$this->dbus->f("Create_priv"), "drop"=>$this->dbus->f("Drop_priv"), "references"=>$this->dbus->f("References_priv"), "index"=>$this->dbus->f("Index_priv"), "alter"=>$this->dbus->f("Alter_priv"), "create_tmp"=>$this->dbus->f("Create_tmp_table_priv"), "lock"=>$this->dbus->f("Lock_tables_priv"),
        "create_view"=>$this->dbus->f("Create_view_priv"),
        "show_view"=>$this->dbus->f("Show_view_priv"),
        "create_routine"=>$this->dbus->f("Create_routine_priv"),
        "alter_routine"=>$this->dbus->f("Alter_routine_priv"),
        "execute"=>$this->dbus->f("Execute_priv"),
        "event"=>$this->dbus->f("Event_priv"),
        "trigger"=>$this->dbus->f("Trigger_priv")
        );
      }else{
        $r[]=array("db"=>$tab['db'], "select"=>"N", "insert"=>"N", "update"=>"N", "delete"=>"N", "create"=>"N", "drop"=>"N", "references"=>"N", "index"=>"N", "alter"=>"N", "Create_tmp"=>"N", "lock"=>"N","create_view"=>"N","show_view"=>"N","create_routine"=>"N","alter_routine"=>"N","execute"=>"N","event"=>"N","trigger"=>"N");

      }

    }	
    return $r;
  }

  /* ------------------------------------------------------------ */
  /** 
   * Set the access rights of user $user to database $dbn to be rights $rights
   * @param $user the username to give rights to
   * @param $dbn The database to give rights to
   * @param $rights The rights as an array of MySQL keywords (insert, select ...)
   * @return boolean TRUE if the rights has been applied or FALSE if an error occurred
   * 
   **/
  function set_user_rights($user,$dbn,$rights) {
    global $mem,$err,$db;
    $err->log("mysql","set_user_rights");

    $usern=addslashes($user);
    $dbname=addslashes($dbn);
    $dbname=str_replace('_','\_',$dbname);
    // On génère les droits en fonction du tableau de droits
    $strrights="";
    for( $i=0 ; $i<count($rights) ; $i++ ) {
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
    $this->dbus->query("SELECT * FROM mysql.db WHERE User = '$usern' AND Db = '$dbname';");
    if($this->dbus->num_rows())
      $this->dbus->query("REVOKE ALL PRIVILEGES ON `$dbname`.* FROM '$usern'@'".$this->dbus->Client."';");
    if( $strrights ){
      $strrights=substr($strrights,0,strlen($strrights)-1);
      $this->grant($dbname,$usern,$strrights);
    }
    $this->dbus->query("FLUSH PRIVILEGES");
    return TRUE;
  }

  function available_sql_rights(){
    return Array('select','insert','update','delete','create','drop','references','index','alter','create_tmp','lock','create_view','show_view','create_routine','alter_routine','execute','event','trigger');


  }


  /* ----------------------------------------------------------------- */
  /** Hook function called by the quota class to compute user used quota
   * Returns the used quota for the $name service for the current user.
   * @param $name string name of the quota
   * @return integer the number of service used or false if an error occured
   * @access private
   */
  function hook_quota_get() {
    global $err,$db,$cuid;
    $err->log("mysql","alternc_get_quota");
    $q=Array("name"=>"mysql", "description"=>_("MySQL Databases"), "used"=>0);
    $c=$this->get_dblist();
    if (is_array($c)) {
      $q['used']=count($c);
    }
    return $q;
  }

  /* ----------------------------------------------------------------- */
  /** Hook function called when a user is created.
   * AlternC's standard function that create a member
   * @access private
   */
  function alternc_add_member() {
    global $db,$err,$cuid,$mem;
    $err->log("mysql","alternc_add_member");
    //checking for the phpmyadmin user
    $db->query("SELECT name,password FROM dbusers WHERE uid=$cuid AND Type='ADMIN';");
    if ($db->num_rows()) {
      $myadm=$db->f("name");  
      $password=$db->f("password");  
    }else{
      $myadm=$cuid."_myadm";
      $password=create_pass(8); 
    }


    $db->query("INSERT INTO dbusers (uid,name,password,enable) VALUES ('$cuid','$myadm','$password','ADMIN');");

    return true;
  }




  /* ----------------------------------------------------------------- */
  /** Hook function called when a user is deleted.
   * AlternC's standard function that delete a member
   * @access private
   */
  function alternc_del_member() {
    global $db,$err,$cuid;
    $err->log("mysql","alternc_del_member");
    $c=$this->get_dblist();
    if (is_array($c)) {
      for($i=0;$i<count($c);$i++) {
        $this->del_db($c[$i]["name"]);
      }
    }
    $d=$this->get_userslist(1);
    if (!empty($d)) {
      for($i=0;$i<count($d);$i++) {
        $this->del_user($d[$i]["name"],1);
      }
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Hook function called when a user is logged out.
   * We just remove the cookie created in admin/sql_admin.php
   a @access private
   */
  function alternc_del_session() {
    setcookie("REMOTE_USER","");
    setcookie("REMOTE_PASSWORD","");
  }


  /* ----------------------------------------------------------------- */
  /**
   * Exporte all the mysql information of an account
   * @access private
   * EXPERIMENTAL 'sid' function ;) 
   */
  function alternc_export_conf() {
    //TODO don't work with separated sql server for dbusers
    global $db,$err,$cuid;
    $err->log("mysql","export");
    $db->query("SELECT login, pass, db, bck_mode, bck_dir, bck_history, bck_gzip FROM db WHERE uid='$cuid';");
    if ($db->next_record()) {
      $str.=" <sql>\n";
      $str.="   <login>".$db->Record["login"]."</login>\n";
      $str.="   <pass>".$db->Record["pass"]."</pass>\n";
      do {
        $filename=$tmpdir."/mysql.".$db->Record["db"].".sql.gz";
        $str.="   <database>".($db->Record["db"])."</database>\n";
        $str.="   <password>".($db->Record["pass"])."</password>\n";
        if ($s["bck_mode"]!=0) {
          $str.="   <backup-mode>".($db->Record["bck_mode"])."</backup-mode>\n";
          $str.="   <backup-dir>".($db->Record["bck_dir"])."</backup-dir>\n";
          $str.="   <backup-history>".($db->Record["bck_history"])."</backup-history>\n";
          $str.="   <backup-gzip>".($db->Record["bck_gzip"])."</backup-gzip>\n";
        }
      } while ($db->next_record());
      $str.=" </sql>\n";
    }
    return $str;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Exporte all the mysql databases a of give account to $dir directory
   * @access private
   * EXPERIMENTAL 'sid' function ;) 
   */
  function alternc_export_data ($dir){
    global $db, $err, $cuid,$mem;
    $err->log("mysql","export_data");
    $db->query("SELECT db.login, db.pass, db.db, dbusers.name FROM db,dbusers WHERE db.uid='$cuid' AND dbusers.uid=db.uid;");
    $dir.="sql/";
    if(!is_dir($dir)){
      if(!mkdir($dir)){
        $err->raise('mysql',_("The directory could not be created"));
      }
    }
    // on exporte toutes les bases utilisateur.
    while($db->next_record()){
      $filename=$dir."mysql.".$db->Record["db"].".".date("H:i:s").".sql.gz";
      exec ("/usr/bin/mysqldump --defaults-file=/etc/alternc/my.cnf --add-drop-table --allow-keywords -Q -f -q -a -e ".escapeshellarg($db->Record["db"])." |/bin/gzip >".escapeshellarg($filename));
    }
  }


} /* Class m_mysql */

?>
