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
 * Changes a user account's password
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */

require_once("../class/config.php");

$fields = array (
	"oldpass"    		=> array ("post", "string", ""),
	"newpass"    		=> array ("post", "string", ""),
	"newpass2"    		=> array ("post", "string", ""),
);
getFields($fields);



if ($mem->passwd($oldpass,$newpass,$newpass2)) {
	$msg->raise("INFO", "mem", __("Your password has been successfully changed.", "alternc", true));
}

include_once("head.php");

?>
<div align="center"><h3><?php __("Password change"); ?></h3></div>
<?php
echo $msg->msg_html_all();
echo "<p><span class='ina'><a href='mem_param.php'>".__("Click here to continue", "alternc", true)."</a></span></p>";
include_once("foot.php");
?>
