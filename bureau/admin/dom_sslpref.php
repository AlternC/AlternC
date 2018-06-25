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

$dom->lock();
if (!$r=$dom->get_domain_all($domain)) {
	$dom->unlock();
    require_once("head.php");
	echo $msg->msg_html_all();
	include('foot.php');
	die();
}
$dom->unlock();

$haserror=false;
if (count($_POST)) {
    $dom->lock();
    // get fields from the posted form:
    foreach($r["sub"] as $subdomain) {
        if (isset($_POST["ssl_".$subdomain["id"]])) {
            if (!$dom->set_subdomain_ssl_provider($subdomain["id"],$_POST["ssl_".$subdomain["id"]])) {
                $haserror=true;
            }
            // errors will be shown below
        }
    }
    $dom->unlock();    
    if ($haserror) {
        require_once("head.php");
        echo $msg->msg_html_all();
    } else {
        header("Location: dom_edit.php?domain=".eue($domain,false)."&msg=".eue(_("Your HTTPS preferences have been set"),false));
        exit();
    }
} // post ?

require_once("head.php");

?>
<h3><i class="fas fa-globe-africa"></i> <?php printf(_("Manage %s HTTPS preferences"),ehe($domain,false)); ?></h3>

<p class="alert alert-info"><?php __("These parameters are for advanced user who want to choose specific certificate provider. <br />Usually you'd want to click 'edit' in front of a subdomain to choose between HTTP and HTTPS by default."); ?></p>
<p>
  <?php __("For each subdomain that may be available through HTTPS, please choose which certificate provider you want to use."); ?>
<br />
<?php __("please note that you only see a provider if you have a valid certificate for this domain"); ?>
</p>

<form action="dom_sslpref.php" method="post" name="main" id="main">
    <input type="hidden" name="domain" value="<?php ehe($domain); ?>" />
   <?php csrf_get(); ?>
<table class="tlist" id="dom_edit_ssl">
<thead>
    <tr><th><?php __("Subdomain"); ?></th><th><?php __("HTTPS Preference"); ?></th></tr>
</thead>
<?php

for($i=0;$i<$r["nsub"];$i++) {
    if (!$r["sub"][$i]["only_dns"]) {
        continue;
    }
    $fqdn=$r["sub"][$i]["name"].(($r["sub"][$i]["name"])?".":"").$r["name"];
    $certs = $ssl->get_valid_certs($fqdn);

    echo "<tr>";
    echo "<td>".$fqdn."</td>";
    echo "<td><select name=\"ssl_".$r["sub"][$i]["id"]."\" id=\"ssl_".$r["sub"][$i]["id"]."\">";
    echo "<option value=\"\">"._("-- no HTTPS certificate provider preference --")."</option>";
    $providers=array();
    foreach($certs as $cert) {
        if ($cert["provider"] && $cert["provider"]!="snakeoil" && !isset($providers[$cert["provider"]])) {
            $providers[$cert["provider"]]=1;
            echo "<option value=\"".$cert["provider"]."\"";
            selected($r["sub"][$i]["provider"]==$cert["provider"]);
            echo ">"._("Provider:")." ".$cert["provider"]."</option>";
        }
    }
    echo "</select>";
    echo "</td>";
    echo "</tr>";
    
}

?>
<tr><td></td>
<td>
<p>
<button type="submit" class="inb ok" name="go"><?php __("Set my HTTPS certificate preferences"); ?></button>
<button type="button" class="inb cancel" name="cancel" onclick="document.location='dom_edit.php?domain=<?php eue($domain); ?>';"><?php __("Cancel"); ?></button>
</p>
</td></tr>
</table>
</form>


<?php
require_once("foot.php");
?>