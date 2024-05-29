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
 * Enable a FTP account
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");

$fields = array (
		 "id" =>array ("get","integer",""),
		 "status" =>array ("get","integer",null),
);

getFields($fields);

if ($ftp->switch_enabled($id,$status)) {
  if ($status) 
    $msg->raise("INFO", "ftp", __("The FTP account is enabled", "alternc", true));
  else
    $msg->raise("INFO", "ftp", __("The FTP account is disabled", "alternc", true));
}

require_once('ftp_list.php');
