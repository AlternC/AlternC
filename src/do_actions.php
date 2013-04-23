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

require_once("/usr/share/alternc/panel/class/config_nochk.php");

$LOCK_FILE='/var/run/alternc/do_actions_cron.lock';
$SCRIPT='/usr/bin/php do_actions.php';
$MY_PID=getmypid();

// Check if script isn't already running
if (file_exists($LOCK_FILE) !== false){
    // Check if file is in process list
    $PID=file_get_contents($LOCK_FILE);
    if ($PID == exec("pidof $SCRIPT | grep -v $MY_PID")){
      // Previous cron is not finished yet, just exit
      echo "Previous cron is not finished yet, just exit\n";
      exit(0);
    }else{
      // Previous cron failed!
      echo "Previous cron failed!\n";
      // Delete the lock and continue to the next action
      unlink($LOCK_FILE);
    }
}

// We lock the script
if (file_put_contents($LOCK_FILE,$MY_PID) === false){
  die("Cannot open/write $LOCK_FILE");
}

//We get the next action to do
while ($rr=$action->get_action()){
  $r=$rr[0];
  $return="OK";
  // We lock the action
  echo "-----------\nBeginning action n°".$r["id"]."\n";
  $action->begin($r["id"]);
  // We process it
  $params=unserialize($r["parameters"]);
  // Remove all previous error message...
  @trigger_error("");
  // We exec with the specified user
  echo "Executing action '".$r["type"]."' with user '".$r["user"]."'\n";
  // For now, this script only work for user 'root'
  if($r["user"] != "root"){
    if(exec("su ".$r["user"])){ // TODO
      echo "Login successfull, now processing the action...\n";
    }else{
      echo "Error: can't login as ".$r["user"]."\n";
      $action->finish($r["id"],"Can't login as user ".$r["user"]);
      continue;
    }
  }
  switch ($r["type"]){
    case "CREATE_FILE" :
      @file_put_contents($params["file"],$params["content"]);
      break;
    case "CREATE_DIR" :
      @mkdir($params["dir"],0777,true);
      break;
    case "DELETE" :
      @exec("rm -rf ".$params["dir"]." 2>&1", $output);
      break;
    case "MOVE" :
      // If destination dir does not exists, create it
      if(!is_dir($params["dst"]))
        @mkdir($params["dst"],0777,true);
      @exec("mv -f ".$params["src"]." ".$params["dst"]." 2>&1", $output);
      break;
    case "PERMFIX" :
      // TODO 
      break;
    default :
      break;
  }
  // Get the last error if exists.
  if(isset($output[0]))
    $return=$output[0];
  else
    if($error=error_get_last())
      if($error["message"]!="")
        $return=$error["message"];
  echo "Finishing... return value is : $return\n\n";
  // We finished the action, notify the DB.
  $action->finish($r["id"],$return);
}

// Unlock the script
unlink($LOCK_FILE);

// Exit this script
exit(0);
?>
