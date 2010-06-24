#!/usr/bin/php -q
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

// On vérifie que mysql.so est bien chargé, sinon on essaye de le charger
if(!function_exists(mysql_connect))  {
  if(!dl("mysql.so"))
    exit(1);
}

// Ne vérifie pas ma session :)
if(!chdir("/var/alternc/bureau"))
  exit(1);
require("/var/alternc/bureau/class/config_nochk.php");

// On passe super-admin
$admin->enabled=1;

// On crée le compte admin : 
if (!$admin->add_mem("admin","admin","Administrateur", "Admin", "postmaster@".$L_FQDN,1,'default',0,'',1)) {
	echo $err->errstr()."\n";
	exit(1);
}

if(!$db->query("update membres set su=1 where login='admin';"))
  exit(1);

// On lui attribue des quotas par defaut
// 10 domains, 10 stats, 10 bases mysql, 20 ftp et 100 emails
if(!($db->query("update quotas,membres set quotas.total=10 where (quotas.name='stats' or quotas.name='sta2' or quotas.name='mysql' or quotas.name='dom') and quotas.uid=membres.uid and membres.login='admin' ;")
  && $db->query("update quotas,membres set quotas.total=20 where quotas.name='ftp' and quotas.uid=membres.uid and membres.login='admin' ;")
  && $db->query("update quotas,membres set quotas.total=100 where quotas.name='mail' and quotas.uid=membres.uid and membres.login='admin' ;")))
  exit(1);

exit(0);
?>
