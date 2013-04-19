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
$SCRIPT='php do_actions';
$MY_PID=getmypid();
$BACKUP_DIR='/var/backup/alternc/';

// Check if script isn't already running
if (($PID=file_get_contents($LOCK_FILE)) !== false){
    // Check if file is in process list
    if ($PID == exec("pidof $SCRIPT | grep -v $MY_PID")){
      // Previous cron is not finished yet, just exit
      exit 0;
    }else{
      // Previous cron failed!
      // Send an error mail to the admin and tell him what action failed
      mail("postmaster@$L_FQDN",'Cron do_actions.php failed!',"Hello\n\nPrevious cron /usr/lib/alternc/do_actions.php seems to have failed (I found the lock file but the cron is not running anymore)\nIts PID was: $PID\nI'll remove the lock file and continue to do performed actions, beginning to the right next action after the failed one.");
      // Delete the lock and continue to the next action
      unlink($LOCK_FILE);
    }
}

// We lock the script
if (file_put_contents($LOCK_FILE,$MY_PID) === false){
  die("Cannot open/write $LOCK_FILE");
}

//We get the next action to do
while ($r=$action->get_action()){
  // We lock the action
  $action->begin($r[id]);
  // We process it
  $params=array($r["parameters"]);
  // We exec with the specified user
  exec("su ".$params["user"]);
  switch ($r["type"]){
    case "CREATE_FILE" :
      $return=file_put_contents($params["file"],$params["contents"]);
      break;
    case "CREATE_DIR" :
      $return=mkdir($params["dir"]));
      break;
    case "DELETE" :
      $return=exec("rm -rf ".$params["dir"]);
      break;
    case "MOVE" :
      $return=rename($params["src"],$params["dst"]);
      break;
    default :
      break;
  }
  // We finished the action, notify the DB
  $action->finish($r["id"],$return);
}

// Unlock the script
unlink($LOCK_FILE);
?>
