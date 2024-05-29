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
 * List domains on the server and their DNS / Vhost compatibility
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");

if (!$admin->enabled) {
	$msg->raise("ERROR", "admin", __("This page is restricted to authorized staff", "alternc", true));
	echo $msg->msg_html_all();
	exit();
}

include_once ("head.php");

?>
<h3><?php __("Manage installed domains"); ?></h3>
<?php
echo $msg->msg_html_all();

$fields = array (
	"force"    		=> array ("get", "integer", "0"),
);
getFields($fields);

// List the domains. If the first parameter is true, also check their DNS & other IPs actual parameters.
// If the second parameter is true, check the domains whatever the dis cache is.

$forcecheck=$force; // retrocompatibility
$c=$admin->dom_list(true,$forcecheck);

?>
<p>
<?php __("Here is the list of the domains installed on this server. You can remove a domain if it does not exist or does not point to our server anymore. You can also set the 'Lock' flag on a domain so that the user will not be able to change any DNS parameter or delete this domain from his account."); ?>
</p>
<p>
<?php __("The domain OK column are green when the domain exists in the worldwide registry and has a proper NS,MX and IP depending on its configuration. It is red if we have serious doubts about its NS, MX or IP configuration. Contact the user of this domain or a system administrator."); ?>
</p>
<p>
<?php __("If you want to force the check of NS, MX, IP on domains, click the link"); ?> <a href="adm_doms.php?force=1"><?php __("Show domain list with refreshed checked NS, MX, IP information"); ?></a>
</p>
<form method="post" action="adm_dodom.php" name="main" id="main">
  <?php csrf_get(); ?>
<table class="tlist" id="dom_list_table">
<thead>
    <tr><th></th><th><?php __("Action"); ?></th><th><?php __("Domain"); ?></th><th><?php __("Creator"); ?></th><th><?php __("Connect as"); ?></th><th><?php __("OK?"); ?></th><th><?php __("Status"); ?></th></tr>
</thead>
<tbody>
<?php
for($i=0;$i<count($c);$i++) {
?>

<tr class="lst">
				    <td><?php if ($c[$i]["noerase"]) {
			echo "<img src=\"icon/encrypted.png\" width=\"16\" height=\"16\" alt=\"".__("Locked Domain", "alternc", true)."\" />";
				    } ?></td>
<td><div class="ina"><a href="adm_domlock.php?domain=<?php echo urlencode($c[$i]["domaine"]); ?>"><?php
   if ($c[$i]["noerase"]) __("Unlock"); else __("Lock");  ?></a></div></td>
<td><a href="http://<?php echo $c[$i]["domaine"]; ?>" target="_blank"><?php echo $c[$i]["domaine"]; ?></a></td>
<td><?php echo $c[$i]["login"]; ?></td>
<td>
<?php		  if($admin->checkcreator($c[$i]['uid'])) {
		?>
			<div class="ina"><a href="adm_login.php?id=<?php echo $c[$i]["uid"];?>"><?php __("Connect as"); ?></a></div>
		<?php } ?>
</td>
<td style="background: <?php 
				       if ($c[$i]["errno"]==0) {
					 echo "green";
				       } else {
					 echo  "red";
				       }
  ?>">&nbsp;
</td>
<td><?php echo nl2br($c[$i]["errstr"]); ?></td>
</tr>
<?php
}
?>
</tbody>
</table>
</form>
<script type="text/javascript">

$(document).ready(function()
    {
        $("#dom_list_table").tablesorter();
    }
);
</script>
<?php include_once("foot.php"); ?>
