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
 * Show quotas for one user
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */

require_once("../class/config.php");
if (!defined("QUOTASONE")) return;

// FIXME missing getfield for $mode
if (!isset($mode)) { // when included from adm_login, mode is not set
  $mode = 0;
}
// $mode = 4; // to debug the graphical mode of quotas

// If the var $usr exists, it means we call quotas for 1 user from the general admin page.
// If not, we get the ID of the user via $mem->user["login"]
if (isset($usr) && is_int($usr)) {
  $id_usr=$usr;
  $login=$admin->get_login_by_uid($id_usr);
} else {
  $id_usr = $mem->user["uid"];
  $login = $mem->user["login"];
}
?>
<center>

<h3 style="text-align:center;"><?php printf(__("<b>%s</b> account", "alternc", true),$login); ?></h3>

<div style="width: 600px">

<!-- Webspaces -->

<?php

  $totalweb = $quota->getquota('web');
  if ( $totalweb['u'] > 0 ) {
    $t=$quota->get_size_unit($totalweb['u'] * 1024);
    echo "<p>".__("quota_web", "alternc", true)." "; // use quota_web because it's the magically translated string
    echo sprintf("%.1f", $t['size'])."&nbsp;".$t['unit'];
    echo "</p>";
  }
?>

<!-- Mails -->

<p style="text-align: left; font-size:16px;"><b><?php __("Emails"); ?></b></p>

<table class="tedit" width="100%">
<thead>
<tr>
   <th><?php __("Domains"); ?></th>
   <th><?php __("Emails"); ?></th>
   <th><?php __("Space"); ?></th>
</tr>
</thead>
<tbody>
<?php

  $domaines_user = $dom->enum_domains($id_usr);
  $totalmail=0;
  foreach ($domaines_user as $domaine) {
    $mstmp = $quota->get_size_mail_sum_domain($domaine);
    $totalmail+=$mstmp;
  }

  $t=$quota->get_size_unit($totalmail);

  foreach ($domaines_user as $domaine) { 
    $alias_sizes = $quota->get_size_mail_details_domain($domaine);
    $domsize = 0; 
    foreach ($alias_sizes as $e) {
      if($e['size'] > 0) {
        $domsize += $e['size'];
        echo "<tr><td>{$domaine}</td>";
        echo "<td>".str_replace("_","@",$e["alias"])."</td>";
        echo "<td"; if ($mode!=2) echo " style=\"text-align: right\""; echo ">";
        $ms = $quota->get_size_unit($e['size']);
        if ($totalmail) {
          $pc=intval(100*($e['size']/$totalmail));
        } else {
          $pc=0;
        }
        if ($mode==0) {
          echo sprintf("%.1f", $ms['size'])."&nbsp;".$ms['unit'];
        } elseif ($mode==1) {
          echo sprintf("%.1f", $pc)."&nbsp;%";
        } else {
          $quota->quota_displaybar($pc);
        }
        echo "</td></tr>";
      }
    }

    $d = $quota->get_size_unit($domsize);

    if ($totalmail) {
      $tpc = intval(100 * $domsize / $totalmail);
    } else {
      $tpc = 0;
    }
    if (count($alias_sizes) > 0) {
    echo "<tr><td style=\"text-align: right\"><i><b>". __('Total', "alternc", true). " {$domaine}</b></i></td><td></td>";
    echo "<td";
    if ($mode!=2) echo " style=\"text-align: right\"";
    echo "><i><b>";
    if ($mode==0) {
      echo sprintf("%.1f", $d['size'])."&nbsp;".$d['unit'];
    } elseif ($mode==1) {
      echo sprintf("%.1f", $tpc)."&nbsp;%";
    } else {
      $quota->quota_displaybar($tpc);
    }
    echo "</b></i></td></tr>";
  }
}
?>
</tbody>
</table>

<p>&nbsp;</p>
<!-- Databases -->

<?php
  $totaldb = $quota->get_size_db_sum_user($login);

  $t = $quota->get_size_unit($totaldb);

  echo "<p style=\"text-align: left; font-size:16px;\"><b>".__("Databases:", "alternc", true)." ";
  echo "</b></p>";
?>

<table class="tedit" width="100%">
<thead>
<tr>
  <th width='50%'><?php __("DB"); ?></th>
  <th width='50%'><?php __("Space"); ?></th>
</tr>
</thead>
<tbody>
<?php

  $db_sizes = $quota->get_size_db_details_user($login);
  foreach ($db_sizes as $d) {
    echo "<tr><td>".$d["db"]."</td><td";
    if ($mode!=2) echo " style=\"text-align: right\"";
    echo ">";
    $ds = $quota->get_size_unit($d["size"]);
    if ($totaldb) {
      $pc=intval(100*$d['size']/$totaldb);
    } else {
      $pc=0;
    }
    if (isset($mode) && $mode==0) {
      echo sprintf("%.1f", $ds['size'])."&nbsp;".$ds['unit'];
    } elseif (isset($mode) &&$mode==1) {
      echo sprintf("%.1f", $pc)."&nbsp;%";
    } else {
      $quota->quota_displaybar($pc, 0);
    }
    echo "</td></tr>";
  }

  if (count($db_sizes) > 0 && $mode==0) {
    echo "<tr><td style=\"text-align: right\"><i><b>". __('Total', "alternc", true). " " . __("Databases:", "alternc", true)."</b></i></td>";
    echo "<td style=\"text-align: right\"><i><b>";
    echo sprintf("%.1f", $t['size'])."&nbsp;".$t['unit'];
    echo "</b></i></td></tr>";
  }
?>
</tbody>
</table>

<!-- Mailing lists -->

<?php
  $totallist = $quota->get_size_mailman_sum_user($id_usr);
  if ($totallist) {
    // $totalweb is in KB, so we call get_size_unit() with it in Bytes
    $t=$quota->get_size_unit($totallist * 1024);

    echo "<p style=\"text-align: left; font-size:16px;\"><b>".__("Mailman lists:", "alternc", true)." ";
    echo "</b></p>";
?>

<table class="tedit" width='100%'>
<thead>
<tr>
  <th><?php __("Lists"); ?></th>
  <th><?php __("Space"); ?></th>
</tr>
</thead>
<tbody>
<?php

  $mailman_size = $quota->get_size_mailman_details_user($id_usr);
  foreach ($mailman_size as $d) {
    echo "<tr><td>".$d["list"]."</td><td";
    if ($mode!=2) echo " style=\"text-align: right\"";
    echo ">";
    $ds = $quota->get_size_unit($d["size"] * 1024);
    if ($totallist) {
      $pc=intval(100*$d['size']/$totallist);
    } else {
      $pc=0;
    }
    if ($mode==0) {
      echo sprintf("%.1f", $ds['size'])."&nbsp;".$ds['unit'];
    } elseif ($mode==1) {
      echo sprintf("%.1f", $pc)."&nbsp;%";
    } else {
      $quota->quota_displaybar($pc);
    }
    echo "</td></tr>";
  }

  if (count($db_sizes) > 0 && $mode==0) {
    echo "<tr><td style=\"text-align: right\"><i><b>". __('Total', "alternc", true). " " . __("Mailman lists:", "alternc", true)."</b></i></td>";
    echo "<td";
    if ($mode!=2) echo " style=\"text-align: right\"";
    echo "><i><b>";
    echo sprintf("%.1f", $t['size'])."&nbsp;".$t['unit'];
    echo "</b></i></td></tr>";
  }
?>
</tbody>
</table>

    <?php } /* totallist */ ?>
</div>
<p>&nbsp;</p>
</center>
