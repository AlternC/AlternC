#!/usr/bin/php -q
<?php
/*
   $Id: do_actions.php,v 1.0 2013/04/19 13:40:32 axel Exp $
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
   Original Author of file: Axel Roger
   Purpose of file: Do planed actions on files/directories.
   ----------------------------------------------------------------------
 */
/**
 * This script check the MySQL DB for actions to do, and do them one by one.
 *
 * @copyright AlternC-Team 2002-2013 http://alternc.org/
 */

// Put this var to 1 if you want to enable debug prints
$debug=0;

// Debug function that print infos
function d($mess){
  global $debug;
  if ($debug == 1)
    echo "$mess\n";
}

require_once("/usr/share/alternc/panel/class/config_nochk.php");

$LOCK_FILE='/var/run/alternc/do_actions_cron.lock';
$SCRIPT='/usr/bin/php do_actions.php';
$MY_PID=getmypid();
$FIXPERM='/usr/lib/alternc/fixperms.sh';

// Check if script isn't already running
if (file_exists($LOCK_FILE) !== false){
    d("Lock file already exists. ");
    // Check if file is in process list
    $PID=file_get_contents($LOCK_FILE);
    d("My PID is $MY_PID, PID in the lock file is $PID");
    if ($PID == exec("pidof $SCRIPT | tr ' ' '\n' | grep -v $MY_PID")){
      // Previous cron is not finished yet, just exit
      d("Previous cron is already running, we just exit and let it finish :-)");
      exit(0);
    }else{
      // Previous cron failed!
      echo "No process with PID $PID found! Previous cron failed...\n";
      d("Removing lock file and trying to process the failed action...");
      // Delete the lock and continue to the next action
      unlink($LOCK_FILE);

      // Lock with the current script's PID
      d("Lock the script...");
      if (file_put_contents($LOCK_FILE,$MY_PID) === false){
        die("Cannot open/write $LOCK_FILE");
      }

      // Get the action(s) that was processing when previous script failed
      // (Normally, there will be at most 1 job pending... but who know?)
      while($cc=$action->get_job()){
        $c=$cc[0];
        $params=unserialize($c["parameters"]);
        // We can resume these types of action, so we reset the job to process it later
        d("Previous job was the n°".$c["id"]." : '".$c["type"]."'");
        if($c["type"] == "CREATE_FILE" && is_dir(dirname($params["file"])) || $c["type"] == "CREATE_DIR" || $c["type"] == "DELETE" || $c["type"] == "FIXDIR"){
          d("Reset of the job! So it will be resumed...");
          $action->reset_job($c["id"]);
        }else{
          // We can't resume the others types, notify the fail and finish this action
          echo "Can't resume the job n°".$c["id"]." action '".$c["type"]."', finishing it with a fail status.\n";
          if(!$action->finish($c["id"],"Fail: Previous script crashed while processing this action, cannot resume it.")){
            echo "Cannot finish the action! Error while inserting the error value in the DB for action n°".$c["id"]." : action '".$c["type"]."'\n";
            break; // Else we go into an infinite loop... AAAAHHHHHH
          }
        }
      }
    }
}else{
  // Lock with the current script's PID
  d("Lock the script...");
  if (file_put_contents($LOCK_FILE,$MY_PID) === false){
    die("Cannot open/write $LOCK_FILE");
  }
}

//We get the next action to do
while ($rr=$action->get_action()){
  $r=$rr[0];
  $return="OK";
  unset($output);
  // We lock the action
  d("-----------\nBeginning action n°".$r["id"]);
  $action->begin($r["id"]);
  // We process it
  $params=unserialize($r["parameters"]);
  // Remove all previous error message...
  @trigger_error("");
  // We exec with the specified user
  d("Executing action '".$r["type"]."' with user '".$r["user"]."'");
  // For now, this script only work for user 'root'
  if($r["user"] != "root"){
    // TODO
  }
  switch ($r["type"]){
    case "CREATE_FILE" :
      if(!file_exists($params["file"]))
        @file_put_contents($params["file"],$params["content"]);
      else
        $output=array("Fail: file already exists");
      break;
    case "CREATE_DIR" :
      // Create the directory and make parent directories as needed
      @mkdir($params["dir"],0777,true);
      break;
    case "DELETE" :
      // Delete file/directory and its contents recursively
      @exec("rm -rf ".$params["dir"]." 2>&1", $output);
      break;
    case "MOVE" :
      // If destination dir does not exists, create it
      if(!is_dir($params["dst"]))
        @mkdir($params["dst"],0777,true);
      @exec("mv -f ".$params["src"]." ".$params["dst"]." 2>&1", $output);
      // If MOVE failed, we have to notify the cron
      if(isset($output[0]))
        echo "Action n°".$r["id"]." 'MOVE' failed!\nuser: ".$r["user"]."\nsource: ".$params["src"]."\ndestination: ".$params["dst"]."\n";
      break;
    case "FIXDIR" :
      @exec("$FIXPERM -f ".$params["dir"]." 2>&1", $trash, $code);
      if($code!=0)
        $output[0]=$code;
      break;
    default :
      $output=array("Fail: Sorry dude, i do not know this type of action");
      break;
  }
  // Get the last error if exists.
  if(isset($output[0]))
    $return=$output[0];
  else
    if($error=error_get_last())
      if($error["message"]!="")
        $return=$error["message"];
  // We finished the action, notify the DB.
  d("Finishing... return value is : $return\n");
  if(!$action->finish($r["id"],addslashes($return))){
    echo "Cannot finish the action! Error while inserting the error value in the DB for action n°".$c["id"]." : action '".$c["type"]."'\nReturn value: ".addslashes($return)."\n";
    break; // Else we go into an infinite loop... AAAAHHHHHH
  }
}

// Unlock the script
d("Unlock the script...");
unlink($LOCK_FILE);

// Exit this script
exit(0);
?>
