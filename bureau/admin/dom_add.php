<?php
/*
 $Id: dom_add.php,v 1.5 2003/06/10 13:16:11 root Exp $
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
 Purpose of file: Add a new domain
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

include_once("head.php");

$fields = array (
	"newdomain"   => array ("request", "string", ""),
	"yndns"   => array ("request", "integer", 0),
	"newisslave"   => array ("request", "integer", 0),
	"slavedom"   => array ("request", "string", ""),
);
getFields($fields);

if (!isset($dns)) $dns="1";

?>
<h3><?php __("Domain hosting"); ?></h3>
<hr />
<?php
if (!$quota->cancreate("dom")) { ?>
<p class="error"><?php echo _("You cannot add any new domain, your quota is over.")." "._("Contact your administrator for more information."); ?></p>
<?php
exit();
}
if ($error) echo "<p class=\"error\">$error</p>";
?>
<form method="post" action="dom_doadd.php" id="main">
<p>
<label for="newdomain"><b><?php __("Domain name"); ?> :</b></label> <span class="int" id="newdomwww">www.</span><input type="text" class="int" id="newdomain" name="newdomain" value="<?php ehe($newdomain); ?>" size="32" maxlength="255" />
</p>
<p>
  <input type="checkbox" name="dns" class="inc" value="1" id="yndns"<?php checked($dns=="1"); ?>/>&nbsp;<label for="yndns"><?php __("host my dns here"); ?></label>
</p>
<?php
  $q = $quota->getquota("dom");
if ($q["u"]>0) {
?> 
<p>
    <?php __("Do you want to point this domain to another domain already installed in your account?"); ?>
<br />
   <input type="radio" id="newisslave0" name="newisslave" value="0"<?php checked($newisslave==0); ?>/><label for="newisslave0"><?php __("No: This domain will have its own folder."); ?></label>
<br />
   <input type="radio" id="newisslave1" name="newisslave" value="1"<?php checked($newisslave==1); ?>/><label for="newisslave1"><?php __("Yes, redirect this new domain to this one:"); ?> </label> <select name="slavedom" id="slavedom" class="inl">
 <option value=""><?php __("-- Choose a domain --"); ?></option>
<?php
$dl=$dom->get_domain_list();
  $ddl=array();
  foreach($dl as $d) {
    $ddl[$d]=$d;
  }
  eoption($ddl,"slavedom");
?></select>
<br />
</p>
    <?php } ?>

<p>
  <input type="submit" class="inb" name="submit" value="<?php __("Add this domain"); ?>" />
</p>

<p class="error">
<small>
<?php __("If you don't want to host in our server the DNS of your domain, don't check the box 'host my dns here'. If you don't know what it mean, leave it checked."); ?></small></p>
<?php $mem->show_help("add_domain"); ?>
</form>
<?php
	if (is_array($dom->dns)) {
		echo "<br />"._("Whois result on the domain")." : <pre>";
		reset($dom->dns);
		while (list($key,$val)=each($dom->dns)) {
			echo "nameserver: $val\n";
		}
		echo "</pre>";
	}
?>
<script type="text/javascript">
document.forms['main'].newdomain.focus();
</script>
<?php include_once("foot.php"); ?>
