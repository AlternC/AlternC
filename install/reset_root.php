#!/usr/bin/php -q
<?php
/*
 $Id: reset_root.php,v 1.1 2005/01/19 05:37:18 anarcat Exp $
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

// Ne vérifie pas ma session :)
chdir("/usr/share/alternc/panel/");
require("/usr/share/alternc/panel/class/config_nochk.php");

// On passe super-admin
$admin->enabled=1;

// On remet le pass admin a zero
if (!$admin->update_mem(2000,"postmaster@".$L_FQDN,"Administrateur", "Admin", $admin->getadmin(), 1, 1, "default")) {
    echo "erreur : ".$err->errstr()."<br>\n";
    exit();
}

echo "\nThe password was successfully reset to 'admin' for the 'admin' account.\n";

?>
