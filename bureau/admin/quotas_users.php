<?php

require_once("../class/config.php");

$fields = array (
	"mode"   => array ("get", "integer" ,0), 
	"sd"     => array ("get", "integer" ,0), 
	"usr"    => array ("get", "integer" ,0), 
);
getFields($fields);

if (!$admin->enabled) {
  __("This page is restricted to authorized staff");
  exit();
}

include_once ("head.php");

?>
<h3><?php __("Quotas status"); ?></h3>
<hr id="topbar"/>
<br />
<?php
if (isset($error) && $error) {
  echo "<p class=\"error\">$error</p>";
}
?>
<p>
<?php __("This page shows the space and service count of your AlternC server and each AlternC accounts."); ?>
</p>
<p>
<?php printf(_("Sizes are shown as %s"),($mode==0 || $mode==4)?_("MB."):_("% of the total.")); ?>
</p>
<p>
<?php __("Server-side view:"); ?> <span class="ina <?php if ($mode==4) { echo 'ina-active'; } ?>"><a href="quotas_users.php?mode=4"><?php __("Global"); ?></a></span><br /><br />
<?php __("Detailed view:"); ?>
  <span class="ina <?php if ($mode==0) { echo 'ina-active'; } ?>"><a href="quotas_users.php?mode=0&amp;sd=<?php echo $sd; ?>&amp;usr=<?php echo $usr; ?>"><?php __("In MB"); ?></a></span>
  <span class="ina <?php if ($mode==1) { echo 'ina-active'; } ?>"><a href="quotas_users.php?mode=1&amp;sd=<?php echo $sd; ?>&amp;usr=<?php echo $usr; ?>"><?php __("Percentage"); ?></a></span>
  <span class="ina <?php if ($mode==2) { echo 'ina-active'; } ?>"><a href="quotas_users.php?mode=2&amp;sd=<?php echo $sd; ?>&amp;usr=<?php echo $usr; ?>"><?php __("Graphical"); ?></a></span>
<?php if ($mode != 4) { ?>
<?php if ($usr==0) { if ($sd==0) { ?>
      <span class="ina"><a href="quotas_users.php?mode=<?php echo $mode; ?>&amp;sd=1&amp;usr=<?php echo $usr; ?>"><?php __("Show the domain names"); ?></a></span>
   <?php } else { ?>
      <span class="ina"><a href="quotas_users.php?mode=<?php echo $mode; ?>&amp;sd=0&amp;usr=<?php echo $usr; ?>"><?php __("Hide the domain names"); ?></a></span>
 <?php } } ?>
<?php if ($usr) { ?>
    <span class="ina"><a href="quotas_users.php?mode=<?php echo $mode; ?>&amp;sd=<?php echo $sd; ?>"><?php __("All accounts"); ?></a></span>
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
            $tmpweb = $quota->get_size_web_sum_user($mUID);
			$totalweb += $tmpweb;

			if (!empty($mData["domaines"]))
			{
				foreach ($mData["domaines"] as $domaine)
				{
					$dc++;

					$tmpmail = $quota->get_size_mail_sum_domain($domaine);
					$totalmail += $tmpmail;
					$mc = $quota->get_size_mail_count_domain($domaine);

					$tmplist = $quota->get_size_mailman_sum_domain($domaine);
					$totallist += $tmplist;
				}
			}

            $mlc = $quota->get_size_mailman_count_user($mUID);
            $tmpdb = $quota->get_size_db_sum_user($mData["login"]);
			$totaldb += $tmpdb;
            $dbc = $quota->get_size_db_count_user($mData["login"]);
		}

		$totaltotal=$totalweb+$totallist+$totalmail+($totaldb/1024); // en Ko
	}
	else
	{
        $totalweb = $quota->get_size_web_sum_all();
        $totalmail = $quota->get_size_mail_sum_all();
        $totallist = $quota->get_size_mailman_sum_all();
        $totaldb = $quota->get_size_db_sum_all();

		$totaltotal=$totalweb+$totallist+$totalmail+($totaldb/1024); // en Ko

		list($dc)=@mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM domaines;"));
        $mc = $quota->get_size_mail_count_all();
        $mlc = $quota->get_size_mailman_count_all();
        $dbc = $quota->get_size_db_count_all();
	}

?>
<center>
<div style="width: 650px">
<table class="tedit">
<thead>
   <tr><th>&nbsp;</th><th><?php __("Count"); ?></th><th><?php __("Space"); ?></th></tr>
</thead>
<tbody>
<tr>
  <th><?php __("Domains"); ?></th>
 <td><?php echo $dc; ?></td>
 <td><?php echo sprintf("%.1f", $totalweb / 1024); ?>&nbsp;Mo</td>
</tr>
<tr>
 <th><?php __("Email addresses"); ?></th>
 <td><?php echo $mc; ?></td>
 <td><?php echo sprintf("%.1f", $totalmail / 1024); ?>&nbsp;Mo</td>
</tr>
<?php if ($mlc) { ?>
<tr>
 <th><?php __("Mailman lists"); ?></th>
 <td><?php echo $mlc; ?></td>
 <td><?php echo sprintf("%.1f", $totallist / 1024); ?>&nbsp;Mo</td>
</tr>
							      <?php } ?>
<tr>
 <th><?php __("MySQL Databases"); ?></th>
 <td><?php echo $dbc; ?></td>
 <td><?php echo sprintf("%.1f", $totaldb / 1024 / 1024); ?>&nbsp;Mo</td>
</tr>
<tr>
 <th colspan="2"><?php __("Total"); ?></th>
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
<table  class="tedit">
<thead>
	    <tr><th rowspan="2"><?php __("Account"); ?></th><th colspan="3"><?php __("Count"); ?></th><th colspan="5"><?php __("Space"); ?></th></tr>
<tr>
  <th><?php __("Dom"); ?></th>
  <th><?php __("Mails"); ?></th>
  <th><?php __("Lists"); ?></th>
  <th><?php __("Web");  ?></th>
  <th><?php __("Mails"); ?></th>
  <th><?php __("Lists"); ?></th>
  <th><?php __("DB"); ?></th>
  <th><?php __("Total"); ?></th>
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
        $tmpweb = $quota->get_size_web_sum_user($mUID);
		$totalweb += $tmpweb;

		if (!empty($mData["domaines"]))
		{
			foreach ($mData["domaines"] as $domaine)
			{
				$dc++;

                $tmpmail = $quota->get_size_mail_sum_domain($domaine);
				$totalmail += $tmpmail;
                $mc = $quota->get_size_mail_count_domain($domaine);

                $tmplist = $quota->get_size_mailman_sum_domain($domaine);
				$totallist += $tmplist;
			}
		}

        $mlc = $quota->get_size_mailman_count_domain($mUID);
        $tmpdb = $quota->get_size_db_sum_user($mData["login"]);
		$totaldb += $tmpdb;
        $dbc = $quota->get_size_db_count_user($mData["login"]);
	}

}
else
{
    $totalweb = $quota->get_size_web_sum_all();
    $totalmail = $quota->get_size_mail_sum_all();
    $totallist = $quota->get_size_mailman_sum_all();
    $totaldb = $quota->get_size_db_sum_all();
}

$totaltotal=$totalweb+$totallist+$totalmail+($totaldb/1024); // en Ko
if ($totaltotal==0) $totaltotal=1;

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
    $mstmp = $quota->get_size_mail_sum_domain($d["domaine"]);
    $ms+=$mstmp;
    $mlstmp = $quota->get_size_mailman_sum_domain($d["domaine"]);
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
  $ws = $quota->get_size_web_sum_user($c["uid"]);

	if (isset($totalweb) && $totalweb){
		$pc=intval(100*$ws/$totalweb);
	}
	else{
		$pc=0;
	}

if ($mode==0) {
  echo sprintf("%.1f", $ws / 1024)."&nbsp;"._("MB");
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
  echo sprintf("%.1f", $mls / 1024)."&nbsp;"._("MB");
} elseif ($mode==1) {
  echo sprintf("%.1f",$pc)."&nbsp;%";
} else {
  echo "<img src=\"hippo_bleue.gif\" style=\"width: ".(1*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."%\"/>";
}

echo "</td><td";
if ($mode!=2) echo " style=\"text-align: right\"";
echo ">";

// Espace DB :
$ds = $quota->get_size_db_sum_user($c["login"]);

if ($totaldb)
	$pc=intval(100*$ds/$totaldb);
else
	$pc=0;

if ($mode==0) {
  echo sprintf("%.1f", $ds / 1024/1024)."&nbsp;"._("MB");
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
  echo sprintf("%.1f", $ts/1024)."&nbsp;"._("MB");
} elseif ($mode==1) {
	echo sprintf("%.1f",(100*$ts/$totaltotal))."&nbsp;%";
} else {
  if ($totaltotal) { 
	$pc=intval(100*$ts/$totaltotal);
  } else {
    $pc=0;
  }
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

	  define("QUOTASONE","1");
	  require_once("quotas_oneuser.php");

 } ?>
<?php
    }
?>
<?php include_once("foot.php"); ?>
