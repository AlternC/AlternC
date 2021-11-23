#!/usr/bin/php-alternc-wrapper -q
<?php
/*
 $Id: newone.php,v 1.6 2006/02/17 15:15:54 olivier Exp $
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
 Purpose of file: Create the first admin account on a new AlternC server
 ----------------------------------------------------------------------
*/

// don't check my authentication !
if(!chdir("/usr/share/alternc/panel"))
  exit(1);
require("/usr/share/alternc/panel/class/config_nochk.php");

// We go root 
$admin->enabled=1;

// We Create the default mysql server if needed : 
$db->query("SELECT MIN(id) AS id FROM db_servers;");
$db->next_record();
if(!intval($db->Record["id"])) {
  echo "No default db_servers, creating one\n";
  // No db_servers ? We create one from the local MySQL parameters
  if ($L_MYSQL_HOST=="localhost") $client="localhost"; else $client="%";
  $db->query("INSERT INTO db_servers SET `name`='Default', `host`='$L_MYSQL_HOST', `login`='$L_MYSQL_LOGIN', `password`='$L_MYSQL_PWD', `client`='$client';");
  $db->query("SELECT MIN(id) AS id FROM db_servers;");
  $db->next_record();
}
$dbs=$db->Record["id"];

// And create the admin account
if (!$admin->add_mem("admin","admin","Administrateur", "Admin", "postmaster@".$L_FQDN,
		     1,'default',0,'',0 ,'', 
		     $dbs 
		     )) {
	echo $err->errstr()."\n";
	exit(1);
}

if(!$db->query("UPDATE membres SET su=1 WHERE login='admin';"))
  exit(1);

// Give admin account some default quota:
if(!$quota->synchronise_user_profile()) {
  exit(1);
}

exit(0);

