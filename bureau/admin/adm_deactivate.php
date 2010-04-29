<?php 
/*
 $Id: adm_tld.php,v 1.4 2004/11/29 17:27:04 anonymous Exp $
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

include_once("head.php");

$uid = $_GET['uid'];
if (!$uid) {
	__("Missing uid");
	include_once("foot.php");
	exit();
}
if (!$admin->enabled || !$admin->checkcreator($uid)) {
        __("This page is restricted to authorized staff");
	include_once("foot.php");
	exit();
}

if (!$r=$admin->get($uid)) {
	__("User does not exist");
	include_once("foot.php");
	exit();
}

if (! ($confirmed = ($_GET['submit'] == _("Confirm")) ) ) {
  print '<h2>' . _('WARNING: experimental feature, use at your own risk') . '</h2>';
  __("The following domains will be deactivated and redirected to the URL entered in the following box. A backup of the domain configuration will be displayed as a serie of SQL request that you can run to restore the current configuration if you want. Click confirm if you are sure you want to deactivate all this user's domains.");

  ?>
  <form action="<?=$PHP_SELF?>" method="GET">
  <input type="hidden" name="uid" value="<?=$uid?>" />
  <? __("Redirection URL:") ?> <input type="text" name="redirect" class="int" value="http://example.com/" />
  <input type="submit" name="submit" class="inb" value="<?=_("Confirm")?>" />
  <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='adm_list.php'"/>
  </form><?php

  print "<h3>" . _("Domains of user: ") . $r["login"] . "</h3>";
} else {
  if (!$_GET['redirect']) {
    __("Missing redirect url.");
    include_once("foot.php");
    exit();
  } else {
    $redirect = $_GET['redirect'];
  }
}

# this string will contain an SQL request that will be printed at the end of the process and that can be used to reload the old domain configuration
$backup = "";

# 1. list the domains of the user
# 1.1 list the domains
global $cuid;
$old_cuid = $cuid;
$cuid = $uid;
$domains = $dom->enum_domains();

if ($confirmed) {
  print "<pre>";
  printf(_("-- Redirecting all domains and subdomains of the user %s to %s\n"), $r['login'], $redirect);
}

reset($domains);
# 1.2 foreach domain, list the subdomains
foreach ($domains as $key => $domain) {
  if (!$confirmed) print '<h4>' . $domain . '</h4><ul>';
  $dom->lock();
  if (!$r=$dom->get_domain_all($domain)) {
          $error=$err->errstr();
  }
  $dom->unlock();
  # 2. for each subdomain
  if (is_array($r['sub'])) {
    foreach ($r['sub'] as $k => $sub) {
# shortcuts
      $type = $sub['type'];
      $dest = $sub['dest'];
      $sub = $sub['name'];
# if it's a real website
      if ($type == $dom->type_local) {
	if (!$confirmed) {
	  print "<li>";
	  if ($sub) {
	    print $sub . '.';
	  }
	  print "$domain -> $dest</li>";
	} else {

# 2.1 keep a copy of where it was, in an SQL request
	  $backup .= "UPDATE `sub_domaines` SET `type`='$type', valeur='$dest' WHERE `domaine`='$domain' AND sub='$sub';\n";
	  $backup .= "DELETE FROM `sub_domaines_standby` WHERE domaine='$domain' and sub='$sub';\n";
	  $backup .= "INSERT INTO sub_domaines_standby (compte,domaine,sub,valeur,type,action) values ('$cuid','$domain','$sub','$dest','$type',1);\n"; // UPDATE
	  
# 2.2 change the subdomain to redirect to http://spam.koumbit.org/
	  $dom->lock();
	  if (!$dom->set_sub_domain($domain, $sub, $dom->type_url, "edit", $redirect)) {
	    print "-- error in $sub.$domain: " . $err->errstr() . "\n";
	  }
	  $dom->unlock();
	}
      }
    }
  }
  if (!$confirmed) print '</ul>';
}

# 3. wrap up (?)
if ($confirmed) {
  print "-- The following is a serie of SQL request you can run, as root, to revert the user's domains to their previous state.\n";
  print $backup;
  print "</pre>";
}
$cuid = $old_cuid;

include_once("foot.php");

?>

