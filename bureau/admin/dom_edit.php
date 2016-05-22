<?php
/*
 $Id: dom_edit.php,v 1.8 2006/02/17 18:20:08 olivier Exp $
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
 Purpose of file: Edit a domain parameters
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"domain"    => array ("request", "string", (empty($domain)?"":$domain) ),
	"sub"       => array ("request", "string", (empty($sub)?"":$sub) ),
);
getFields($fields);

$dom->lock();
if (!$r=$dom->get_domain_all($domain)) {
	$error=$err->errstr();
	echo "<p class=\"alert alert-danger\">$error</p>";
	include('foot.php');
	die();
}
if (isset($error) && $error ) {
	echo "<p class=\"alert alert-danger\">$error</p>";
}
$dom->unlock();

?>
<script type="text/javascript">
function dnson() {
  alert('This function seems unused. If you see this message, please let us know.');
	// Active les composants DNS :
	if (document.forms["fdns"].mx.disabled!=null)
		document.forms["fdns"].mx.disabled=false;
	if (document.forms["fdns"].emailon.disabled!=null)
		document.forms["fdns"].emailon.disabled=true;
	if (document.forms["fdns"].emailoff.disabled!=null)
		document.forms["fdns"].emailoff.disabled=true;
}
function dnsoff() {
  alert('This function seems unused. If you see this message, please let us know.');
	// Active les composants DNS :
	if (document.forms["fdns"].mx.disabled!=null)
		document.forms["fdns"].mx.disabled=true;
	if (document.forms["fdns"].emailon.disabled!=null)
		document.forms["fdns"].emailon.disabled=false;
	if (document.forms["fdns"].emailoff.disabled!=null)
		document.forms["fdns"].emailoff.disabled=false;
}

function destruction_alert() {
  // On ne se pose pas de question si le DNS est deja sur NON
  if (<?php echo (int)$r["dns"]; ?>!=1) {
    return true;
  }
  if (document.forms["fdns"].email[1].checked) {
    if ( confirm("<?php __("Are you sure you want to do this? This will DELETE ALL the mailboxes, messages and aliases on this domain ?"); ?>") ) {
      return true;
    } else {
      return false;
    }
  } else {
      return true;
  }
}
</script>

<h3><img src="images/dom.png" alt="" />&nbsp;<?php printf(_("Manage %s"),$domain); ?></h3>

<?php

if ($r['dns_action']=='UPDATE') {?>
  <p class="alert alert-info"><?php __("This domain have some DNS change pending. Please wait."); ?></p>
<?php
} elseif ($r['dns_action']=='DELETE') {?>
  <p class="alert alert-warning"><?php printf(_("You requested deletion of domain %s."), $domain);?></p>
<?php
/*
  // Link hidden as long as the del_domain_cancel function is not complete
  <a href="dom_dodel.php?domain=<?php echo urlencode($domain);?>&del_cancel=true"><?php __("Clic here to cancel deletion");?></a>
*/
?>
  <?php
  include_once("foot.php");
  die();
}

if (! empty($r['dns_result']) && $r['dns_result'] != '0') {
  if ($r['dns_result'] == 1) $r['dns_result'] =_("DNS zone is locked, changes will be ignored");
  echo '<p class="alert alert-warning">'; __($r['dns_result']); echo '</p>';
}

?>


<div id="tabsdom">

<ul>
  <li class="edit"><a href="#tabsdom-editsub"><?php __("Edit subdomains");?></a></li>
  <li class="add"><a href="#tabsdom-addsub"><?php __("Add subdomains");?></a></li>
  <li class="settings"><a href="#tabsdom-params"><?php __("Settings");?></a></li>
<?php if ( $r["dns"] ) { ?>
  <li class="view"><a href="#tabsdom-view" onClick="update_dns_content();"><?php __("View");?></a></li> 
<?php } //if gesdns ?>
  <li class="delete"><a href="#tabsdom-delete"><?php __("Delete");?></a></li>
</ul>


<div id="tabsdom-editsub">
<h3><?php __("Main subdomains"); ?></h3>
<?php
$dt=$dom->domains_type_lst();

$problems = $dom->get_problems($domain);
if ( ! empty($problems) ) {
  echo '<p class="alert alert-danger">';
  foreach ($problems as $p) echo $p."</br>";
  echo "</p>";
}

?>
<table class="tlist" id="dom_edit_table">
<thead>
<tr><th colspan="2"> </th><th><?php __("Subdomain"); ?></th><th><?php __("Type");?></th><th><?php __("Status")?></th><th></th></tr>
</thead>
<?php
$hasadvanced=false;
for($i=0;$i<$r["nsub"];$i++) {
if ($r["sub"][$i]["advanced"] && !$hasadvanced) {
 $hasadvanced=true;
?>
</table>
<h3 style="padding: 40px 0 0 0"><?php __("Advanced subdomains"); ?></h3>
<p class="alert alert-warning"><?php __("The following entries are advanced ones, edit them at your own risks."); ?></p>
<table class="tlist" id="dom_edit_table">
<thead>
<tr><th colspan="2"> </th><th><?php __("Subdomain"); ?></th><th><?php __("Type");?></th><th><?php __("Status")?></th><th></th></tr>
</thead>
<?php

}

$disabled_class=in_array(strtoupper($r['sub'][$i]['enable']),array('DISABLED','DISABLE') )?'sub-disabled':'';
?>
	<tr class="lst" data-fqdn="<?php echo $r["sub"][$i]["fqdn"]; ?>">
    <?php if ( $r['sub'][$i]['web_action'] =='DELETE') { echo "<td colspan='2' />"; } else { ?>
		<td class="center">
    <?php  if (!(!$isinvited && $dt[strtolower($r["sub"][$i]["type"])]["enable"] != "ALL" )) { ?>
      <?php if ( isset($problems[$r["sub"][$i]["fqdn"]])) {  // if this subdomain have problem, can't modify it, only delete it
              __("Forbidden");
            } else { ?>
			<div class="ina edit"><a href="dom_subedit.php?sub_domain_id=<?php echo urlencode($r["sub"][$i]["id"]) ?>"><?php __("Edit"); ?></a></div><?php
            } // isset problems
        } ?>


			</td><td class="center">
    <?php  if (!(!$isinvited && $dt[strtolower($r["sub"][$i]["type"])]["enable"] != "ALL" )) { ?>
			<div class="ina delete"><a href="dom_subdel.php?sub_domain_id=<?php echo urlencode($r["sub"][$i]["id"]) ?>"><?php __("Delete"); ?></a></div>
<?php } ?>
		</td>
    <?php } // end IF ==DELETE ?>
		<td><div class="retour-auto <?php echo $disabled_class; ?>"><a href="http://<?php echo $r["sub"][$i]["fqdn"] ?>" target="_blank"><?php echo $r["sub"][$i]["fqdn"]; ?></a></div></td>
  <td><div class="retour-auto <?php echo $disabled_class; ?>"><?php if ($r['sub'][$i]['type_desc']) { __($r['sub'][$i]['type_desc']); } else { echo __("ERROR, please check your server setup"); } ?>
 <?php 
 //if ($r["sub"][$i]['type'] === 'VHOST') {
 if ( @$dt[$r["sub"][$i]['type']]['target'] === 'DIRECTORY') {
  $iidir=$r["sub"][$i]["dest"];
  if ($iidir=='') $iidir='/';
  echo '<br /><a href="bro_main.php?R='.urlencode($iidir).'">'.htmlspecialchars($iidir).'</a>';
  if ( ! file_exists($bro->convertabsolute($iidir,0))) { echo " <span class=\"alerte\">"._("Directory not found")."</span>"; }
} else {
  if ($r["sub"][$i]['type']) echo "<br />".htmlspecialchars($r["sub"][$i]["dest"]);
}
?></div></td>
		<td><?php 
			if (!(!$isinvited && $dt[strtolower($r["sub"][$i]["type"])]["enable"] != "ALL" )) {
				if ( $r['sub'][$i]['web_action'] !='DELETE') { 
					switch ($r['sub'][$i]['enable']) {
						case 'ENABLED':
							__("Enabled");
							echo "<br/><a href='dom_substatus.php?sub_id=".urlencode($r["sub"][$i]["id"])."&amp;status=disable'>";__("Disable");echo "</a>";
							break;
						case 'ENABLE':
							__("Activation pending");
							break;
						case 'DISABLED':
							__("Disabled");
							echo "<br/><a href='dom_substatus.php?sub_id=".urlencode($r["sub"][$i]["id"])."&amp;status=enable'>";__("Enable");echo "</a>";
							break;
						case 'DISABLE':
							__("Desactivation pending");
							break;
					}
				}
      }?></td>
		<td><?php 
      switch ($r['sub'][$i]['web_action']) {
        case 'UPDATE':
          __("Update pending");
          break;
        case 'DELETE':
          __("Deletion pending");
          break;
        case 'OK':
        default:
          break;
      }?></td>
            
	</tr>
<?php } ?>
</table>
<?php
// Add a class on the sub_domains who have a problem
foreach ($problems as $pr => $lm) { // $problems can be empty but can't be null/false
  echo "<script type='text/javascript'>$(\"tr[data-fqdn='".$pr."']\").addClass('alert-danger-tr');</script>\n";
}
?>
</div>


<div id="tabsdom-addsub">
<h3><?php printf(_("Add a subdomain to %s"),$domain); ?></h3>
<?php
$isedit=false;
require_once('dom_edit.inc.php');
sub_domains_edit($domain);
?>
<br />
<?php $mem->show_help("edit_domain"); ?>
<!-- *****************************************
		 modification des parametres dns
 -->

</div>
    <?php
if (!$r['noerase']) {
?>

<div id="tabsdom-params">
<h3><?php __("DNS &amp; Email parameters"); ?></h3>
<form action="dom_editdns.php?domain=<?php echo urlencode($r["name"]) ?>" method="post" id="fdns" name="fdns" onSubmit="return destruction_alert();">
 <?php csrf_get(); ?>
<table class="tlist2">
<tr>
  <td><?php __("Manage the DNS on the server ?"); ?></td>
  <td> 
     <input type="radio" id="yesdns" class="inc" name="dns" value="1"<?php cbox($r["dns"]); ?> />&nbsp;<label for="yesdns"><?php __("Yes"); ?></label>
      </td><td><input type="radio" id="nodns" class="inc" name="dns" value="0"<?php cbox(!$r["dns"]); ?> />&nbsp;<label for="nodns"><?php __("No"); ?></label>
  </td>
</tr>
</table>

<?php if ($r["dns"]) { ?>
<table class="tlist2">
<tr>
  <td><?php __("Define TTL for the zone records"); ?>&nbsp;: </td>
  <td> 
     <input type="text" id="ttldns" class="inc" name="ttl" size="6" value="<?php ehe($r["zonettl"]); ?>" /> <?php __("seconds"); ?> <small><i><?php __("Warning: a low TTL can be problematic. It is recommended not to use a lower TTL than 3600 seconds."); ?></i></small>
  </td>
</tr>
</table>
<?php } ?>

<table class="tlist2">
<tr>
  <td>
    <?php __("Manage the Emails Addresses of this domain on the server?"); ?>
  </td>
  <td> 
     <input type="radio" id="yesemail" class="inc" name="email" value="1"<?php cbox($r["mail"]); ?> />&nbsp;<label for="yesemail"><?php __("Yes"); ?></label>
     </td><td><input type="radio" id="noemail" class="inc" name="email" value="0"<?php cbox(!$r["mail"]); ?> />&nbsp;<label for="noemail"><?php __("No"); ?></label>
  </td>
</tr>
</table>
<p class="alert alert-warning">    <?php __("Warning: If you set this to 'no', all your email accounts and aliases on this domain will be immediately deleted."); ?></p>
<input type="submit" class="inb ok" name="submit" value="<?php __("Submit the changes"); ?>" />
	</form>

</div>

<?php if ( $r["dns"] ) { ?>
<div id="tabsdom-view">
<p>
<?php __("Here is the actual DNS zone running on the AlternC server. If you just made some changes, you have to wait for it."); ?>
</p>

<pre><span class="petit" id="divdumpdns">
<a target="_blank" href="dom_dnsdump.php?domain=<?php echo urlencode($domain) ?>"><?php __("Click here to view the dump");?></a>
</span>
</pre>
<a href="javascript:force_update_dns_content();"><?php __("Refresh");?></a>

</div>
<?php } // if dns ?>

<div id="tabsdom-delete">
  <h3><?php __("Domain removal"); ?></h3>
  <?php printf(_("If you want to destroy the domain %s, click on the button below. Warning: this also deletes all FTP accounts, email, mailing lists associated with the domain and subdomains."),$domain); ?><br />
  <form action="dom_dodel.php?domain=<?php echo urlencode($domain) ?>" method="post">
 <?php csrf_get(); ?>
    <p>
      <input type="submit" class="inb delete" name="detruire" value="<?php printf(_("Delete %s from this server"),$domain); ?>" />
    </p>
  </form>
</div> <!-- tabsdom-delete -->
</div> <!-- tabsdom -->
<?php } else { // noerase 
  ?>
<div id="tabsdom-params">  
   <p class="alert alert-info"><?php __("This domain is locked, only a server administrator can unlock it."); ?></p>
</div>
    <?php
} ?>
<script type="text/javascript">
//document.forms['main'].sub.focus(); // not with tabs

$(function() {
  $("#tabsdom").tabs();
});

get_dns_content = 1;
function update_dns_content(){
  if ( get_dns_content == 1 ) {
    get_dns_content = 0;

    $.ajax({
      url: "dom_dnsdump.php?domain=<?php echo urlencode($domain)?>",
      }).done(function( html ) {
      $("#divdumpdns").html(html);
    });
  }
}

function force_update_dns_content(){
  get_dns_content = 1;
  $("#divdumpdns").html('In progress...');
  update_dns_content();
}

$(document).ready(function() 
    { 
        $("#dom_edit_table").tablesorter(); 
    } 
); 

</script>
<?php include_once("foot.php"); ?>
