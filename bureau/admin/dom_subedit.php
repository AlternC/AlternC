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

$dom->lock();
if (!$r=$dom->get_sub_domain_all($domain,$sub)) {
	$error=$err->errstr();
}
$dom->unlock();

include("head.php");
?>
</head>
<body>
<h3><?php __("Edition du sous-domaine"); ?> http://<?php ecif($sub,$sub."."); echo $domain; ?> : </h3>
<?php
	if ($error) {
		echo "<p class=\"erroe\">$error</p></body></html>";
		exit();
	}
?>
<hr />
<!-- *****************************************
		 gestion du sous-domaine
 -->
<h3><?php __("Sous-domaine"); ?> http://<?php ecif($sub,$sub."."); echo $domain; ?></h3>
<form action="dom_subdoedit.php" method="post" id="main" name="main">
	<table border="0">
	<tr> 
		<td>	<input type="hidden" name="domain" value="<?php echo $domain ?>" />
	<input type="hidden" name="sub" value="<?php echo $sub ?>" />
	<input type="hidden" name="action" value="edit" />

<input type="radio" id="local" class="inc" name="type" value="<?php echo $dom->type_local; ?>" <?php cbox($r["type"]==$dom->type_local); ?> onclick="document.main.sub_local.focus();" />
			<label for="local"><?php __("Locally managed"); ?></label></td>
		<td><input type="text" class="int" name="sub_local" id="sub_local" value="<?php ecif($r["type"]==0,$r["dest"],"/"); ?>" size="40" />
<script type="text/javascript">
<!--
  document.write("&nbsp;<input type=\"button\" name=\"bff\" onclick=\"browseforfolder('main.sub_local');\" value=\" ... \" class=\"inb\">");
//  -->
</script>
</td>
	</tr>
	<tr>
		<td><input type="radio" id="url" class="inc" name="type" value="<?php echo $dom->type_url; ?>" <?php cbox($r["type"]==$dom->type_url); ?> onclick="document.main.sub_url.focus();" />
			<label for="url"><?php __("URL redirection"); ?></label></td>
		<td><input type="text" class="int" name="sub_url" id="sub_url" value="<?php ecif($r["type"]==1,$r["dest"],"http://"); ?>" size="50" /></td>
	</tr>
	<tr>
		<td><input type="radio" id="ip" class="inc" name="type" value="<?php echo $dom->type_ip; ?>" <?php cbox($r["type"]==$dom->type_ip); ?> onclick="document.main.sub_ip.focus();" />
			<label for="ip"><?php __("IP redirection"); ?></label></td>
		<td><input type="text" class="int" name="sub_ip" id="sub_ip" value="<?php ecif($r["type"]==2,$r["dest"],"xxx.xxx.xxx.xxx"); ?>" size="16" /></td>
	</tr>
	<tr>
		<td><input type="radio" id="webmail" class="inc" name="type" value="<?php echo $dom->type_webmail; ?>" <?php cbox($r["type"]==$dom->type_webmail); ?> />
			<label for="webmail"><?php __("Webmail access"); ?></label></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td><input type="submit" class="inb" name="submit" value="Valider les changements" /></td>
		<td><input type="button" class="inb" name="back" value="Annuler" onclick="history.back();" /></td>
	</tr>
	</table>
</form>
</body>
</html>
