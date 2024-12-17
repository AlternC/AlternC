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
 * delete a protected folder using .htaccess for Apache2
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");

foreach($_POST as $key=>$val) {
	if (substr($key,0,4)=="del_") {
		$return = $hta->DelDir($val);
		if ($return) {
			$msg->raise("INFO", "hta",_("The protected folder %s has been successfully unprotected"),$val);
		}
	}
}
include("hta_list.php");
exit();
?>
