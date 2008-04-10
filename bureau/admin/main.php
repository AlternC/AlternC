<?php
/*
 $Id: main.php,v 1.3 2004/05/19 14:23:06 benjamin Exp $
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

include("head.php");
?>
</head>
<body>

<h3>Ce bureau est un lieu de développement pour arranger minimalement alterc dans l'objectif d'y faire un "theme" plus joli et personnalisable</h3>

<div id="info"><?php
// Show last login information : 
__("Last Login: ");

echo format_date('the %3$d-%2$d-%1$d at %4$d:%5$d',$mem->user["lastlogin"]);
printf("&nbsp;"._('from: <code> %1$s </code>')."<br />",$mem->user["lastip"]);

if ($mem->user["lastfail"]) {
	printf(_("%1\$d login failed since last login")."<br />",$mem->user["lastfail"]);
}

$mem->resetlast();

# use MagpieRSS to syndicate content from another site if available

# this should work, since the debian package installs it in
# /usr/share/php, which is in the include path
$rss_url = variable_get('rss_feed');
$inc = @include_once('magpierss/rss_fetch.inc');
if ($inc && $rss_url) {
  $rss = fetch_rss($rss_url);

  if ($rss) {
    echo "<h2>" . _("Latest news") . "</h2>";
    foreach ($rss->items as $item) {
      $href = $item['link'];
      $title = $item['title'];
      echo "<h3><a href=$href>$title</a></h3>";
      echo '<span class="date">'.$item['pubdate'] .'</span> - ';
      echo '<span class="author">'.$item['dc']['creator'].'</span>';
      echo $item['summary'];
    }
  }
}

if($admin->enabled) {
  $expiring = $admin->renew_get_expiring_accounts();

  if(is_array($expiring) && count($expiring) > 0) {
    echo "<h2>" . _("Expired or about to expire accounts") . "</h2>\n";
    echo "<table cellspacing=\"2\" cellpadding=\"4\">\n";
    echo "<tr><th>"._("uid")."</th><th>"._("Last name, surname")."</th><th>"._("Expiry")."</th></tr>\n";
    foreach($expiring as $account) {
      echo "<tr class=\"exp{$account['status']}\"><td>{$account['uid']}</td>";
      if($admin->checkcreator($account['uid']))
	echo "<td><a href=\"adm_edit.php?uid={$account['uid']}\">{$account['nom']}, {$account['prenom']}</a></td>";
      else
	echo "<td>{$account['nom']}, {$account['prenom']}</td>";
      echo "<td>{$account['expiry']}</td></tr>\n";
    }
    echo "</table>\n";
  }
}

?></div>


</body>
</html>
