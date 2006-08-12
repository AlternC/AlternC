<?php 

require_once('../class/config.php');

$uid = $_GET['uid'];
if (!$uid) {
	__("Missing uid");
	exit();
}
if (!$admin->enabled || !$admin->checkcreator($uid)) {
        __("This page is restricted to authorized staff");
	exit();
}

if (!$r=$admin->get($uid)) {
	__("User does not exist");
	exit();
}

if (! ($confirmed = ($_GET['submit'] == _("Confirm")) ) ) {
  print '<h2>' . _('WARNING: experimental feature, use at your own risk') . '</h2>';
  __("The following domains will be deactivated and redirected to the URL entered in the following box. A backup of the domain configuration will be displayed as a serie of SQL request that you can run to restore the current configuration if you want. Click confirm if you are sure you want to deactivate all this user's domains.");

  ?>
  <form action="<?=$PHP_SELF?>" method="GET">
  <input type="hidden" name="uid" value="<?=$uid?>" />
  <? __("Redirection URL:") ?> <input type="text" name="redirect" value="http://example.com/" />
  <input type="submit" name="submit" value="<?=_("Confirm")?>" />
  </form><?php

  print "<h3>" . _("Domains of user: ") . $r["login"] . "</h3>";
} else {
  if (!$_GET['redirect']) {
    __("Missing redirect url.");
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
  if (!$confirmed) print '</ul>';
}

# 3. wrap up (?)
if ($confirmed) {
  print "-- The following is a serie of SQL request you can run, as root, to revert the user's domains to their previous state.\n";
  print $backup;
  print "</pre>";
}
$cuid = $old_cuid;
