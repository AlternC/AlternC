<?php
/*
 $Id: menu.php,v 1.9 2005/01/18 22:16:10 anarcat Exp $
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

?>
<img src="logo3.png" class="menutoplogo" border="0" alt="AlternC" alt="<?php __("AlternC"); ?>"/>
<p class="currentuser"><?php echo sprintf(_("Welcome %s"),$mem->user["login"]); ?></p>

<?php

$obj_menu = $menu->getmenu();

foreach ($obj_menu as $k => $m ) {
  echo "<div class='menu-box ".(!empty($m['divclass'])?$m['divclass']:'')."'>\n";
  echo "  <a href=\"".$m['link']."\"";
  if (!empty($m['target'])) echo " target='". $m['target']."' ";
  echo ">\n";
  echo "    <div class='menu-title'>\n";
  echo "      <img src='".$m['ico']."' alt=\"".$m['title']."\" width='16' height='16' />&nbsp;";
  echo "        <span class='";
  if (!empty($m['class'])) echo $m['class']." ";
  echo "'>"; // fin span ouvrant
  echo $m['title'];
  if (isset($m['quota_total'])) {
    if (!$quota->cancreate($k)) { echo '<span class="full">' ; } else { echo "<span>"; }
    echo " (".$m['quota_used']."/".$m['quota_total'].")";
    echo "</span>\n";
  } // if there are some quota
  if ( empty($m['links'])) {
    $i = "images/menu_right.png";
    // img machin
  } else {
    if ( $m['visibility'] ) {
      $i="/images/menu_moins.png";
    } else {
      $i="/images/menu_plus.png";
    }
  }
  echo "      <img src='$i' alt='' style='float:right;' id='menu-$k-img'/>\n";
  echo "      </span>";
  echo "    </div>\n";
  echo "  </a>\n";

  if (!empty($m['links'])) {
    echo "<div class='menu-content' id='menu-$k'>";
    echo "  <ul>";
    foreach( $m['links'] as $l ) {
      if ( $l['txt'] == 'progressbar' ) {
        $usage_percent = (int) ($l['used'] / $l['total'] * 100);
        $usage_color = ( $l['used'] > $l['total'] ? '#800' : '#080');
        $usage_color = ((85 < $usage_percent && $usage_percent <= 100) ? '#ff8800' : $usage_color); // yellow
        echo "<li>";
        echo '<div class="progress-bar">';
        echo '<div style="width: ' . ($usage_percent > 100 ? 100 : $usage_percent) . '%; background: ' . $usage_color . ';">&nbsp;</div>';
        echo '</div>';
        echo "</ul>";
        continue;
      } // progressbar
      echo "<li><a href=\"".$l['url']."\" ";
      if (!empty($l['onclick'])) echo " onClick='". $l['onclick']."' ";
      if (!empty($l['target'])) echo " target='". $l['target']."' ";
      echo " ><span class='".(empty($l['class'])?'':$l['class'])."'>";
      if (!empty($l['ico'])) echo "<img src='".$l['ico']."' alt='' />&nbsp;";
      echo $l['txt'];
      echo "</span></a></li>";
    }
    echo "  </ul>";
    echo "</div>";
  }
  echo "</div>";
  if (! $m['visibility']) echo "<script type='text/javascript'>menu_toggle('menu-$k');</script>\n";

}

?>
<p class="center"><a href="about.php"><img src="logo2.png" class="menulogo" border="0" alt="AlternC" title="<?php __("About"); ?>"/></a>
<br />
<?php 
echo "$L_VERSION";
?>
</p>


