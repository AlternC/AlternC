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
 * Page used by administrators to deactivate an account
 * and redirect its domains
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */

require_once("../class/config.php");

include_once("head.php");

if (!$admin->enabled) {
	$msg->raise("ERROR", "admin", __("This page is restricted to authorized staff", "alternc", true));
	echo $msg->msg_html_all();
	exit;
}
$fields = array (
	"uid"    		=> array ("request",  "integer", ""),
	"submit"    		=> array ("post", "string", ""),
	"redirect"    		=> array ("post", "string", ""),
);
getFields($fields);

if (!$uid) {
	$msg->raise("ERROR", "admin", __("Account not found", "alternc", true));
	echo $msg->msg_html_all();
	include_once("foot.php");
	exit();
}

if (!$admin->checkcreator($uid)) {
	$msg->raise("ERROR", "admin", __("This page is restricted to authorized staff", "alternc", true));
	echo $msg->msg_html_all();
	include_once("foot.php");
	exit();
}

if (!$r=$admin->get($uid)) {
	$msg->raise("ERROR", "admin", __("User does not exist", "alternc", true));
	echo $msg->msg_html_all();
	include_once("foot.php");
	exit();
}

$confirmed = ($submit == __("Confirm", "alternc", true))?true:false;


if (! ($confirmed ) ) {
  print '<h2>' . __('WARNING: experimental feature, use at your own risk', "alternc", true) . '</h2>';
  __("The following domains will be deactivated and redirected to the URL entered in the following box. A backup of the domain configuration will be displayed as a serie of SQL request that you can run to restore the current configuration if you want. Click confirm if you are sure you want to deactivate all this user's domains.");

  ?>
  <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="POST">
     <?php csrf_get(); ?>
  <input type="hidden" name="uid" value="<?php ehe($uid); ?>" />
  <?php __("Redirection URL:") ?> <input type="text" name="redirect" class="int" value="http://example.com/" />
  <input type="submit" name="submit" class="inb" value="<?php __("Confirm")?>" />
  <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='adm_list.php'"/>
  </form><?php

  print "<h3>" . __("Domains of user: ", "alternc", true) . $r["login"] . "</h3>";
} else {
  if (empty($redirect)) {
    $msg->raise("ERROR", "admin", __("Missing redirect url.", "alternc", true));
    echo $msg->msg_html_all();
    include_once("foot.php");
    exit();
  } 
}

// this string will contain an SQL request that will be printed at the end of the process and that can be used to reload the old domain configuration
$backup = "";

# 1. list the domains of the user
# 1.1 list the domains
global $cuid;
$old_cuid = $cuid;
$cuid = $uid;
$domains = $dom->enum_domains();

if ($confirmed) {
  print "<pre>";
  printf(__("-- Redirecting all domains and subdomains of the user %s to %s\n", "alternc", true), $r['login'], $redirect);
}

reset($domains);
# 1.2 foreach domain, list the subdomains
foreach ($domains as $key => $domain) {
  if (!$confirmed) print '<h4>' . $domain . '</h4><ul>';
  $dom->lock();
  $r=$dom->get_domain_all($domain);
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
	  $backup .= "UPDATE `sub_domaines` SET `type`='$type', valeur='$dest',web_action='UPDATE' WHERE `domaine`='$domain' AND sub='$sub';\n";
	  
# 2.2 change the subdomain to redirect to http://spam.koumbit.org/
	  $dom->lock();
	  if (!$dom->set_sub_domain($domain, $sub, $dom->type_url, "edit", $redirect)) {
          print "-- error in $sub.$domain: ";
          echo $msg->msg_html("ERROR");
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

