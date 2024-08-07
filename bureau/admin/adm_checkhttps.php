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
 * Check that all hosted vhosts on the server are giving a 300 redirection to https on HTTP port.
 * 
 * @copyright AlternC-Team 2000-2024 https://alternc.com/ 
 */

require_once("../class/config.php");

if (!$admin->enabled) {
	$msg->raise("ERROR", "admin", _("This page is restricted to authorized staff"));
	echo $msg->msg_html_all();
	exit();
}

include_once ("head.php");

?>
<h3><?php __("Check HTTPs redirect for Vhosts"); ?></h3>
<?php
echo $msg->msg_html_all();

$fields = array (
	"force"    		=> array ("get", "integer", "0"),
);
getFields($fields);

// List the vhost-type-domains. If the first parameter is true, also check their DNS & other IPs actual parameters.
// if forcecheck is set, update the informations

$c=$admin->check_https($force);
?>
<p>
<?php __("Here is the list of the hosted virtual-hosts installed on this server. This page checks that all hosted virtual-hosts give a redirect to HTTPS (secured) when called using HTTP (unsecured)."); ?>
</p>
<p>
<?php __("This page is automatically cached for a day. If you want to update the checks, click "); ?> <a href="adm_checkhttps.php?force=1"><?php __("Update cache information"); ?></a>
</p>
<table class="tlist" id="dom_list_table">
<thead>
    <tr><th><?php __("Creator"); ?></th><th><?php __("Connect as"); ?></th></th><th><?php __("Virtual Host"); ?></th><th colspan="2"><?php __("Redirect status"); ?></th><th><?php __("Redirect url"); ?></th></tr>
</thead>
<tbody>
<?php
$astatus=[
          0 => _("OK"),
          1 => _("Redirect to https elsewhere"),
          2 => _("Not redirecting to HTTPS"),
          3 => _("Timeout"),
          4 => _("Forbidden"),
          5 => _("Errored"),
];
$acolor=[
    0 => "green", 1=> "green", 2=> "red", 3=> "orange", 4=> "orange" , 5=> "red",
];

for($i=0;$i<count($c);$i++) {
?>

<tr class="lst">
<td><?php echo $c[$i]["login"]; ?></td>
<td><span class="ina<?php if ($col == 2) echo "v"; ?>"> <a href="adm_login.php?id=<?php echo $c[$i]["uid"]; ?>"><?php echo __("Connect as"); ?></a> </span></td>
<td><a href="http://<?php echo $c[$i]["fqdn"]; ?>" target="_blank"><?php echo $c[$i]["fqdn"]; ?></a></td>
<td style="background: <?php echo $acolor[$c[$i]["status"]]; ?>">&nbsp; </td>
<td><?php echo $astatus[$c[$i]["status"]]; ?></td>
<td><?php echo $c[$i]["url"]; ?></td>
</tr>
<?php
}
?>
</tbody>
</table>

<?php include_once("foot.php"); ?>
