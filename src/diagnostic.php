#!/usr/bin/php -q
<?php

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
 * Attempts to load a class in multiple path, the PSR-0 or old style way
 * 
 * @staticvar array $srcPathList
 * @staticvar boolean $init
 * @param string $class_name
 * @return boolean
 */
function __autoload($class_name)
{
    // Contains (Namespace) => directory
    static $srcPathList                 = array();
    static $init;
    
    // Attempts to set include path and directories once
    if( is_null( $init )){
        
        // Sets init flag
        $init                           = true;
        
        // Sets a contextual directory
        $srcPathList["standard"]        = __DIR__."/../lib";

        // Updates include_path according to this list
        $includePathList                = explode(PATH_SEPARATOR, get_include_path()); 

        foreach($srcPathList as $path){
            if ( !in_array($path, $includePathList)){
                $includePathList[]      = $path;
            }
        }
        // Reverses the path for search efficiency
        $finalIncludePathList           = array_reverse($includePathList);
        
        // Sets the updated include_path
        set_include_path(implode(PATH_SEPARATOR, $finalIncludePathList));
        
    }
    
    // Accepts old Foo_Bar namespacing
    if(preg_match("/_/", $class_name)){
        $file_name                      = str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';
        
    // Accepts 5.3 Foo\Bar PSR-0 namespacing 
    } else if(preg_match("/\\/", $class_name)){
        $file_name                      = str_replace('\\', DIRECTORY_SEPARATOR, ltrim($class_name,'\\')) . '.php';
        
    // Accepts non namespaced classes
    } else {
        $file_name                      = $class_name . '.php';        
    }
   
    // Attempts to find file in namespace
    foreach($srcPathList as $namespace => $path ){
        $file_path                      = $path.DIRECTORY_SEPARATOR.$file_name;
        if(is_file($file_path) && is_readable($file_path)){
            require $file_path;
            return true;
        }
    }
    
    // Failed to find file
    return false;
}


// ==================================================================
// ==================================================================
// alternc config
// ==================================================================
// ==================================================================

$version				= "3.2";

// alternc 1.0
if(is_file("/usr/share/alternc/panel/class/config_nochk.php")){
    require_once("/usr/share/alternc/panel/class/config_nochk.php");
}else{
    $version				= "1.0";				
    require_once("/var/alternc/bureau/class/config_nochk.php");
    include "../bureau/class/class_system_bind.php";
}




$directoryInstance                      = new Alternc_Diagnostic_Directory("/tmp/diagnostic");


// instanciation of the diagnosticManager service

$diagnosticManager                      = new Alternc_Diagnostic_Manager( array(
    "directoryInstance"         => $directoryInstance,
    "formatInstance"            => new Alternc_Diagnostic_Format_Json($directoryInstance),
    "version"			=> $version 
));


// ==================================================================
// ==================================================================
// Console parser configuration
// ==================================================================
// ==================================================================

$consoleParser                          = new Alternc_Diagnostic_Console();


$createCommmand                         = $consoleParser->addCommand('create', array('multiple'=>true,"alias"=>"c","description" => "Creates a new diagnostic"));

$createCommmand->addOption('services', array(
   'short_name'  => '-s',
   'long_name'   => '--services',
   'action'      => 'StoreString',
   'default'     => 'apache2,dns,mail,system,mailman,mysql,panel,ftp',
   'description' => 'Sets the services to use for diagnostics separated by comma
   ex: -d apache2,dns,mail',
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



// Attempts to parse command line
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
