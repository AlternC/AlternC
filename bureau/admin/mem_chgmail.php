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
 * Changes the email of an account, STEP 1
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */

require_once("../class/config.php");

$fields = array (
	"newmail"    => array ("post", "string", ""),
);
getFields($fields);

if ($cle=$mem->ChangeMail1($newmail)) {
	$msg->raise("INFO", "mem", _("The mail was successfully changed"));
}

include_once("head.php");
?>
<h3><?php __("Change the email of the account"); ?></h3>
<?php
echo $msg->msg_html_all();
if ($msg->has_msgs("ERROR")) {
	echo "<p><span class='ina'><a href='mem_param.php'>"._("Click here to continue")."</a></span></p>";
	include_once("foot.php");
	exit();
}

printf(_("help_mem_chgmail %s"),$newmail);
?>
<p class="code"><?php echo $cle; ?></p>
<p><span class="ina"><a href="mem_param.php"><?php __("Click here to continue"); ?></a></span></p>

<?php include_once("foot.php"); ?>
