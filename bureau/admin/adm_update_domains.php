<?php
/*
 $Id: adm_tld.php,v 1.4 2004/11/29 17:27:04 anonymous Exp $
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
 Original Author of file: Alan Garcia
 Purpose of file: Manage domain types on the server 
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
  if ( ! ( $isinvited && isset($oldid) && !empty($oldid) && $oldid==2000) ) { // Allow sub admins
    $msg->raise('Error', "admin", _("This page is restricted to authorized staff"));
    echo $msg->msg_html_all();
    exit();
  }
}

if (! isset($L_INOTIFY_UPDATE_DOMAIN)) {
  $msg->raise('Error', "admin", _("Missing INOTIFY_UPDATE_DOMAIN var in /etc/alternc/local.sh . Fix it!"));
  echo $msg->msg_html_all();
  die();
}

touch($L_INOTIFY_UPDATE_DOMAIN);

Header('Location: /main.php');

?>
