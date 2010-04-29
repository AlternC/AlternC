<?php
/*
 $Id: dom_subedit.php,v 1.3 2003/08/13 23:01:45 root Exp $
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
 Original Author of file:
 Purpose of file:
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
	"action"    => array ("request", "string", "add"),
);
getFields($fields);

$dom->lock();
if (!$noread) {
  if (!$r=$dom->get_sub_domain_all($domain,$sub)) {
    $error=$err->errstr();
    ?>
      <h3><?php __("Editing subdomain"); ?> http://<?php ecif($sub,$sub."."); echo $domain; ?></h3>
<?php
	echo "<p class=\"error\">$error</p>";
	include_once("foot.php");
	exit();
 } 

$sub=$r["name"];
$type=$r["type"];
switch ($type) {
 case $dom->type_local:
   $sub_local=$r["dest"];
   break;
 case $dom->type_url:
   $sub_url=$r["dest"];
   break;
 case $dom->type_ip:
   $sub_ip=$r["dest"];
   break;
 case $dom->type_webmail:
   break;
 }
 }

$dom->unlock();

?>
<h3><?php __("Editing subdomain"); ?> http://<?php ecif($sub,$sub."."); echo $domain; ?></h3>
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p>";
	}
?>
<hr id="topbar"/>
<br />
<!-- *****************************************
		 gestion du sous-domaine
 -->
<form action="dom_subdoedit.php" method="post" id="main" name="main">
	<table border="0">
	<tr>
		<td>	<input type="hidden" name="domain" value="<?php ehe($domain); ?>" />
	<input type="hidden" name="sub" value="<?php echo ehe($sub); ?>" />
	<input type="hidden" name="action" value="edit" />

<input type="radio" id="local" class="inc" name="type" value="<?php echo $dom->type_local; ?>" <?php cbox($r["type"]==$dom->type_local); ?> onclick="document.main.sub_local.focus();" />
			<label for="local"><?php __("Locally managed"); ?></label></td>
		<td><input type="text" class="int" name="sub_local" id="sub_local" value="<?php ehe($sub_local); ?>" size="40" />
<script type="text/javascript">
<!--
  document.write("&nbsp;<input type=\"button\" name=\"bff\" onclick=\"browseforfolder('main.sub_local');\" value=\" <?php __("Choose a folder..."); ?> \" class=\"bff\">");
//  -->
</script>
</td>
	</tr>
	<tr>
		<td><input type="radio" id="url" class="inc" name="type" value="<?php echo $dom->type_url; ?>" <?php cbox($type==$dom->type_url); ?> onclick="document.main.sub_url.focus();" />
			<label for="url"><?php __("URL redirection"); ?></label></td>
		<td><input type="text" class="int" name="sub_url" id="sub_url" value="<?php ehe($sub_url); ?>" size="50" /></td>
	</tr>
	<tr>
		<td><input type="radio" id="ip" class="inc" name="type" value="<?php echo $dom->type_ip; ?>" <?php cbox($type==$dom->type_ip); ?> onclick="document.main.sub_ip.focus();" />
			<label for="ip"><?php __("IP redirection"); ?></label></td>
		<td><input type="text" class="int" name="sub_ip" id="sub_ip" value="<?php ehe($sub_ip); ?>" size="16" /> <small><?php __("(enter an IPv4 address, for example 192.168.1.2)"); ?></small></td>
	</tr>
	<tr>
		<td><input type="radio" id="webmail" class="inc" name="type" value="<?php echo $dom->type_webmail; ?>" <?php cbox($r["type"]==$dom->type_webmail); ?> />
			<label for="webmail"><?php __("Webmail access"); ?></label></td>
		<td>&nbsp;</td>
	</tr>
	<tr class="trbtn">
            <td colspan="2">
              <input type="submit" class="inb" name="submit" value="<?php __("Validate this change"); ?>" />
              <input type="button" class="inb" name="back" value="<?php __("Cancel"); ?>" onclick="document.location='dom_edit.php?domain=<?php ehe($domain); ?>'" />
            </td>
        </tr>

	</table>

</form>
<?php include_once("foot.php"); ?>
