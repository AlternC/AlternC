<?php
/*
 $Id: mail_list.php,v 1.8 2005/04/01 16:05:26 benjamin Exp $
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
 Original Author of file: Benjamin Sonntag, Franck Missoum
 Purpose of file: Show the mail account list on domain $dom
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"domain"    => array ("request", "string", ""),

	"letter"    => array ("get", "string", ""),
);
getFields($fields);

if(!$domain)
{
	include("main.php");
	exit();
}

if(!$res=$mail->enum_doms_mails($domain,1,$letter)) {
  $error=$err->errstr();
?>
<h3><?php printf(_("Email addresses of the domain %s"),$domain); ?> : </h3>
<?php
if ($error) {
  echo "<p class=\"error\">$error</p>";
}
?>
<hr id="topbar"/>
<br />
<p>
  <span class="inb"><a href="mail_add.php?domain=<?php echo $domain; ?>"><?php printf(_("Add a mailbox on <b>%s</b>"),$domain); ?></a></span> 
  <span class="inb"><a href="mail_add.php?many=1&amp;domain=<?php echo $domain; ?>"><?php printf(_("Add many mailboxes on <b>%s</b>"),$domain); ?></a></span>
</p>
<?
}
else
{

?>
<h3><?php printf(_("Email addresses of the domain %s"),$domain); ?> : </h3>
<?php
if ($error) {
  echo "<p class=\"error\">$error</p>";
}
?>
<hr id="topbar"/>
<br />
<p>
  <span class="inb"><a href="mail_add.php?domain=<?php echo $domain; ?>"><?php printf(_("Add a mailbox on <b>%s</b>"),$domain); ?></a></span> 
  <span class="inb"><a href="mail_add.php?many=1&amp;domain=<?php echo $domain; ?>"><?php printf(_("Add many mailboxes on <b>%s</b>"),$domain); ?></a></span>
</p>
<?php

if(!$letters=$mail->enum_doms_mails_letters($domain))
  $error=$err->errstr();
else{
  echo "<p>";
  __("Show only mail starting by:"); 
  echo " ";
  for($i=0;$i<count($letters);$i++){
    $val=$letters[$i];
    echo "   <a href=\"mail_list.php?domain=$domain&amp;letter=$val\">$val&nbsp;</a>";
  }
  echo "   <a href=\"mail_list.php?domain=$domain\">".sprintf(_("All"))."</a>";
  echo "</p>";
}

 if ($res["count"]) {
?>
<form method="post" action="mail_del.php" id="main">

<table class="tlist">

<tr><th colspan="2"><input type="hidden" name="domain" value="<?php echo $domain ?>"/>
<?php __("Actions"); ?></th><th><?php __("Email address"); ?></th><th><?php __("Size"); ?></th></tr>
<?php
$col=1;
for($i=0;$i<$res["count"];$i++) {
	$col=3-$col;
	$val=$res[$i];
	echo "<tr class=\"lst$col\">";
	echo "<td align=\"center\"><input class=\"inc\" type=\"checkbox\" id=\"del_$i\" name=\"d[]\" value=\"".$val["mail"]."\" /></td>";
?>
<td><div class="ina"><a href="mail_edit.php?email=<?php echo urlencode($val["mail"]);  ?>&amp;domain=<?php echo urlencode($domain); ?>"><img src="images/edit.png" alt="<?php __("Edit"); ?>" /><?php __("Edit"); ?></a></div></td>

<?php
	echo "<td><label for=\"del_$i\">".$val["mail"]."</label></td>";
	if ($val["pop"]) {
		echo "<td>".format_size($val["size"])."</td>";
	} else {
		echo "<td>&nbsp;</td>";
	}
	echo "</tr>";

}
?>
</table>
<br />
<input type="submit" class="inb" name="submit" value="<?php __("Delete the checked email addresses"); ?>" />
</form>

<?php
   }
}
?>
<?php include_once("foot.php"); ?>
