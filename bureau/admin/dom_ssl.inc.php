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
 * Form to get/set HTTPS preferences
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");

$fields = array (
	"domain"    => array ("request", "string", (empty($domain)?"":$domain) ),
);
getFields($fields);

?>
<p class="alert alert-info"><?php __("These parameters are for advanced user who want to choose specific certificate provider. <br />Usually you'd want to click 'edit' in front of a subdomain to choose between HTTP and HTTPS by default."); ?></p>
<p>
  <?php __("For each subdomain that may be available through HTTPS, please choose which certificate provider you want to use."); ?>
<br />
<?php __("please note that you only see a provider if you have a valid certificate for this domain"); ?>
</p>

<table class="tlist" id="dom_edit_ssl">
<thead>
    <tr><th><?php __("Subdomain"); ?></th><th><?php __("HTTPS Preference"); ?></th></tr>
</thead>
<?php
$dom->lock();
if (!$r=$dom->get_domain_all($domain)) {
	$dom->unlock();
	echo $msg->msg_html_all();
	include('foot.php');
	die();
}
$dom->unlock();

for($i=0;$i<$r["nsub"];$i++) {
    if (!$r["sub"][$i]["only_dns"]) {
        continue;
    }
    echo "<br />\n";
    echo "<tr>";
    echo "<td>".$r["name"].(($r["name"])?".":"").$r["sub"][$i]["name"]."</td>";
    echo "<td><select name=\"ssl_".$r["sub"][$i]["name"]."\" id=\"ssl_".$r["sub"][$i]["name"]."\">";
    echo "<option value=\"\">"._("-- no HTTPS certificate provider preference --")."</option>";
    echo "</select>";
    echo "</td>";
    echo "</tr>";
    
}

