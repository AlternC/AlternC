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
 * Add a piwik account using piwik's API
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */

require_once("../class/config.php");

$userslist = $piwik->users_list();
$quotapiwik = $quota->getquota('piwik');

if (!($quotapiwik['t'] > 0 && count($userslist) < 3)) {
	$msg->raise("ERROR", "piwik", _("You cannot add any new Piwik account, your quota is over.")." ("._("Max. 3 accounts").")");
}

$fields = array (
	"account_name" 		=> array ("post", "string", ""),
	"account_mail" 		=> array ("post", "string", ""),
);
getFields($fields);

if ($piwik->user_add($account_name, $account_mail) ) {
  $msg->raise("INFO", "piwik", _('Successfully added piwik account'));
}
include_once("piwik_userlist.php");
?>
