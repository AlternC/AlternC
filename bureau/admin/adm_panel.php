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
 * Administrator misc. settings
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
<h3><?php __("Admin Control Panel"); ?></h3>
<hr id="topbar"/>
<br />
<?php
echo $msg->msg_html_all();
?>
<ul id="adm_panel">
 <li class="lst"><a href="adm_tld.php"><?php __("Manage allowed domains (TLD)"); ?></a></li>
 <li class="lst"><a href="adm_passpolicy.php"><?php __("Password Policies"); ?></a></li>
 <li class="lst"><a href="adm_doms.php"><?php __("Manage installed domains"); ?></a></li>
 <li class="lst"><a href="adm_defquotas.php"><?php __("Change the default quotas"); ?></a></li>
 <li class="lst"><a href="adm_authip_whitelist.php"><?php __("Manage IP whitelist"); ?></a></li>
 <li class="lst"><a href="adm_email.php"><?php __("Send an email to all members"); ?></a></li>
 <li class="lst"><a href="adm_checkhttps.php.php"><?php __("Check HTTPs redirect for Vhosts"); ?></a></li>
</ul>


 <h3><?php __("Advanced features"); ?></h3>

<ul id="adm_panel_root">
 <li class="lst"><a href="adm_slavedns.php"><?php __("Manage slave DNS"); ?></a></li>
 <li class="lst"><a href="adm_mxaccount.php"><?php __("Manage allowed accounts for secondary mx"); ?></a></li>
 <li class="lst"><a href="adm_variables.php"><?php __("Configure AlternC variables"); ?></a></li>
 <li class="lst"><a href="adm_doms_def_type.php"><?php __("Manage defaults domains type"); ?></a></li>
 <li class="lst"><a href="adm_domstype.php"><?php __("Manage domains type"); ?></a></li>
 <li class="lst"><a href="adm_dnsweberror.php"><?php __("DNS and website having errors"); ?></a></li>
 <li class="lst"><a href="adm_db_servers.php"><?php __("Manage databases servers"); ?></a></li>
<!--  <li class="lst2"><a href="stats_members.php"><?php __("Account creation statistics"); ?></a></li> -->

<?php

// here we include any "adminmenu_*" file content
$d=opendir(".");
if ($d) {
  $lst=2;
  while ($c=readdir($d)) {
    if (substr($c,0,10)=="adminmenu_") {
      echo "<li class=\"lst$lst\">";
      include($c);
      echo "</li>\n";
      $lst=3-$lst;
    }
  }
}

closedir($d);
?>
</ul>

<?php if ($cuid == 2000) { ?>
  <p class="alert alert-info">
    <?php if (panel_islocked()) { ?>
      <a href="adm_lockpanel.php?action=unlock"><?php __("Click here to unlock the panel and allow user to login.");?></a>
    <?php } else { ?>
      <a href="adm_lockpanel.php?action=lock" onClick='return confirm("<?php echo addslashes(_("Are you sure you want to kick everyone?"));?>");' ><?php __("Click here to lock the panel and force logout of all the user.");?></a>
    <?php } ?>
  </p>
<?php } //cuid 2000 ?>

<?php include_once("foot.php"); ?>
