<?php
/*
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2000-2012 by the AlternC Development Team. 
 https://alternc.org/  
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
 Purpose of file: Delete requested awstats statistics.
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

// On parcours les POST_VARS et on repere les del_.
reset($_POST);
$found=false;
while (list($key,$val)=each($_POST)) {
  if (substr($key,0,4)=="del_") {
    // Effacement du jeu de stats $val
    $r=$aws->delete_stats($val);
    $found=true;
    if ($r) {
      $msg->raise('INFO', "aws", __("The statistics %s has been successfully deleted", "alternc", true),$r);
    }
  }
}

if (!$found) {
  $msg->raise('INFO', "aws", __("Please check the statistics set you want to delete", "alternc", true));
 }

include("aws_list.php");
exit();

?>
