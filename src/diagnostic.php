#!/usr/bin/php -q
<?php

/*
 * @todo : proper namespace autoloader
 */
include "Console.php";

/*
   $Id: diagnostic.php 2014/05/19 alban Exp $
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
   Original Author of file: alban 
   Purpose of file: Provide a diagnostic of the server state 
   ----------------------------------------------------------------------
 */


/*

   Handles diagnostics of an alternc server.

   Notes 
   The diagnostic files are located by default in /var/lib/alternc/diagnostics

*/

/**
 * Console aware class, encapsulates the Console CommandLine class
 */
class Alternc_Diagnostic_Console extends Console_CommandLine{}



/**
 * Uniform data component containing other components or real data
 */
class Alternc_Diagnostic_Data {
    
    public $index                       = array();
    public $data                        = array();
    public $type                        = "";
    public $metadata                    = null;
    
    
    const TYPE_ROOT                     = "root";
    const TYPE_DOMAIN                   = "service";
    const TYPE_SECTION                  = "section";
   
    
    public function __construct( $type, $sectionData = null) {
        $this->type                     = $type;
        if( $sectionData){
            $this->data                 = $sectionData;
        }
        
    }
    
    
    /**
     * 
     * @param array $options a module name => data
     * @return boolean
     */
    function addData( $name, Alternc_Diagnostic_Data $data){
        $this->index[]         = $name;
        $this->data[$name]    = $data;
        return true;
    }
    
    /**
     * @param array index
     */
    public function setIndex($index) {
        $this->index                    = $index;
        return $this;
    }

    /**
     * @return array
     */
    public function getIndex() {
        return $this->index;
    }
    
    /**
     * @param array data
     */
    public function setData($data) {
        $this->data                     = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getData() {
        return $this->data;
    }

        /**
     * @param string type
     */
    public function setType($type) {
        $this->type                     = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param array metadata
     */
    public function setMetadata($metadata) {
        $this->metadata                 = $metadata;
        return $this;
    }

    /**
     * @return array
     */
    public function getMetadata() {
        return $this->metadata;
    }

}

/**
 * 
 */
interface Alternc_Diagnostic_Service_Interface{
    
    function run();
    
}

/**
 * 
 */
abstract class Alternc_Diagnostic_Service_Abstract{
    
    /** @var Alternc_Diagnostic_Data*/
    protected $data;
    
    /** @var m_mysql */
    public $db;

    /** @var m_mysql */
    protected $mysql;

    /** @var m_mem */
    protected $mem;

    /** @var m_admin */
    protected $admin;

    /** @var m_authip */
    protected $authip;
    /** @var m_cron */
    protected $cron;

    /** @var m_dom */
    protected $dom;

    /** @var m_ftp */
    protected $ftp;

    /** @var m_hta */
    protected $hta;

    /** @var m_mail */
    protected $mail;

    /** @var m_quota */
    protected $quota;

    public function __construct() {
        $this->data                     = new Alternc_Diagnostic_Data(Alternc_Diagnostic_Data::TYPE_DOMAIN);
        
        global $db;
        $this->db                       = $db;
        
        global $mem;
        $this->mem                      = $mem;
        
        global $mysql;
        $this->mysql                    = $mysql;
        
        global $quota;
        $this->quota= $quota;

        global $mail;
        $this->mail= $mail;

        global $hta;
        $this->hta= $hta;

        global $ftp;
        $this->ftp= $ftp;

        global $dom;
        $this->dom= $dom;

        global $cron;
        $this->cron= $cron;

        global $authip;
        $this->authip= $authip;

        global $admin;
        $this->admin= $admin;

    }
    
    /**
     * 
     * @param string $cmd
     * @return array
     * @throws \Exception
     */
    protected function execCmd( $cmd ){
        exec(escapeshellcmd("$cmd")." 2>&1", $output, $return_var);
        if( 0 != $return_var ){
            throw new \Exception("Invalid return for command $cmd returned error code #$return_var with output :".  json_encode($output));
        }
        return $output;
    }

    
    protected function filterRegexp($pattern,$result){
        $returnArray                    = array();
        foreach ($result as $line) {
            $captures_count             = preg_match($pattern, $line, $matches);
            if($captures_count){
                array_shift($matches);
                $returnArray[]          = implode(" ", $matches);
            }
        }
        return $returnArray;
    }


    /**
     * @param Alternc_Diagnostic_Data data
     */
    public function setData($data) {
        $this->data                     = $data;
        return $this;
    }

    /**
     * @return Alternc_Diagnostic_Data
     */
    public function getData() {
        return $this->data;
    }
    
    /**
     * Utility for filling the service agent data holder
     * 
     * @param string $name
     * @param mixed $content
     * @return boolean
     */
    function addDataSection( $name, $content){
        
        $section                        = new Alternc_Diagnostic_Data(Alternc_Diagnostic_Data::TYPE_SECTION,$content);
        $this->data->addData($name, $section);
        return true;
        
    }

    
}
/**
 * Lists versions : php mysql posfix dovecot roundcubke squirrelmail courier mailman alternc-* acl quota sasl
 * 
 */
class Alternc_Diagnostic_Service_System 
    extends Alternc_Diagnostic_Service_Abstract
    implements Alternc_Diagnostic_Service_Interface
{

    public $name                        = "system";
    function run(){
        $this->addDataSection("ip list", $this->execCmd("ip a"));
        return $this->data;
    }
}
/**
 * Lists accounts
 * Checks root
 */
class Alternc_Diagnostic_Service_Ftp 
    extends Alternc_Diagnostic_Service_Abstract
    implements Alternc_Diagnostic_Service_Interface
{

    public $name                        = "ftp";
    function run(){
        return $this->data;
    }
}
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
    function run(){
        return $this->data;
    }

}
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
    function run(){
        return $this->data;
    }

}
/**
 * Lists databases
 * Lists users
 */
class Alternc_Diagnostic_Service_Mysql 
    extends Alternc_Diagnostic_Service_Abstract
    implements Alternc_Diagnostic_Service_Interface
{

    public $name                        = "mysql";
    function run(){
        return $this->data;
    }

}
/**
 * Lists mailing lists
 */
class Alternc_Diagnostic_Service_Mailman 
    extends Alternc_Diagnostic_Service_Abstract
    implements Alternc_Diagnostic_Service_Interface
{

    public $name                        = "mailman";
    function run(){
        return $this->data;
    }

}
/**
 * Lists members
 */
class Alternc_Diagnostic_Service_Panel 
    extends Alternc_Diagnostic_Service_Abstract
    implements Alternc_Diagnostic_Service_Interface
{

    public $name                        = "panel";
    function run(){
        return $this->data;
    }

}
/**
 * Lists vhosts 
 * Lists redirections
 * Checks vhosts
 * Checks redirections
 */
class Alternc_Diagnostic_Service_Web 
    extends Alternc_Diagnostic_Service_Abstract
    implements Alternc_Diagnostic_Service_Interface
{

    public $name                        = "web";
    const SECTION_APACHE2_VHOSTS        = "apache2 vhosts";
    const SECTION_APACHE2_MODULES       = "apache2 modules";
    function run(){        
        
        $this->addDataSection (self::SECTION_APACHE2_VHOSTS,$this->filterRegexp("/^[\D]*(\d{2,4}).* (.*) \(\/etc.*$/u", $this->execCmd("apache2ctl -S")));
        $this->addDataSection (self::SECTION_APACHE2_MODULES,$this->filterRegexp("/^[\W]*(\w+).*\(.*$/u", $this->execCmd("apache2ctl -M")));
        $this->addDataSection (self::SECTION_APACHE2_REDIRECTION,$this->mysql->query("SELECT domaine, valeur from sub_domaines where type='url';"));
        
        return $this->data;
    }

}

interface Alternc_Diagnostic_Format_Interface{
    
    /**
     * 
     * @param   mixed $file_reference
     *          Either a number or a string refering to the file
     * @return  Alternc_Diagnostic_Data A diagnostic file
     */
    function read( $file_reference );


    /**
     * Writes a Data object to file
     * 
     * @return boolean
     */
    function write();
    
}

class Alternc_Diagnostic_Format_Abstract {
    
    /**
     *
     * @var Alternc_Diagnostic_Data
     */
    public $data;

    /**
     *
     * @var Alternc_Diagnostic_Directory 
     */
    public $directory;
    
    /**
     * Files extension for the format
     * 
     * @var string
     */
    protected $extension;

    /**
     * @param string extension
     */
    public function setExtension($extension) {
        $this->extension                = $extension;
        return $this;
    }

    /**
     * @return string
     */
    public function getExtension() {
        return $this->extension;
    }

    public function __construct(Alternc_Diagnostic_Directory $directory) {
        
        $this->directory                = $directory;

    }
    
    /**
     * @param Alternc_Diagnostic_Data  data
     */
    public function setData(Alternc_Diagnostic_Data $data) {
        $this->data                     = $data;
        return $this;
    }

    /**
     * @return Alternc_Diagnostic_Data 
     */
    public function getData() {
        if(is_null($this->data)){
            throw new \Exception("Missing property 'data' in format instance");
        }
        return $this->data;
    }
    
    public function getFilename(){
        return $this->getDirectory()->getFile_path()."/".time().".".$this->getExtension();
    }

    /**
     * @param Alternc_Diagnostic_Directory directory
     */
    public function setDirectory($directory) {
        $this->directory = $directory;
        return $this;
    }

    /**
     * @return Alternc_Diagnostic_Directory
     */
    public function getDirectory() {
        if( null == $this->directory){
            throw new \Exception("Missing property 'directory' in format instance");
        }
        return $this->directory;
    }


}


class Alternc_Diagnostic_Directory {
    
    /**
     * Location of diagnostic files 
     * 
     * @var string
     */
    protected $file_path;

    public function __construct( $file_path) {
        if( null == $file_path){
            throw new \Exception("Empty file_path in Diagnostic Format handler");
        }
        if( !file_exists($file_path)){
            if( !mkdir($file_path, 0774, true)){
                throw new \Exception("Could not access path $file_path in Diagnostic Format handler");
            }
        }
        $this->file_path                = $file_path;
        
    }
    
    function getList( $max = null){
        
        $dir                    = new DirectoryIterator($this->file_path);
        
    }
 
    /**
     * @param string file_path
     */
    public function setFile_path($file_path) {
        $this->file_path                = $file_path;
        return $this;
    }

    /**
     * @return string
     */
    public function getFile_path() {
        if( null == $this->file_path){
            throw new \Exception("Missing property 'file_path' in format instance");
        }
        return $this->file_path;
    }

    
}

/**
 * JSON implementation of the format interface : writes, reads, compares
 */
class Alternc_Diagnostic_Format_Json 
    extends Alternc_Diagnostic_Format_Abstract
    implements Alternc_Diagnostic_Format_Interface
{

    /**
     * @inherit
     */
    public function __construct(Alternc_Diagnostic_Directory $directory) {
        parent::__construct($directory);
        $this->setExtension("json");
    }
    
    /**
     * @inherit
     */
    function read( $file_reference ){
        
    }
    
    
    /**
     * @inherit
     */
    function write(Alternc_Diagnostic_Data $data = null ){
        
        if( $data ){
            $this->setData($data);
        }
        $file_content                   = json_encode($this->getData());
        $filename                       = $this->getFilename();
        if(json_last_error()){
            throw new \Exception("Json conversion failed with error #".json_last_error()."for data".serialize($this->getData()));
       }
        if( ! file_put_contents($filename, $file_content) ){
            throw new \Exception("Failed to write in json format to file $filename for data".serialize($this->getData()));
        }
        return true;
    }
    
    
}

class Alternc_Diagnostic_Diff{
    
    /**
     * 
     * @param type $file_reference_1
     *          Either a number or a string refering to the file
     *          Default = Last file
     * @param type $file_reference_2
     *          Either a number or a string refering to the file
     *          Default = pre-last file
     */
    function compare($file_reference_1, $file_reference_2){
        
    }
    
    /**
     * Finds a file by reference or name
     * 
     * @param string $file_reference
     * @return Alternc_Diagnostic_Data Resulting data
     */
    function resolve( $file_reference){
        
    }
}

/**
 * Central service which provides the glue and intelligence for all parts
 */
class Alternc_Diagnostic_Manager{
    
    /**
     * @var Alternc_Diagnost_Format_Abstract
     */
    public $formatInstance;
    
    /**
     * @var Alternc_Diagnost_Directory
     */
    public $directoryInstance;

    /**
     * Constructor with dependancy injection
     * 
     * @param array $options
     * @throws \Exception
     */
    public function __construct($options) {
        // Attempts to retrieve formatInstance
        if (isset($options["formatInstance"]) && ! is_null($options["formatInstance"])) {
            $this->formatInstance       = $options["formatInstance"];
        } else {
            throw new \Exception("Missing parameter formatInstance");
        }
        
        // Attempts to retrieve directoryInstance
        if (isset($options["directoryInstance"]) && ! is_null($options["directoryInstance"])) {
            $this->directoryInstance    = $options["directoryInstance"];
        } else {
            throw new \Exception("Missing parameter directoryInstance");
        }
    }
    
    /**
     * Controls the diagnostics creation
     * 
     * @param Console_CommandLine_Result $options
     * @throws \Exception
     */
    function create(Console_CommandLine_Result $options){
     
        $args                           = $options->args;
        $options                        = $options->options;
        $diagnosticData                 = new Alternc_Diagnostic_Data(Alternc_Diagnostic_Data::TYPE_ROOT);
        
        $serviceList                    = explode(',',$options["services"]);
        foreach ($serviceList as $service) {
            $class_name                 = "Alternc_Diagnostic_Service_".trim(ucfirst($service));
            if(!class_exists($class_name)){
                throw new \Exception("Invalid service $service");
            }
            /** @var Alternc_Diagnostic_Service_Interface */
            $serviceAgent                = new $class_name;
            
            // Runs the service agent and store the results
            $diagnosticData->addData($serviceAgent->name, $serviceAgent->run());
        }
        $this->formatInstance->setData($diagnosticData)->write();
        
    }
    
    function compare( $options ){}
    function index( $options ){}
    function show( $options ){}
    function delete( $options ){}
    
    
}

// ==================================================================
// ==================================================================
// Console parser configuration
// ==================================================================
// ==================================================================

$consoleParser                          = new Alternc_Diagnostic_Console(array(
    'description' => "Handles diagnostics of an alternc server.",
    'version'     => '0.0.1', 
));


$createCommmand                         = $consoleParser->addCommand('create', array('multiple'=>true,"alias"=>"c","description" => "Creates a new diagnostic"));
$createCommmand->addOption('services', array(
   'short_name'  => '-s',
   'long_name'   => '--services',
   'action'      => 'StoreString',
   'default'     => 'web,dns,email,system,mailman,mysql,panel,ftp',
   'description' => 'Sets the services to use for diagnostics separated by comma
   ex: -d web,dns,email',
    'help_name'   => 'services'
    ));
$createCommmand->addOption('format', array(
   'short_name'  => '-f',
   'long_name'   => '--format',
   'action'      => 'StoreString',
   'default'     => 'json',
   'description' => 'Sets the format of the diagnostic file : json (default)',
    'help_name'   => 'format'
    ));

$indexCommmand                          = $consoleParser->addCommand('index', array('multiple'=>false,"alias"=>"i","description" => "Shows all available diagnostics"));
$compareCommmand                        = $consoleParser->addCommand('compare', array('multiple'=>false,"alias"=>"x","description" => "Removes one or more diagnotics"));
$compareCommmand                        = $consoleParser->addCommand('show', array('multiple'=>false,"alias"=>"s","description" => "Prints a diagnotic content"));
$deleteCommmand                         = $consoleParser->addCommand('delete', array('multiple'=>false,"alias"=>"d","description" => "Deletes diagnostic files"));


$directoryInstance                      = new Alternc_Diagnostic_Directory("/tmp/diagnostic");
$diagnosticManager                      = new Alternc_Diagnostic_Manager( array(
    "directoryInstance"         => $directoryInstance,
    "formatInstance"            => new Alternc_Diagnostic_Format_Json($directoryInstance)
));


//require_once("/usr/share/alternc/panel/class/config_nochk.php");
require_once("../bureau/class/config_nochk.php");

try {
    $result                             = $consoleParser->parse();
    if ($result->command_name){
        $command_name                   = $result->command_name;
        $command                        = $result->command;
    }else{
        throw new \Exception("Command missing, use -h to learn about available commands.");
    }
    if( !method_exists($diagnosticManager, $command_name)){
        throw new \Exception("Invalid command : $command");
    }
    $diagnosticManager->$command_name($command);
} catch (\Exception $exc) {
    $consoleParser->displayError($exc->getMessage());
}
/*
// Put this var to 1 if you want to enable debug prints


$admin->stop_if_jobs_locked();

$LOCK_FIL                              E= '/var/run/alternc/do_actions_cron.lock';
$SCRIP                                 T= '/usr/bin/php do_actions.php';
$MY_PI                                 D= getmypid();
$FIXPER                                M= '/usr/lib/alternc/fixperms.sh';

// Check if script isn't already running
if (file_exists($LOCK_FILE) !== false){
    d("Lock file already exists. ");
    // Check if file is in process list
    $PI                                D= file_get_contents($LOCK_FILE);
    d("My PID is $MY_PID, PID in the lock file is $PID");
    if ($PID == exec("pidof $SCRIPT | tr ' ' '\n' | grep -v $MY_PID")){
      // Previous cron is not finished yet, just exit
      d("Previous cron is already running, we just exit and let it finish :-)");
      exit(0);
    }else{
      // Previous cron failed!
      $error_raise                     .= "Lock file already exists. No process with PID $PID found! Previous cron failed...\n";
      d("Removing lock file and trying to process the failed action...");
      // Delete the lock and continue to the next action
      unlink($LOCK_FILE);

      // Lock with the current script's PID
      if (file_put_contents($LOCK_FILE,$MY_PID) === false){
        $error_raise                   .= "Cannot open/write $LOCK_FILE\n";
        mail_it();
        exit(1);
      }

      // Get the action(s) that was processing when previous script failed
      // (Normally, there will be at most 1 job pending... but who know?)
      while($cc=$action->get_job()){
        $                              c= $cc[0];
        $param                         s= unserialize($c["parameters"]);
        // We can resume these types of action, so we reset the job to process it later
        d("Previous job was the n°".$c["id"]." : '".$c["type"]."'");
        if($c["type"] == "CREATE_FILE" && is_dir(dirname($params["file"])) || $c["type"] == "CREATE_DIR" || $c["type"] == "DELETE" || $c["type"] == "FIXDIR" || $c["type"] == "FIXFILE"){
          d("Reset of the job! So it will be resumed...");
          $action->reset_job($c["id"]);
        }else{
          // We can't resume the others types, notify the fail and finish this action
          $error_raise                 .= "Can't resume the job n°".$c["id"]." action '".$c["type"]."', finishing it with a fail status.\n";
          if(!$action->finish($c["id"],"Fail: Previous script crashed while processing this action, cannot resume it.")){
            $error_raise               .= "Cannot finish the action! Error while inserting the error value in the DB for action n°".$c["id"]." : action '".$c["type"]."'\n";
            break; // Else we go into an infinite loop... AAAAHHHHHH
          }
        }
      }
    }
}else{
  // Lock with the current script's PID
  if (file_put_contents($LOCK_FILE,$MY_PID) === false){
    $error_raise                       .= "Cannot open/write $LOCK_FILE\n";
    mail_it();
    exit(1);
  }
}

//We get the next action to do
while ($rr=$action->get_action()){
  $                                    r= $rr[0];
  $retur                               n= "OK";
  // Do we have to do this action with a specific user?
  if($r["user"] != "root")
    $S                                 U= "su ".$r["user"]." 2>&1 ;";
  else
    $S                                 U= "";
  unset($output);
  // We lock the action
  d("-----------\nBeginning action n°".$r["id"]);
  $action->begin($r["id"]);
  // We process it
  $param                               s= @unserialize($r["parameters"]);
  // We exec with the specified user
  d("Executing action '".$r["type"]."' with user '".$r["user"]."'");
  switch ($r["type"]){
    case "FIX_USER" :
      // Create the directory and make parent directories as needed
      @exec("$FIXPERM -u ".$params["uid"]." 2>&1", $trash, $code);
      break;
    case "CREATE_FILE" :
      if(!file_exists($params["file"]))
        @exec("$SU touch ".$params["file"]." 2>&1 ; echo '".$params["content"]."' > '".$params["file"]."' 2>&1", $output);
      else
        $outpu                         t= array("Fail: file already exists");
      break;
    case "CREATE_DIR" :
      // Create the directory and make parent directories as needed
      @exec("$SU mkdir -p ".$params["dir"]." 2>&1",$output);
      break;
    case "DELETE" :
      // Delete file/directory and its contents recursively
      @exec("$SU rm -rf ".$params["dir"]." 2>&1", $output);
      break;
    case "MOVE" :
      // If destination dir does not exists, create it
      if(!is_dir($params["dst"]))
        @exec("$SU mkdir -p ".$params["dst"]." 2>&1",$output);
      if(!isset($output[0]))
        @exec("$SU mv -f ".$params["src"]." ".$params["dst"]." 2>&1", $output);
      break;
    case "FIXDIR" :
      @exec("$FIXPERM -d ".$params["dir"]." 2>&1", $trash, $code);
      if($code!=0)
        $output[0]="Fixperms.sh failed, returned error code : $code";
      break;
    case "FIXFILE" :
      @exec("$FIXPERM -f ".$params["file"]." 2>&1", $trash, $code);
      if($code!=0)
        $output[0]="Fixperms.sh failed, returned error code : $code";
      break;
    default :
      $outpu                           t= array("Fail: Sorry dude, i do not know this type of action");
      break;
  }
  // Get the error (if exists).
  if(isset($output[0])){
    $retur                             n= $output[0];
    $error_raise                       .= "Action n°".$r["id"]." '".$r["type"]."' failed! With user: ".$r["user"]."\nHere is the complete output:\n".print_r($output);
  }
  // We finished the action, notify the DB.
  d("Finishing... return value is : $return\n");
  if(!$action->finish($r["id"],addslashes($return))){
    $error_raise                       .= "Cannot finish the action! Error while inserting the error value in the DB for action n°".$c["id"]." : action '".$c["type"]."'\nReturn value: ".addslashes($return)."\n";
    break; // Else we go into an infinite loop... AAAAHHHHHH
  }
}

// If something have failed, notify it to the admin
if($error_raise !== '')
  mail_it(); 

// Unlock the script
unlink($LOCK_FILE);

// Exit this script
exit(0);
?>

*/
