<?php

require_once("../class/config.php");

$mode=intval($_REQUEST["mode"]);
$sd=intval($_REQUEST["sd"]);
$usr=intval($_REQUEST["usr"]);

if (!$admin->enabled) {
  __("This page is restricted to authorized staff");
  exit();
}

include_once ("head.php");

?>
<h3>Tableau de bord</h3>
<?php
if ($error) {
  echo "<p class=\"error\">$error</p>";
}
?>
<p>
Cette page résume les informations d`hébergement des comptes AlternC<br />
Les tailles sont exprimées en <?php if ($mode==0 || $mode==4) echo "Mo."; else echo "% du total"; ?>
</p>
<p>
<a href="quotas_users.php?mode=4">Global</a><br /><br />
Détail:
<?php if ($mode==0) { ?>
<a href="quotas_users.php?mode=1&amp;sd=<?php echo $sd; ?>&amp;usr=<?php echo $usr; ?>">En pourcentage</a>
<a href="quotas_users.php?mode=2&amp;sd=<?php echo $sd; ?>&amp;usr=<?php echo $usr; ?>">En graphique</a>
   <?php } elseif ($mode==1) { ?>
<a href="quotas_users.php?mode=0&amp;sd=<?php echo $sd; ?>&amp;usr=<?php echo $usr; ?>">En Mo</a>
<a href="quotas_users.php?mode=2&amp;sd=<?php echo $sd; ?>&amp;usr=<?php echo $usr; ?>">En graphique</a>
   <?php } else { ?>
<a href="quotas_users.php?mode=0&amp;sd=<?php echo $sd; ?>&amp;usr=<?php echo $usr; ?>">En Mo</a>
<a href="quotas_users.php?mode=1&amp;sd=<?php echo $sd; ?>&amp;usr=<?php echo $usr; ?>">En pourcentage</a>
 <?php } ?>

<?php if ($mode != 4) { ?>
<?php if ($usr==0) { if ($sd==0) { ?>
<a href="quotas_users.php?mode=<?php echo $mode; ?>&amp;sd=1&amp;usr=<?php echo $usr; ?>">Afficher les domaines</a>
   <?php } else { ?>
<a href="quotas_users.php?mode=<?php echo $mode; ?>&amp;sd=0&amp;usr=<?php echo $usr; ?>">Cacher les domaines</a>
 <?php } } ?>
<?php if ($usr) { ?>
<a href="quotas_users.php?mode=<?php echo $mode; ?>&amp;sd=<? echo $sd; ?>">Tous les comptes</a>
<?php } ?>
<?php } ?>
</p>

<?php if ($mode == 4) {
	// Mode : affichage des données globales

	if ($cuid != 2000)
	{
		$mList = array();
		$res = mysql_query("SELECT * FROM membres WHERE creator = '" . $cuid . "'");
		while ($n = @mysql_fetch_array($res))
		{
			$domList = array();
			$res2 = mysql_query("SELECT * FROM domaines WHERE compte = '" . $n["uid"] . "'");
			while ($n2 = @mysql_fetch_array($res2))
			{
				$domList[] = $n2["domaine"];
			}
			$mList[$n["uid"]] = array (
				"login"    => $n["login"],
				"domaines" => $domList,
			);
		}

		$totalweb = 0; $totalmail = 0; $totallist = 0; $totaldb = 0;
		$dc = 0; $mc = 0; $mlc = 0; $dbc = 0;

		foreach ($mList as $mUID => $mData)
		{
			list($tmpweb) = @mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_web WHERE uid = '" . $mUID . "'"));
			$totalweb += $tmpweb;

			if (!empty($mData["domaines"]))
			{
				foreach ($mData["domaines"] as $domaine)
				{
					$dc++;

					list($tmpmail) = @mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_mail WHERE alias LIKE '%\_" . $domaine . "'"));
					$totalmail += $tmpmail;
					list($mc) = @mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM size_mail WHERE alias LIKE '%\_" . $domaine . "'"));

					list($tmplist) = @mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_mailman WHERE list LIKE '%@" . $domaine . "'"));
					$totallist += $tmplist;
				}
			}

			list($mlc) = @mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM size_mailman WHERE uid = '" . $mUID . "'"));
			list($tmpdb) = @mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_db WHERE db = '" . $mData["login"] . "' OR db LIKE '" . $mData["login"] . "\_%'"));
			$totaldb += $tmpdb;
			list($dbc) = @mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM size_db WHERE db = '" . $mData["login"] . "' OR db LIKE '" . $mData["login"] . "\_%'"));
		}

		$totaltotal=$totalweb+$totallist+$totalmail+($totaldb/1024); // en Ko
	}
	else
	{
		list($totalweb)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_web;"));
		list($totalmail)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_mail;"));
		list($totallist)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_mailman;"));
		list($totaldb)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_db;"));

		$totaltotal=$totalweb+$totallist+$totalmail+($totaldb/1024); // en Ko

		list($dc)=@mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM domaines;"));
		list($mc)=@mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM size_mail;"));
		list($mlc)=@mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM size_mailman;"));
		list($dbc)=@mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM size_db;"));
	}

?>
<center>
<div style="width: 650px">
<table cellspacing="0" cellpadding="4" border="1" width="550" style="border-collapse: collapse">
<thead>
<tr><th>&nbsp;</th><th>Nombre</th><th>Espace</th></tr>
</thead>
<tbody>
<tr>
<td>Domaines</td>
<td><?php echo $dc; ?></td>
<td><?php echo sprintf("%.1f", $totalweb / 1024); ?>&nbsp;Mo</td>
</tr>
<tr>
<td>Mails</td>
<td><?php echo $mc; ?></td>
<td><?php echo sprintf("%.1f", $totalmail / 1024); ?>&nbsp;Mo</td>
</tr>
<tr>
<td>Listes</td>
<td><?php echo $mlc; ?></td>
<td><?php echo sprintf("%.1f", $totallist / 1024); ?>&nbsp;Mo</td>
</tr>
<tr>
<td>Bases</td>
<td><?php echo $dbc; ?></td>
<td><?php echo sprintf("%.1f", $totaldb / 1024 / 1024); ?>&nbsp;Mo</td>
</tr>
<tr>
<td colspan="2" style="text-align: right;">Total</td>
<td><?php echo sprintf("%.1f", $totaltotal / 1024); ?>&nbsp;Mo</td>
</tr>
</tbody>
</table>
</div>
</center>
<?php } elseif ($usr==0) {
  // Mode : affichage de tous les comptes
?>
<center>

<div style="width: 650px">
<table cellspacing="0" cellpadding="4" border="1" width="550" style="border-collapse: collapse">
<thead>
<tr><th rowspan="2">Compte</th><th colspan="3">Nombre de</th><th colspan="5">Espace</th></tr>
<tr>
    <th>Dom</th>
    <th>Mails</th>
    <th>Listes</th>
    <th>Web</th>
    <th>Mail</th>
    <th>Listes</th>
    <th>Bases</th>
    <th>Total</th>
</tr>
</thead>
<tbody>
<?php

if ($cuid != 2000)
{
	$mList = array();
	$res = mysql_query("SELECT * FROM membres WHERE creator = '" . $cuid . "'");
	while ($n = @mysql_fetch_array($res))
	{
		$domList = array();
		$res2 = mysql_query("SELECT * FROM domaines WHERE compte = '" . $n["uid"] . "'");
		while ($n2 = @mysql_fetch_array($res2))
		{
			$domList[] = $n2["domaine"];
		}
		$mList[$n["uid"]] = array (
			"login"    => $n["login"],
			"domaines" => $domList,
		);
	}

	$totalweb = 0; $totalmail = 0; $totallist = 0; $totaldb = 0;
	$dc = 0; $mc = 0; $mlc = 0; $dbc = 0;

	foreach ($mList as $mUID => $mData)
	{
		list($tmpweb) = @mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_web WHERE uid = '" . $mUID . "'"));
		$totalweb += $tmpweb;

		if (!empty($mData["domaines"]))
		{
			foreach ($mData["domaines"] as $domaine)
			{
				$dc++;

				list($tmpmail) = @mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_mail WHERE alias LIKE '%\_" . $domaine . "'"));
				$totalmail += $tmpmail;
				list($mc) = @mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM size_mail WHERE alias LIKE '%\_" . $domaine . "'"));

				list($tmplist) = @mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_mailman WHERE list LIKE '%@" . $domaine . "'"));
				$totallist += $tmplist;
			}
		}

		list($mlc) = @mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM size_mailman WHERE uid = '" . $mUID . "'"));
		list($tmpdb) = @mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_db WHERE db = '" . $mData["login"] . "' OR db LIKE '" . $mData["login"] . "\_%'"));
		$totaldb += $tmpdb;
		list($dbc) = @mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM size_db WHERE db = '" . $mData["login"] . "' OR db LIKE '" . $mData["login"] . "\_%'"));
	}

}
else
{
	list($totalweb)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_web;"));
	list($totalmail)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_mail;"));
	list($totallist)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_mailman;"));
	list($totaldb)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_db;"));
}

$totaltotal=$totalweb+$totallist+$totalmail+($totaldb/1024); // en Ko

if ($cuid != 2000)
{
	$r = mysql_query("SELECT * FROM membres WHERE creator = '" . $cuid . "' ORDER BY login;");
}
else
{
	$r=mysql_query("SELECT * FROM membres ORDER BY login;");
}

while ($c=mysql_fetch_array($r)) {

  echo "<tr><td>";

  // On affiche le compte et ses domaines :
  echo "<b><a href=\"quotas_users.php?mode=".$mode."&sd=".$sd."&usr=".$c["uid"]."\">".$c["login"]."</a></b><br />\n";
  $s=mysql_query("SELECT * FROM domaines WHERE compte='".$c["uid"]."';");
  $dc=0; // Domain Count
  $ms=0; // Mail Space
	$mls=0;
  while ($d=mysql_fetch_array($s)) {
if ($sd)     echo "&nbsp;&nbsp;&nbsp;-&nbsp;".$d["domaine"]."<br />\n";
    $dc++;
    list($mstmp)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_mail WHERE alias LIKE '%\_".$d["domaine"]."';"));
    $ms+=$mstmp;
    list($mlstmp)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_mailman WHERE list LIKE '%@".$d["domaine"]."';"));
    $mls+=$mlstmp;
  }

  // Mail Count
  list($mc)=@mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM mail_domain WHERE type=0 AND uid='".$c["uid"]."';"));
  // Mailman List Count
  list($mlc)=@mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM mailman WHERE uid='".$c["uid"]."';"));
  echo "</td><td>$dc</td><td>$mc</td><td>$mlc</td><td";
  if ($mode!=2) echo " style=\"text-align: right\"";
  echo ">";

  // Espace WEB
  list($ws)=@mysql_fetch_array(mysql_query("SELECT size FROM size_web WHERE uid='".$c["uid"]."';"));

	if ($totalweb)
		$pc=intval(100*$ws/$totalweb);
	else
		$pc=0;

if ($mode==0) {
  echo sprintf("%.1f", $ws / 1024)."&nbsp;Mo";
} elseif ($mode==1) {
  echo sprintf("%.1f",$pc)."&nbsp;%";
} else {
  echo "<img src=\"hippo_bleue.gif\" style=\"width: ".(1*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."\"/>";
}
  echo "</td><td";
  if ($mode!=2) echo " style=\"text-align: right\"";
  echo ">";

  // Espace Mail :

if ($totalmail)
	$pc=intval(100*$ms/$totalmail);
else
	$pc=0;

if ($mode==0) {
  echo sprintf("%.1f", $ms / 1024)."&nbsp;Mo";
} elseif ($mode==1) {
  echo sprintf("%.1f",$pc)."&nbsp;%";
} else {
  echo "<img src=\"hippo_bleue.gif\" style=\"width: ".(1*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."%\"/>";
}

  echo "</td><td";
  if ($mode!=2) echo " style=\"text-align: right\"";
  echo ">";

  // Espace Mailman :
if ($totallist)
	$pc=intval(100*$mls/$totallist);
else
	$pc=0;

if ($mode==0) {
  echo sprintf("%.1f", $mls / 1024)."&nbsp;Mo";
} elseif ($mode==1) {
  echo sprintf("%.1f",$pc)."&nbsp;%";
} else {
  echo "<img src=\"hippo_bleue.gif\" style=\"width: ".(1*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."%\"/>";
}

echo "</td><td";
if ($mode!=2) echo " style=\"text-align: right\"";
echo ">";

// Espace DB :
list($ds)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_db WHERE db='".$c["login"]."' OR db LIKE '".$c["login"]."\_%';"));

if ($totaldb)
	$pc=intval(100*$ds/$totaldb);
else
	$pc=0;

if ($mode==0) {
	echo sprintf("%.1f", $ds / 1024/1024)."&nbsp;Mo";
} elseif ($mode==1) {
	echo sprintf("%.1f",$pc)."&nbsp;%";
} else {
	echo "<img src=\"hippo_bleue.gif\" style=\"width: ".(1*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."%\"/>";
}

echo "</td><td";
if ($mode!=2) echo " style=\"text-align: right\"";
echo ">";

$ts=$ds/1024+$ws+$ms+$mls;
if ($mode==0) {
	echo sprintf("%.1f", $ts/1024)."&nbsp;Mo";
} elseif ($mode==1) {
	echo sprintf("%.1f",(100*$ts/$totaltotal))."&nbsp;%";
} else {
	$pc=intval(100*$ts/$totaltotal);
	echo "<img src=\"hippo_bleue.gif\" style=\"width: ".(1*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."%\"/>";
}


echo "</td>";

echo "</tr>";

}

?>
</tbody>


</table>
</div>
</center>
<?php
    } else { // Mode affichage d'UN seul compte

	if ($cuid != 2000)
	{
		$c=@mysql_fetch_array(mysql_query("SELECT * FROM membres WHERE uid='".$usr."' AND creator = '" . $cuid . "';"));
	}
	else
	{
		$c=@mysql_fetch_array(mysql_query("SELECT * FROM membres WHERE uid='".$usr."';"));
	}

	if (!empty($c))
	{
?>

<!-- Les Mails -->
<center>
<p>Compte <span style="font-weight: bold;"><?php echo $c["login"]; ?></span></p>
<?php

	list($totalweb)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_web WHERE uid = '" . $c["uid"] . "'"));

	echo "<p>Espace WEB: ";
	echo sprintf("%.1f", $totalweb / 1024)."&nbsp;Mo";
	echo "</p>";

?>
<div style="width: 550px">
<table cellspacing="0" cellpadding="4" border="1" width="550" style="border-collapse: collapse">
<thead>
<tr>
    <th>Domaine</th>
    <th>Mail</th>
    <th>Espace</th>
</tr>
</thead>
<tbody>
<?php


  $s=mysql_query("SELECT * FROM domaines WHERE compte='".$c["uid"]."';");
  $totalmail=0;
  while ($d=mysql_fetch_array($s)) {
    list($mstmp)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_mail WHERE alias LIKE '%\_".$d["domaine"]."';"));
    $totalmail+=$mstmp;
  }

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
</tbody>
</table>
    <p>&nbsp;</p>

<table cellspacing="0" cellpadding="4" border="1" width="550" style="border-collapse: collapse">
<thead>
<tr>
    <th>DB</th>
    <th>Espace</th>
</tr>
</thead>
<tbody>
<?php

    // Espace DB :
    list($totaldb)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_db WHERE db='".$c["login"]."' OR db LIKE '".$c["login"]."\_%';"));
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
</tbody>
</table>

<p>&nbsp;</p>

<table cellspacing="0" cellpadding="4" border="1" width="550" style="border-collapse: collapse">
<thead>
<tr>
    <th>Liste</th>
    <th>Espace</th>
</tr>
</thead>
<tbody>
<?php

    // Espace Liste :
    list($totallist)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_mailman WHERE uid='".$c["uid"]."'"));
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
</tbody>
</table>

</div>
</center>
<?php } ?>
<?php
    }
?>
<?php include_once("foot.php"); ?>
