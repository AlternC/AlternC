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

include_once("head.php");

include_once("menu.php");

// Show last login information :
echo "<p>";
__("Last Login: ");

echo format_date('the %3$d-%2$d-%1$d at %4$d:%5$d',$mem->user["lastlogin"]);
printf("&nbsp;"._('from: <code> %1$s </code>')."<br />",$mem->user["lastip"]);
echo "</p>";

if ($mem->user["lastfail"]) {
	printf(_("%1\$d login failed since last login")."<br />",$mem->user["lastfail"]);
}

$mem->resetlast();

/*
 use MagpieRSS to syndicate content from another site if available
 this should work, since the debian package installs it in
 /usr/share/php, which is in the include path
*/
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

  if(count($expiring) > 0) {
    echo "<h2>" . _("Expired or about to expire accounts") . "</h2>\n";
    echo "<table cellspacing=\"2\" cellpadding=\"4\">\n";
    echo "<tr><th>"._("uid")."</th><th>"._("Last name, surname")."</th><th>"._("Expiry")."</th></tr>\n";
  if (is_array($expiring)) {
	    foreach($expiring as $account) {
      echo "<tr class=\"exp{$account['status']}\"><td>{$account['uid']}</td>";
      if($admin->checkcreator($account['uid']))
	echo "<td><a href=\"adm_edit.php?uid={$account['uid']}\">{$account['nom']}, {$account['prenom']}</a></td>";
      else
	echo "<td>{$account['nom']}, {$account['prenom']}</td>";
      echo "<td>{$account['expiry']}</td></tr>\n";
    }
}
    echo "</table>\n";
  }
}

$c=@mysql_fetch_array(mysql_query("SELECT * FROM membres WHERE uid='".$cuid."';"));

?>
<center>
<?php

	list($totalweb)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_web WHERE uid = '" . $c["uid"] . "'"));

echo "<p>"._("WEB Space:")." ";
	echo sprintf("%.1f", $totalweb / 1024)."&nbsp;Mo";
	echo "</p>";

?>
<div style="width: 550px">
<?php

$s=mysql_query("SELECT * FROM domaines WHERE compte='".$c["uid"]."';");
$totalmail=0;
while ($d=mysql_fetch_array($s)) {
	list($mstmp)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_mail WHERE alias LIKE '%\_".$d["domaine"]."';"));
	$totalmail+=$mstmp;
}

if ($totalmail)
{

?>
<table cellspacing="0" cellpadding="4" border="1" width="550" style="border-collapse: collapse">
<tr>
    <th>Domaine</th>
    <th>Mail</th>
    <th>Espace</th>
</tr>
<?php

  $s=mysql_query("SELECT * FROM domaines WHERE compte='".$c["uid"]."';");
  while ($d=mysql_fetch_array($s)) {
    $t=mysql_query("SELECT alias,size FROM size_mail WHERE alias LIKE '%\_".$d["domaine"]."';");
    while ($e=mysql_fetch_array($t)) {
      echo "<tr><td>".$d["domaine"]."</td>";
      echo "<td>".str_replace("_","@",$e["alias"])."</td>";
      echo "<td";
      if ($mode!=2) echo " style=\"text-align: right\"";
      echo ">";
      $ms=$e["size"];
			if ($totalmail)
				$pc=intval(100*$ms/$totalmail);
			else
				$pc=0;
      if ($mode==0) {
	echo sprintf("%.1f", $ms / 1024)."&nbsp;Mo";
      } elseif ($mode==1) {
	echo sprintf("%.1f", $pc)."&nbsp;%";
      } else {
	echo "<img src=\"hippo_bleue.gif\" style=\"width: ".(2*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."\"/>";
      }
      echo "</td></tr>";
    }
  }
?>
</table>

<p>&nbsp;</p>
<?php

}

list($totaldb)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_db WHERE db='".$c["login"]."' OR db LIKE '".$c["login"]."\_%';"));

if ($totaldb)
{

?>
<table cellspacing="0" cellpadding="4" border="1" width="550" style="border-collapse: collapse">
<tr>
    <th>DB</th>
    <th>Espace</th>
</tr>
<?php

    // Espace DB :
    $s=mysql_query("SELECT db,size FROM size_db WHERE db='".$c["login"]."' OR db LIKE '".$c["login"]."\_%';");
  while ($d=mysql_fetch_array($s)) {
    echo "<tr><td>".$d["db"]."</td><td";
    if ($mode!=2) echo " style=\"text-align: right\"";
    echo ">";
    $ds=$d["size"];
		if ($totaldb)
			$pc=intval(100*$ds/$totaldb);
		else
			$pc=0;
    if ($mode==0) {
      echo sprintf("%.1f", $ds / 1024/1024)."&nbsp;Mo";
    } elseif ($mode==1) {
      echo sprintf("%.1f", $pc)."&nbsp;%";
    } else {
      echo "<img src=\"hippo_bleue.gif\" style=\"width: ".(2*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."%\"/>";
    }
    echo "</td></tr>";
  }
?>
</table>

<p>&nbsp;</p>
<?php

}

list($totallist)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_mailman WHERE uid='".$c["uid"]."'"));

if ($totallist)
{

?>
<table cellspacing="0" cellpadding="4" border="1" width="550" style="border-collapse: collapse">
<tr>
    <th>Liste</th>
    <th>Espace</th>
</tr>
<?php

    // Espace Liste :
    $s=mysql_query("SELECT list,size FROM size_mailman WHERE uid='".$c["uid"]."' ORDER BY list ASC");
  while ($d=mysql_fetch_array($s)) {
    echo "<tr><td>".$d["list"]."</td><td";
    if ($mode!=2) echo " style=\"text-align: right\"";
    echo ">";
    $ds=$d["size"];
		if ($totallist)
			$pc=intval(100*$ds/$totallist);
		else
			$pc=0;
    if ($mode==0) {
      echo sprintf("%.1f", $ds / 1024)."&nbsp;Mo";
    } elseif ($mode==1) {
      echo sprintf("%.1f", $pc)."&nbsp;%";
    } else {
      echo "<img src=\"hippo_bleue.gif\" style=\"width: ".(2*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."%\"/>";
    }
    echo "</td></tr>";
  }
?>
</table>
<?php

}

?>
</div>
</center>
<?php include_once("foot.php"); ?>
