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
<?php __("This page shows the space and service count of your AlternC server and each AlternC accounts.");
echo "<br /><br />"; printf(_("If you want to manage them, go to")."&nbsp;<a href=\"adm_list.php\">"._("Administration -> Manage the Alternc accounts")."</a>"); ?>
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
        $membres_list = $admin->get_list(0, $cuid);
        foreach ($membres_list as $n) {
            $domList = $dom->enum_domains($n["uid"]);
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

		$tmptotal=$totalweb+$totallist+$totalmail+($totaldb/1024);
		$totaltotal=$quota->get_size_unit($tmptotal);
	}
	else
	{
        $tmptotalweb = $quota->get_size_web_sum_all();          // In KB
	$totalweb=$quota->get_size_unit($tmptotalweb * 1024);

        $tmptotalmail = $quota->get_size_mail_sum_all();        // In B
        $totalmail=$quota->get_size_unit($tmptotalmail);

        $tmptotallist = $quota->get_size_mailman_sum_all();     // IN KB
        $totallist=$quota->get_size_unit($tmptotallist * 1024);
 
        $tmptotaldb = $quota->get_size_db_sum_all();            // IN B
        $totaldb=$quota->get_size_unit($tmptotaldb);

	$tmptotaltotal=($tmptotalweb*1024)+($tmptotallist*1024)+$tmptotalmail+($tmptotaldb/1024); // IN B
	$totaltotal=$quota->get_size_unit($tmptotaltotal); 

        $dc = $dom->count_domains_all();
        $mc = $quota->get_size_mail_count_all();
        $mlc = $quota->get_size_mailman_count_all();
        $dbc = $quota->get_size_db_count_all();
	}

?>
<center>
<div>
<table class="tedit" width="100%">
<thead>
   <tr><th>&nbsp;</th><th><?php __("Count"); ?></th><th><?php __("Space"); ?></th></tr>
</thead>
<tbody>
<tr>
  <th><?php __("Domains"); ?></th>
 <td><?php echo $dc; ?></td>
 <td><?php echo sprintf("%.1f", $totalweb['size'])."&nbsp;".$totalweb['unit']; ?></td>
</tr>
<tr>
 <th><?php __("Email addresses"); ?></th>
 <td><?php echo $mc; ?></td>
 <td><?php echo sprintf("%.1f", $totalmail['size'])."&nbsp;".$totalmail['unit']; ?></td>
</tr>
<?php if ($mlc) { ?>
<tr>
 <th><?php __("Mailman lists"); ?></th>
 <td><?php echo $mlc; ?></td>
 <td><?php echo sprintf("%.1f", $totallist['size'])."&nbsp;".$totallist['unit']; ?></td>
</tr>
							      <?php } ?>
<tr>
 <th><?php __("MySQL Databases"); ?></th>
 <td><?php echo $dbc; ?></td>
 <td><?php echo sprintf("%.1f", $totaldb['size'])."&nbsp;".$totaldb['unit']; ?></td>
</tr>
<tr>
 <th colspan="2"><?php __("Total"); ?></th>
 <td><?php echo sprintf("%.1f", $totaltotal['size'])."&nbsp;".$totaltotal['unit']; ?></td>
</tr>
</tbody>
</table>
</div>
</center>
<?php } elseif ($usr==0) {
  // Mode : affichage de tous les comptes
?>
<center>

<div>
<table  class="tedit" width="100%">
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
    $membres_list = $admin->get_list(0, $cuid);
    foreach ($membres_list as $minfo) {
        $domList = $dom->enum_domains($minfo['uid']);
		$mList[$muid] = array (
			"login"    => $minfo['login'],
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

}
else
{
    $totalweb = $quota->get_size_web_sum_all();
    $totalmail = $quota->get_size_mail_sum_all();
    $totallist = $quota->get_size_mailman_sum_all();
    $totaldb = $quota->get_size_db_sum_all();
}

$totaltotal=$totalweb+$totallist+($totalmail/1024)+($totaldb/1024); // In KB
if ($totaltotal==0) $totaltotal=1;

if ($cuid != 2000) {
    $membres_list = $admin->get_list(0, $cuid);
} else {
    $membres_list = $admin->get_list(1);
}

foreach ($membres_list as $c) {

  echo "<tr><td>";

  // On affiche le compte et ses domaines :
  echo "<b><a href=\"quotas_users.php?mode=".$mode."&sd=".$sd."&usr=".$c["uid"]."\">".$c["login"]."</a></b><br />\n";
  $domaines_list = $dom->enum_domains($c["uid"]);
  $dc=0; // Domain Count
  $ms=0; // Mail Space
  $mls=0;
  foreach ($domaines_list as $d) {
    if ($sd)     echo "&nbsp;&nbsp;&nbsp;-&nbsp;{$d}<br />\n";
    $dc++;
    $mstmp = $quota->get_size_mail_sum_domain($d);
    $ms+=$mstmp;
    $mlstmp = $quota->get_size_mailman_sum_domain($d);
    $mls+=$mlstmp;
  }

  $mailsize=$quota->get_size_unit($ms);

  if($mls !=  0)
    $mailmansize=$quota->get_size_unit($mls);
  else
    $mailmansize=$quota->get_size_unit($quota->get_size_mailman_sum_user($c["uid"]) * 1024);

  // Mail Count
  $maildomains_list = $mail->enum_domains($c["uid"]);
  $mc = 0;
  foreach ($maildomains_list as $md) {
    $mc += $md['nb_mail'];
  }

  // Mailman List Count
  if (isset($mailman)) {
    $mlc = $mailman->count_ml_user($c["uid"]);
    echo "</td><td>$dc</td><td>$mc</td><td>$mlc</td><td";
    if ($mode!=2) echo " style=\"text-align: right\"";
    echo ">";
  }

  // Espace WEB
  $ws = $quota->get_size_web_sum_user($c["uid"]);
  $webspace=$quota->get_size_unit($ws * 1024);
	if (isset($totalweb) && $totalweb){
		$pc=intval(100*$ws/$totalweb);
	}
	else{
		$pc=0;
	}

if ($mode==0) {
  echo sprintf("%.1f", $webspace['size'])."&nbsp;".$webspace['unit'];
} elseif ($mode==1) {
  echo sprintf("%.1f",$pc)."&nbsp;%";
} else {
  #echo "<img src=\"images/hippo_bleue.gif\" style=\"width: ".(1*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."\"/>";
  $quota->quota_displaybar($pc);
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
  echo sprintf("%.1f", $mailsize['size'])."&nbsp;".$mailsize['unit'];
} elseif ($mode==1) {
  echo sprintf("%.1f",$pc)."&nbsp;%";
} else {
  #echo "<img src=\"images/hippo_bleue.gif\" style=\"width: ".(1*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."%\"/>";
  $quota->quota_displaybar($pc);
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
  echo sprintf("%.1f", $mailmansize['size'])."&nbsp;".$mailmansize['unit'];
} elseif ($mode==1) {
  echo sprintf("%.1f",$pc)."&nbsp;%";
} else {
  #echo "<img src=\"images/hippo_bleue.gif\" style=\"width: ".(1*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."%\"/>";
  $quota->quota_displaybar($pc);
}

echo "</td><td";
if ($mode!=2) echo " style=\"text-align: right\"";
echo ">";

// Espace DB :
$ds = $quota->get_size_db_sum_user($c["login"]);
$dbsize=$quota->get_size_unit($ds);

if ($totaldb)
	$pc=intval(100*$ds/$totaldb);
else
	$pc=0;

if ($mode==0) {
  echo sprintf("%.1f", $dbsize['size'])."&nbsp;".$dbsize['unit'];
} elseif ($mode==1) {
	echo sprintf("%.1f",$pc)."&nbsp;%";
} else {
	#echo "<img src=\"images/hippo_bleue.gif\" style=\"width: ".(1*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."%\"/>";
        $quota->quota_displaybar($pc);
}

echo "</td><td";
if ($mode!=2) echo " style=\"text-align: right\"";
echo ">";

$ts=$ds/1024+$ws+$ms/1024+$mls;                  // In KB
$totalsize=$quota->get_size_unit($ts * 1024);
if ($mode==0) {
  echo sprintf("%.1f", $totalsize['size'])."&nbsp;".$totalsize['unit'];
} elseif ($mode==1) {
	echo sprintf("%.1f",(100*$ts/$totaltotal))."&nbsp;%";
} else {
  if ($totaltotal) { 
	$pc=intval(100*$ts/$totaltotal);
  } else {
    $pc=0;
  }
	#echo "<img src=\"images/hippo_bleue.gif\" style=\"width: ".(1*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."%\"/>";
	$quota->quota_displaybar($pc);
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

    $oneuser_ok = false;
	if ($cuid != 2000) {
        $c = $admin->get($usr);
        $mcreator = $admin->get_creator($c['uid']);
        if ($mcreator['uid'] == $cuid) {
            $oneuser_ok = true;
        }
	} else {
        $c = $admin->get($usr);
        if ($c != false) {
            $oneuser_ok = true;
        }
	}

    if ($oneuser_ok) {  # quotas_oneuser.php will used prefilled $c
	  define("QUOTASONE","1");
	  require_once("quotas_oneuser.php");
    }

    } // endif un seul compte
?>
<?php include_once("foot.php"); ?>
