<?php
/*
 $Id: adm_variables.php,v 1.1 2005/01/19 06:09:36 anarcat Exp $
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
 Original Author of file: Benjamin Sonntag
 Purpose of file: Manage allowed TLD on the server
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
	__("This page is restricted to authorized staff");
	exit();
}
$fields = array (
  "member_id"   => array ("post", "integer", null),
  "fqdn_id"     => array ("post", "integer", null),
);
getFields($fields);

include_once ("head.php");

?>
<h3><?php __("Configure AlternC variables"); ?></h3>
<hr id="topbar"/>
<br />

<p>
<?php __("Here are the internal AlternC variables that are currently being used."); ?>
</p>

<table border="0" cellpadding="4" cellspacing="0" class='tlist' id="tab_listvar_glob">
<thead>
  <tr>
    <th><?php __("Names"); ?></th>
    <th><?php __("Comment"); ?></th>
    <th><?php __("Default value"); ?></th>
    <th><?php __("Global value"); ?></th>
    <th><?php __("Actual value used"); ?></th>
  </tr>
</thead>
<?php

$allvars = $variables->variables_list();
$global_conf=$variables->get_impersonated();
foreach( $variables->variables_list_name() as $varname => $varcomment) {  ?>

 <tr class="lst">
   <td><a href='adm_var_edit.php?var=<?php echo urlencode($varname)?>'><?php echo $varname; ?></a></td>
   <td><?php echo $varcomment; ?></td>
   <td><?php $variables->display_value_html($allvars, 'DEFAULT', NULL, $varname);?></td>
   <td><?php $variables->display_value_html($allvars, 'GLOBAL',  NULL, $varname);?></td>
   <td><?php if (isset($global_conf[$varname]['value'])) { $variables->display_valueraw_html($global_conf[$varname]['value'], $varname); } ?></td>
 </tr>
<?php } ?>
</table>

<br/><br/><br/>

<hr/>
<h3 id="overwrited_vars"><?php __("Overwrited vars"); ?></h3>
<form method="post" action="adm_variables.php#overwrited_vars">
<?php
$creator=$mem->get_creator_by_uid($member_id);

$ml=array();
foreach($admin->get_list() as $mid=>$mlogin) {
  $ml[$mid] = $mlogin['login'];
}
echo _("See the vars for the account")." ";
echo "<select name='member_id'>";eoption($ml, $member_id);echo "</select>";
echo " "._("logged via")." ";
echo "<select name='fqdn_id'>";eoption($dom->get_panel_url_list(), $fqdn_id  );echo "</select> ";
echo "<input type='submit' class='ina' value=\""; echo ehe(_("View")); echo "\" />";

?>
</form>
<br/>

<?php 
if ( $member_id && $fqdn_id ) {
$sub_infos=$dom->get_sub_domain_all($fqdn_id);
$fqdn=$dom->get_panel_url_list()[$fqdn_id];
$impersonated_conf=$variables->get_impersonated($fqdn, $member_id);

echo sprintf(_("Here are values for members %s logged via %s"), '<b>'.$ml[$member_id].'</b>', "<b>$fqdn</b>") ;?>
<table class='tlist' id="tab_listvar_impers">
<?php
echo "<thead><tr>";
echo "<th>"._("Var")."</th>";
foreach( $variables->strata_order as $st) {  
  echo "<th>$st</th>";
} // foeach
echo "<th>"._("Used value")."</th>";
echo "</tr></thead>";
foreach( $variables->variables_list_name() as $varname => $varcomment) {  ?>
 <tr class="lst">
   <td><a href='adm_var_edit.php?var=<?php echo urlencode($varname); ?>'><?php echo $varname; ?></a></td>
   <td><?php $variables->display_value_html($allvars, 'DEFAULT', NULL, $varname); ?></td>
   <td><?php $variables->display_value_html($allvars, 'GLOBAL', NULL, $varname); ?></td>
   <td><?php $variables->display_value_html($allvars, 'FQDN_CREATOR', $sub_infos['member_id'], $varname); ?></td>
   <td><?php $variables->display_value_html($allvars, 'FQDN', $sub_infos['id'], $varname); ?></td>
   <td><?php $variables->display_value_html($allvars, 'CREATOR', $creator, $varname); ?></td>
   <td><?php $variables->display_value_html($allvars, 'MEMBER', $member_id, $varname); ?></td>
   <td><?php $variables->display_value_html($allvars, 'DOMAIN', 'FIXME', $varname); ?></td>
   <td><?php $variables->display_valueraw_html($impersonated_conf[$varname]['value'], $varname); ?></td>
 </tr>
<?php
} //foreach 
?>
</table>

<br/>
<?php } // if $member_id && $fqdn_id  ?>
<script type="text/javascript">

$(document).ready(function() 
    { 
        $("#tab_listvar_impers").tablesorter(); 
        $("#tab_listvar_glob").tablesorter(); 
    } 
); 
</script>

<?php include_once("foot.php"); ?>
