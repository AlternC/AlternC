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
 * Report DNS and WEBSITES being in error mode in the DB
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */

require_once("../class/config.php");

if (!$admin->enabled) {
	$msg->raise("ERROR", "admin", _("This page is restricted to authorized staff"));
	echo $msg->msg_html_all();
	exit();
}

include_once("head.php");

?>
<h3><?php __("Domains and Websites having errors"); ?></h3>
<hr id="topbar" />
 <br />
<?php
echo $msg->msg_html_all();
?>
<br/>
<h3><?php __("List of the websites having errors in the domain database."); ?></h3>
<table class="tlist">
  <tr>
  <th><?php __("Uid"); ?></th>
  <th><?php __("Account"); ?></th>
  <th><?php __("Domain name"); ?></th>
  <th><?php __("FQDN"); ?></th>
  <th><?php __("Value"); ?></th>
  <th><?php __("Description"); ?></th>
  <th><?php __("Web Result field"); ?></th>
  </tr>

<?php 
$pair=0;
$db->query("select sd.compte, m.login, sd.domaine, if(length(sub)>0,concat_ws('.',sd.sub,sd.domaine),sd.domaine) as fqdn, sd.valeur, dt.description, sd.web_result from sub_domaines sd,membres m, domaines_type dt where sd.web_action='OK' and length(sd.web_result)<>0 and upper(dt.name)=upper(sd.type) and sd.compte=m.uid order by sd.domaine, sd.sub, sd.valeur;");

while($db->next_record()) {  ?>
<tr class="lst">
    <td><?php echo $db->f('compte');?></td>
    <td><?php echo $db->f('login');?> </td>
    <td><?php echo $db->f('domaine');?> </td>
    <td><?php echo $db->f('fqdn');?></td>
    <td><?php echo $db->f('valeur');?></td>
    <td><?php echo $db->f('description');?></td>
    <td><?php echo $db->f('web_result');?></td>
</tr>
<?php } // end while  ?>
</table>
<hr/>

<h3><?php __("List of the domain names having errors in the domain database."); ?></h3>
<table class="tlist">
  <tr>
  <th><?php __("Uid"); ?></th>
  <th><?php __("Account"); ?></th>
  <th><?php __("Domain name"); ?></th>
  <th><?php __("DNS Result field"); ?></th>
  </tr>

<?php 
$pair=0;
$db->query("select d.compte, m.login, d.domaine, d.dns_result from domaines d, membres m where d.dns_action='OK' and dns_result <> 0 and dns_result <> '' and m.uid = d.compte;");

while($db->next_record()) { ?>
<tr class="lst">
    <td><?php echo $db->f('compte');?></td>
    <td><?php echo $db->f('login');?> </td>
    <td><?php echo $db->f('domaine');?> </td>
    <td><?php echo $db->f('dns_result');?></td>
</tr>
<?php } // end while ?>
</table>
<?php include_once("foot.php"); ?>
