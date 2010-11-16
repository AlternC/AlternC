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
	"domain"    => array ("request", "string", ""),
	"sub"       => array ("request", "string", ""),
	"type"      => array ("request", "integer", $dom->type_local),
	"sub_local" => array ("request", "string",  "/"),
	"sub_url"   => array ("request", "string", "http://"), 
	"sub_ip"    => array ("request", "string", ""),
	"sub_ipv6"  => array ("request", "string", ""),
	"sub_cname" => array ("request", "string", ""),
	"sub_txt"   => array ("request", "string", ""),
);
getFields($fields);

$dom->lock();
if (!$r=$dom->get_domain_all($domain)) {
	$error=$err->errstr();
}
$dom->unlock();

?>
<script type="text/javascript">
function dnson() {
	// Active les composants DNS :
	if (document.forms["fdns"].mx.disabled!=null)
		document.forms["fdns"].mx.disabled=false;
	if (document.forms["fdns"].emailon.disabled!=null)
		document.forms["fdns"].emailon.disabled=true;
	if (document.forms["fdns"].emailoff.disabled!=null)
		document.forms["fdns"].emailoff.disabled=true;
}
function dnsoff() {
	// Active les composants DNS :
	if (document.forms["fdns"].mx.disabled!=null)
		document.forms["fdns"].mx.disabled=true;
	if (document.forms["fdns"].emailon.disabled!=null)
		document.forms["fdns"].emailon.disabled=false;
	if (document.forms["fdns"].emailoff.disabled!=null)
		document.forms["fdns"].emailoff.disabled=false;
}
</script>
<h3><?php printf(_("Editing subdomains of %s"),$domain); ?></h3>
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p>";
	}
?>
<hr id="topbar"/>
<br />
<!-- *****************************************
		 gestion des sous-domaines
 -->
<table class="tlist">
<tr><th colspan="2"><?php __("Actions"); ?></th><th><?php __("Subdomain"); ?></th><th><?php __("Place"); ?></th></tr>
<?php
$col=1;
for($i=0;$i<$r["nsub"];$i++) {
	$col=3-$col;
?>
	<tr class="lst<?php echo $col; ?>">
		<td class="center">
			<div class="ina"><a href="dom_subedit.php?domain=<?php echo urlencode($r["name"]) ?>&amp;sub=<?php  echo urlencode($r["sub"][$i]["name"]) ?>&amp;type=<?php  echo urlencode($r["sub"][$i]["type"]) ?>"><img src="images/edit.png" alt="<?php __("Edit"); ?>" /><?php __("Edit"); ?></a></div>

			</td><td class="center">
			<div class="ina"><a href="dom_subdel.php?domain=<?php echo urlencode($r["name"]) ?>&amp;sub=<?php  echo urlencode($r["sub"][$i]["name"]) ?>&amp;type=<?php  echo urlencode($r["sub"][$i]["type"]) ?>"><img src="images/delete.png" alt="<?php __("Delete"); ?>" /><?php __("Delete"); ?></a></div>
		</td>
		<td><a href="http://<?php ecif($r["sub"][$i]["name"],$r["sub"][$i]["name"]."."); echo $r["name"] ?>" target="_blank"><?php ecif($r["sub"][$i]["name"],$r["sub"][$i]["name"]."."); echo $r["name"] ?></a></td>
		<td><?php echo $r["sub"][$i]['type'] === '0' ? '<a href="bro_main.php?R='.urlencode($r["sub"][$i]["dest"]).'">'.htmlspecialchars($r["sub"][$i]["dest"]).'</a>' : htmlspecialchars($r["sub"][$i]["dest"]); ?>&nbsp;</td>
	</tr>
<?php } ?>
</table>
<br />
<hr/>
<br />
<form action="dom_subdoedit.php" method="post" name="main" id="main">
	<table border="0">
		<tr>
			<td>
			<input type="hidden" name="domain" value="<?php ehe($r["name"]); ?>" />
			<input type="hidden" name="action" value="add" />
  <?php __("Create a subdomain:"); ?></td><td>
<input type="text" class="int" name="sub" style="text-align:right" value="<?php ehe($sub); ?>" size="22" id="sub" /><span class="int" id="newsubname">.<?php echo $domain; ?></span></td>
		</tr>
		<tr>
<td><input type="radio" id="local" class="inc" name="type" value="<?php echo $dom->type_local; ?>" <?php cbox($type==$dom->type_local); ?> onclick="document.main.sub_local.focus();" />
				<label for="local"><?php __("Locally managed"); ?></label></td>
			<td><input type="text" class="int" name="sub_local" id="sub_local" value="<?php ehe($sub_local); ?>" size="28" />
<script type="text/javascript">
<!--
  document.write("&nbsp;<input type=\"button\" name=\"bff\" onclick=\"browseforfolder('main.sub_local');\" value=\" <?php __("Choose a folder..."); ?> \" class=\"bff\">");
//  -->
</script>
</td>
		</tr>
		<tr>
			<td><input type="radio" id="url" class="inc" name="type" value="<?php echo $dom->type_url; ?>" <?php cbox($type==$dom->type_url); ?> onclick="document.main.sub_url.focus();" />
				<label for="url" ><?php __("URL redirection"); ?></label></td>
			<td><input type="text" class="int" name="sub_url" id="sub_url" value="<?php ehe($sub_url); ?>" size="50" /></td>
		</tr>
		<tr>
		<td><input type="radio" id="webmail" class="inc" name="type" value="<?php echo $dom->type_webmail; ?>" <?php cbox($type==$dom->type_webmail); ?>/>
				<label for="webmail"><?php __("Webmail access"); ?></label></td>
			<td>&nbsp;</td>
		</tr>
		<?php if ($r["dns"]) { // show only if dns is enabled ?>
		<tr>
			<td><input type="radio" id="ip" class="inc" name="type" value="<?php echo $dom->type_ip; ?>" <?php cbox($type==$dom->type_ip); ?> onclick="document.main.sub_ip.focus();" />
				<label for="ip"><?php __("IP redirection"); ?></label></td>
		<td><input type="text" class="int" name="sub_ip" id="sub_ip" value="<?php ehe($sub_ip); ?>" size="16" /> <small><?php __("(enter an IPv4 address, for example 192.168.1.2)"); ?></small></td>
		</tr>

		<tr><td colspan=2 style="background-color: #CFE3F1;color: #007777;font-weight:bold;" >Advanced options</td></tr>
		<tr id="advopt1">
			<td><input type="radio" id="ipv6" class="inc" name="type" value="<?php echo $dom->type_ipv6; ?>" <?php cbox($type==$dom->type_ipv6); ?> onclick="document.main.sub_ipv6.focus();" />
				<label for="ipv6"><?php __("IPv6 redirection"); ?></label></td>
			<td><input type="text" class="int" name="sub_ipv6" id="sub_ipv6" value="<?php ehe($sub_ipv6); ?>" size="32" /> <small><?php __("(enter an IPv6 address, for example 2001:0910::0)"); ?></small></td>
		</tr>

		<tr id="advopt2">
			<td><input type="radio" id="cname" class="inc" name="type" value="<?php echo $dom->type_cname; ?>" <?php cbox($type==$dom->type_cname); ?> onclick="document.main.sub_cname.focus();" />
				<label for="cname"><?php __("CNAME redirection"); ?></label></td>
		<td><input type="text" class="int" name="sub_cname" id="sub_cname" value="<?php ehe($sub_cname); ?>" size="32" /> <small><?php __("(enter a server address or a subdomain)"); ?></small></td>
		</tr>

		<tr id="advopt3">
			<td><input type="radio" id="txt" class="inc" name="type" value="<?php echo $dom->type_txt; ?>" <?php cbox($type==$dom->type_txt); ?> onclick="document.main.sub_txt.focus();" />
				<label for="txt"><?php __("TXT information"); ?></label></td>
		<td><input type="text" class="int" name="sub_txt" id="sub_txt" value="<?php ehe($sub_txt); ?>" size="32" /> <small><?php __("(enter a TXT informations for this domain)"); ?></small></td>
		</tr>


		<? } ?>
		<tr class="trbtn">
			<td colspan="2"><input type="submit" class="inb" name="add" value="<?php __("Add this subdomain"); ?>" /></td>
		</tr>
	</table>
</form>
<?php $mem->show_help("edit_domain"); ?>
<p>&nbsp;</p>
<p>&nbsp;</p>
<!-- *****************************************
		 modification des parametres dns
 -->
<?php
if (!$r[noerase]) {
?>

<hr />
<h3><?php __("DNS parameters"); ?></h3>
<form action="dom_editdns.php?domain=<?php echo urlencode($r["name"]) ?>" method="post" id="fdns" name="fdns">
<table border="1" cellpadding="6" cellspacing="0">
<tr><td colspan="2"><?php __("Manage the DNS on the server ?"); ?></td></tr>
<tr>
								      <td align="center" width="65%"><input type="radio" id="yesdns" class="inc" name="dns" value="1"<?php cbox($r["dns"]); ?> onclick="dnson();" />&nbsp;<label for="yesdns"><?php __("Yes"); ?></label></td>
   <td align="center" width="35%"><input type="radio" id="nodns" class="inc" name="dns" value="0"<?php cbox(!$r["dns"]); ?> onclick="dnsoff();" />&nbsp;<label for="nodns"><?php __("No"); ?></label></td>
</tr>
<tr>
	<td width="65%" valign="top">
	<p>
<?php printf(_("help_dns_mx %s %s"),$L_MX,$L_HOSTING); ?>
	</p>
	<label for="mx"><?php __("MX Field"); ?> : </label><input type="text" class="int" name="mx" id="mx" value="<?php if ($r["dns"]) echo $r["mx"]; else echo $L_MX; ?>" <?php if (!$r["dns"]) echo "disabled=\"disabled\""; ?> />
	</td>
	<td width="35%" valign="top">
	<p>
	<?php __("help_dns_mail"); ?></p>
<p>
	 <input type="radio" id="emailon" class="inc" name="email" id="emailon" value="1"<?php cbox($r["mail"]); ?> <?php if ($r["dns"]) echo "disabled=\"disabled\""; ?>/><label for="emailon"><?php __("Yes"); ?></label>
<br />
         <input type="radio" id="emailoff" class="inc" name="email" id="emailoff" value="0"<?php cbox(!$r["mail"]); ?> <?php if ($r["dns"]) echo "disabled=\"disabled\""; ?>/><label for="emailoff"><?php __("No"); ?></label>
</p>
	<p>																										  <?php __("Warning: If you set this to 'no', all your email accounts and aliases on this domain will be immediately deleted."); ?>
</p>
	</td>

</tr>
<tr class="trbtn"><td colspan="2"><input type="submit" class="inb" name="submit" value="<?php __("Submit the changes"); ?>" /></td></tr>
</table>
	</form>

<!-- *****************************************
		 destruction du domaine
 -->
<br />
<?php printf(_("help_domain_del %s"),$domain); ?><br />
<form action="dom_dodel.php?domain=<?php echo urlencode($domain) ?>" method="post">
<p>
<input type="submit" class="inb" name="detruire" value="<?php printf(_("Delete %s from this server"),$domain); ?>" />
</p>
</form>
<hr />
<?php } // noerase ?>
<script type="text/javascript">
document.forms['main'].sub.focus();
</script>
<?php include_once("foot.php"); ?>
