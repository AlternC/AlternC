<?php
/*
$Id: recapitulatif.php,v 1.0 2013/10/17 12:12:42 fser Exp $
----------------------------------------------------------------------
AlternC - Web Hosting System
Copyright (C) 2005 by the AlternC Development Team.
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
Original Author of file: François Serman
Purpose of file: displays all the credentials for each available services
----------------------------------------------------------------------
*/
require_once("../class/config.php");

include("head.php");

?>
<body>
<h3><?php __("My accounts"); ?></h3>
<hr/>

<h4><?php __("FTP accounts"); ?></h4>

<table>
   <tr><th><?php __("login"); ?></th><th><?php __("password"); ?></th><th><?php __("root directory"); ?></th></tr>

<?php

$ftp_accounts = $ftp->get_list();

if (!$ftp_accounts) {
  echo '<tr><td colspan="3">No FTP account.</td></tr>';
}
else {
  foreach ($ftp_accounts AS $single_ftp_account)
    echo '<tr><td>' . $single_ftp_account['login'] . '</td><td>' . $single_ftp_account['password'] . '</td><td>' . $single_ftp_account['dir'] . '</td></tr>';
}
?>
</table>

<?php include_once('foot.php');?>
