<?php
/**
 mail_add.php, author: squidly
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
 Purpose of file: Create a new mail account
 ----------------------------------------------------------------------
*/

require_once("../class/config.php");
include_once("head.php");
$fields = array (
	"domain_id"    => array ("request", "integer", ""),
	"mail_arg"  => array ("request", "string", ""),
);
getFields($fields);
?>


<h3><?php printf(_("Add %s mail to the domain %s"),$mail_arg,$domain_id); ?> : </h3>
<?php
if ($error) {
  echo "<p class=\"error\">$error</p>";
}
