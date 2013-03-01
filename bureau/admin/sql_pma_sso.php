<?php
/*
 $Id: sql_admin.php,v 1.4 2005/05/27 21:30:38 arnaud-lb Exp $
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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$r=$mysql->php_myadmin_connect()) {
	$error=$err->errstr();
} else {
  // SSO of PhpMyAdmin
  $_SESSION['PMA_single_signon_user'] = $r["login"];
  $_SESSION['PMA_single_signon_password'] = $r["pass"];
  $_SESSION['PMA_single_signon_host'] = $r["host"]; // pma >= 2.11

  // finally redirect to phpMyAdmin :
  header("Location: /alternc-sql/");
  exit();
}

include_once("head.php");
echo '<h3>'._("SQL Admin").'</h3>';

if (!empty($error)) {
  echo "<p class=\"error\">$error</p>";
}
include_once("foot.php"); 

?>
