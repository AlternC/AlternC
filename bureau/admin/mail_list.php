<?php
/*
 $Id: mail_list.php, author: squidly
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
 Purpose of file: listing of mail accounts 
 ----------------------------------------------------------------------
*/

require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"domain"    => array ("request", "string", ""),
	"domain_id"    => array ("request", "integer", ""),
);

$champs=getFields($fields);

if( !$domain && !$domain_id )
{
	include("main.php");
	exit();
}

$domain_id=$champs["domain_id"];

if(!$mails_list = $mail->enum_domain_mails($domain_id)){
	$error=$err->errstr();
}
?>

<?php
if (isset($error)) {
  	echo "<p class=\"error\">$error</p>";
}

//Mail creation.
if ($quota->cancreate("mail")) { ?>
<h3><?php __("Create a new mail account");?></h3>
	<form method="post" action="mail_doadd.php" id="main" name="mail_create" onsubmit="return is_valid_mail(document.getElementById('mail_arg').value+"@"+document.getElementById('domain') )">
		<input type="text" class="int" name="mail_arg" value="" size="20" id="mail_arg" maxlength="32" /><span id="emaildom" class="int" > <?php echo "@".$domain; ?></span>
		<input type="hidden" name="domain_id"  value="<?php echo $domain_id;?>" />
		<input type="hidden" name="domain" id="domain"  value="<?php echo $domain;?>" />
		<input type="hidden" name="arg" id="arg"  value="<?php echo $domain;?>" />
		<input type="submit" name="submit" class="inb" value="<?php __("Create"); ?>" />
	</form>
<?php 
}

if (empty($mails_list)){ // If there is no mail for this domain 
	__("No mail for this domain");
} else {
?>

<h3><?php printf(_("Email addresses of the domain %s"),$domain); ?> : </h3>

<table class="tlist">
<tr><th><?php __("Active");?></th><th align=center><?php __("Address"); ?></th><th><?php __("State"); ?></th></tr>
<?php

$col=1;
//listing of every mail of the current domain.
while (list($key,$val)=each($mails_list)){
	$col=3-$col;
	?>
	<tr class="lst_clic<?php echo $col; ?>" onclick="javascript:window.location.href='mail_properties.php?mail_id=<?php echo $val["id"]; ?>'">
	<td><?php if ($val["enabled"] ) { ?>
			<img src="images/check_ok.png" alt="<?php __("Enabled"); ?>" />
		<?php } else { ?>
			<img src="images/check_no.png" alt="<?php __("Disabled"); ?>" />
		<?php } // if enabled ?>
	</td>
	<td align=right><?php echo $val["address"]."@".$domain ?></td>
	<td><div class="ina"><a href="mail_properties.php?mail_id=<?php echo $val["id"] ?>"><img src="images/edit.png" alt="<?php __("Edit"); ?>" /><?php __("Edit"); ?></a></div></td>
	</tr>
	<?php
}
} // end if no mail for this domain
?>

</table>
<?php include_once("foot.php"); ?>
