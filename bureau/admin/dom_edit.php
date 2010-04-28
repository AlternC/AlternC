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
	if (document.forms["dns"].mx.disabled!=null)
		document.forms["dns"].mx.disabled=false;
	if (document.forms["dns"].mail.disabled!=null)
		document.forms["dns"].mail.disabled=true;
}
function dnsoff() {
	// Active les composants DNS :
	if (document.forms["dns"].mx.disabled!=null)
		document.forms["dns"].mx.disabled=true;
	if (document.forms["dns"].mail.disabled!=null)
		document.forms["dns"].mail.disabled=false;
}
</script>
<h3><?php printf(_("Editing subdomains of %s"),$domain); ?></h3>
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p>";
	}
?>
<hr />
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
			<div class="ina"><a href="dom_subedit.php?domain=<?php echo urlencode($r["name"]) ?>&amp;sub=<?php  echo urlencode($r["sub"][$i]["name"]) ?>"><img src="images/edit.png" alt="<?php __("Edit"); ?>" /><?php __("Edit"); ?></a></div>

			</td><td class="center">
			<div class="ina"><a href="dom_subdel.php?domain=<?php echo urlencode($r["name"]) ?>&amp;sub=<?php  echo urlencode($r["sub"][$i]["name"]) ?>"><img src="images/delete.png" alt="<?php __("Delete"); ?>" /><?php __("Delete"); ?></a></div>
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
			<td colspan="2">
			<input type="hidden" name="domain" value="<?php echo $r["name"]; ?>" />
			<input type="hidden" name="action" value="add" />
<?php __("Create a subdomain:"); ?>
<input type="text" class="int" name="sub" style="text-align:right" value="" size="22" id="sub" /><span class="int" id="newsubname">.<?php echo $domain; ?></span></td>
		</tr>
		<tr>
			<td><input type="radio" id="local" class="inc" name="type" value="<?php echo $dom->type_local; ?>" checked="checked" onclick="document.main.sub_local.focus();" />
				<label for="local"><?php __("Locally managed"); ?></label></td>
			<td><input type="text" class="int" name="sub_local" id="sub_local" value="/" size="28" />
<script type="text/javascript">
<!--
  document.write("&nbsp;<input type=\"button\" name=\"bff\" onclick=\"browseforfolder('main.sub_local');\" value=\" <?php __("Choose a folder..."); ?> \" class=\"bff\">");
//  -->
</script>
</td>
		</tr>
		<tr>
			<td><input type="radio" id="url" class="inc" name="type" value="<?php echo $dom->type_url; ?>" onclick="document.main.sub_url.focus();" />
				<label for="url" ><?php __("URL redirection"); ?></label></td>
			<td><input type="text" class="int" name="sub_url" id="sub_url" value="http://" size="50" /></td>
		</tr>
		<?php if ($r["dns"]) { // show only if dns is enabled ?>
		<tr>
			<td><input type="radio" id="ip" class="inc" name="type" value="<?php echo $dom->type_ip; ?>" onclick="document.main.sub_ip.focus();" />
				<label for="ip"><?php __("IP redirection"); ?></label></td>
			<td><input type="text" class="int" name="sub_ip" id="sub_ip" value="xxx.xxx.xxx.xxx" size="16" /></td>
		</tr>
		 <? } ?>
		<tr>
			<td><input type="radio" id="webmail" class="inc" name="type" value="<?php echo $dom->type_webmail; ?>" />
				<label for="webmail"><?php __("Webmail access"); ?></label></td>
			<td>&nbsp;</td>
		</tr>
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
<form action="dom_editdns.php?domain=<?php echo urlencode($r["name"]) ?>" method="post" id="dns">
<table border="1" cellpadding="6" cellspacing="0">
<tr><td colspan="2"><?php __("Manage the DNS on the server ?"); ?></td></tr>
<tr>
	<td align="center" width="65%"><label for="yesdns"><?php __("Yes"); ?></label><input type="radio" id="yesdns" class="inc" name="dns" value="1"<?php if ($r["dns"]) echo " checked=\"checked\"" ?> onclick="dnson();" /></td>
	<td align="center" width="35%"><label for="nodns"><?php __("No"); ?></label><input type="radio" id="nodns" class="inc" name="dns" value="0"<?php if (!$r["dns"]) echo " checked=\"checked\"" ?> onclick="dnsoff();" /></td>
</tr>
<tr>
	<td width="65%" valign="top">
	<p>
<?php printf(_("help_dns_mx %s %s"),$L_MX,$L_HOSTING); ?>
	</p>
	<label for="mx"><?php __("MX Field"); ?> : </label><input type="text" class="int" name="mx" id="mx" value="<?php echo $r["mx"] ?>" <?php if (!$r["dns"]) echo "disabled=\"disabled\""; ?> />
	</td>
	<td width="35%" valign="top">
	<p>
	<?php __("help_dns_mail"); ?></p>
	<select class="inl" id="email" name="email" <?php if ($r["dns"]) echo "disabled=\"disabled\""; ?>><option value="1"<?php if ($r["mail"]) echo " selected=\"selected\"";?>><?php __("Yes"); ?></option><option value="0"<?php if (!$r["mail"]) echo " selected=\"selected\"";?>><?php __("No"); ?></option></select>
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
