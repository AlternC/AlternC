<?php

require_once("../class/config.php");
if (!defined("QUOTASONE")) return;

?>
<center>

<p><h3><center><?php __("Account"); ?> <span style="font-weight: bold;"><?php echo $c["login"]; ?></span></center></h3></p>

<div style="width: 550px">

<!-- Les esapces web -->

<?php

  $totalweb = $quota->get_size_web_sum_user($c["uid"]);

  echo "<p>"._("Web Space:")." ";
  echo sprintf("%.1f", $totalweb / 1024)."&nbsp;"._("MB");
  echo "</p>";

?>

<!-- Les mails -->

<table class="tedit">
<thead>
<tr>
   <th><?php __("Domains"); ?></th>
   <th><?php __("Emails"); ?></th>
   <th><?php __("Space"); ?></th>
</tr>
</thead>
<tbody>
<?php

  $domaines_user = $dom->enum_domains($c["uid"]);
  $totalmail=0;
  foreach ($domaines_user as $domaine) {
    $mstmp = $quota->get_size_mail_sum_domain($domaine);
    $totalmail+=$mstmp;
  }

  echo "<p>"._("Mail boxes:")." ";
  echo sprintf("%.1f", $totalmail / 1024)."&nbsp;"._("MB");
  echo "</p>";

  foreach ($domaines_user as $domaine) {
    $alias_sizes = $quota->get_size_mail_details_domain($domaine);
    $domsize = 0;
    foreach ($alias_sizes as $e) {
      $domsize += $e['size'];
      echo "<tr><td>{$domaine}</td>";
      echo "<td>".str_replace("_","@",$e["alias"])."</td>";
      echo "<td"; if ($mode!=2) echo " style=\"text-align: right\""; echo ">";
      $ms=$e["size"];
      if ($totalmail) {
        $pc=intval(100*$ms/$totalmail);
      } else {
        $pc=0;
      }
      if ($mode==0) {
        echo sprintf("%.1f", $ms / 1024)."&nbsp;"._("MB");
      } elseif ($mode==1) {
        echo sprintf("%.1f", $pc)."&nbsp;%";
      } else {
        echo "<img src=\"hippo_bleue.gif\" style=\"width: ".(2*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."\"/>";
      }
      echo "</td></tr>";
    }
    $tpc = intval(100 * $domsize / $totalmail);
    echo "<tr><td><i>". _('Total'). " {$domaine}</i></td><td></td>";
    echo "<td";
    if ($mode!=2) echo " style=\"text-align: right\"";
    echo "><i>";
    if ($mode==0) {
      echo sprintf("%.1f", $domsize / 1024)."&nbsp;"._("MB");
    } elseif ($mode==1) {
      echo sprintf("%.1f", $tpc)."&nbsp;%";
    } else {
      echo "<img src=\"hippo_bleue.gif\" style=\"width: ".(2*$tpc)."px; height: 16px\" alt=\"".$tpc."%\" title=\"".$tpc."\"/>";
    }
    echo "</i></td></tr>";
  }
?>
</tbody>
</table>

<!-- Les bases -->

<?php
  $totaldb = $quota->get_size_db_sum_user($c["login"]);

  echo "<p>"._("Databases:")." ";
  echo sprintf("%.1f", $totaldb/(1024*1024))."&nbsp;"._("MB");
  echo "</p>";
?>

<table class="tedit">
<thead>
<tr>
  <th><?php __("DB"); ?></th>
  <th><?php __("Space"); ?></th>
</tr>
</thead>
<tbody>
<?php

  $db_sizes = $quota->get_size_db_details_user($c["login"]);
  foreach ($db_sizes as $d) {
    echo "<tr><td>".$d["db"]."</td><td";
    if ($mode!=2) echo " style=\"text-align: right\"";
    echo ">";
    $ds=$d["size"];
    if ($totaldb) {
      $pc=intval(100*$ds/$totaldb);
    } else {
      $pc=0;
    }
    if (isset($mode) && $mode==0) {
      echo sprintf("%.1f", $ds / 1024/1024)."&nbsp;"._("MB");
    } elseif (isset($mode) &&$mode==1) {
      echo sprintf("%.1f", $pc)."&nbsp;%";
    } else {
      echo "<img src=\"hippo_bleue.gif\" style=\"width: ".(2*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."%\"/>";
    }
    echo "</td></tr>";
  }
?>
</tbody>
</table>

<!-- Les listes -->

<?php
  $totallist = $quota->get_size_mailman_sum_user($c["uid"]);
  if ($totallist) {
?>

<?php
  echo "<p>"._("Mailman lists:")." ";
  echo sprintf("%.1f", $totallist/1024)."&nbsp;"._("MB");
  echo "</p>";
?>

<table class="tedit">
<thead>
<tr>
  <th><?php __("Lists"); ?></th>
  <th><?php __("Space"); ?></th>
</tr>
</thead>
<tbody>
<?php

  $mailman_size = $quota->get_size_mailman_details_user($c["uid"]);
  foreach ($mailman_size as $d) {
    echo "<tr><td>".$d["list"]."</td><td";
    if ($mode!=2) echo " style=\"text-align: right\"";
    echo ">";
    $ds=$d["size"];
    if ($totallist) {
      $pc=intval(100*$ds/$totallist);
    } else {
      $pc=0;
    }
    if ($mode==0) {
      echo sprintf("%.1f", $ds / 1024)."&nbsp;"._("MB");
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

    <?php } /* totallist */ ?>
</div>
</center>
