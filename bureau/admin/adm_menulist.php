<?php
/*
$Id: adm_menulist.php,v 1.1 2005/09/05 10:55:48 arnodu59 Exp $
----------------------------------------------------------------------
AlternC - Web Hosting System
Copyright (C) 2005 by the AlternC Development Team.
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
Original Author of file: Benjamin Sonntag
Purpose of file: Show a form to edit a member
----------------------------------------------------------------------
*/
require_once("../class/config.php");

include("head.php");

?>
<body>
<h3><?php __("About AlternC"); ?></h3>
<hr/>
<?php
$menu_available=array();
$menu_activated=array();
$menu_error=array();

$MENUPATH=ALTERNC_PANEL."/admin/";
$file=file("/etc/alternc/menulist.txt", FILE_SKIP_EMPTY_LINES);
foreach($file as $v) {
  $v=trim($v);
  if ( file_exists($MENUPATH.$v)) {
    $menu_activated[]=$v;
  } else {
    $menu_error[]=$v;
  }
}

$c=opendir($MENUPATH);
while ($di=readdir($c)) {
  if (preg_match("#^menu_.*\\.php$#",$di,$match)) {
    $menu_available[]=$match[0];
  }
}
closedir($c);

asort($menu_available);
asort($menu_activated);
asort($menu_error);

$menus=array(
	     "menu_dom" => _("Domains"),
	     "menu_mail" => _("Email Addresses"),
	     "menu_brouteur" => _("File browser"),
	     "menu_web" => _("Protected folders"),
	     "menu_ftp" => _("FTP accounts"),
	     "menu_cron" => _("Scheduled tasks"),
	     "menu_sql" => _("Databases"),
	     "menu_quota" => _("Show my quotas"),
	     "menu_ip" => _("Access security"),
	     "menu_logs" => _("Logs"),
	     "menu_aide" => _("Online help"),
	     "menu_lang" => _("Languages"),
	     "menu_mem" => _("Settings"),
	     "menu_piwik" => _("Piwik statistics"), 
	     "menu_admin" => _("Administration"),
	     "menu_mailman" => _("Mailing lists"),
	     "menu_aws" => _("Web Statistics"),
	     );

function tr($name) {
  global $menus;
  $name=basename($name,".php"); 
  if ($menus[$name]) return $menus[$name];
  else return _("Module")." ".$name;
}

$menu_diff=array_diff($menu_available,$menu_activated);

__("Edit the file /etc/alternc/menulist.txt to enable, disable ou change order of menu entry.");
?>
<h4><?php __("Menu currently activated"); ?></h4>
<ul>
<?php foreach($menu_activated as $m) { 
echo "<li>$m - <i>".tr($m)."</i></li>";
} ?>
</ul>
<h4><?php __("Menu activated but not present"); ?></h4>
<ul>
  <?php foreach($menu_error as $m) { 
echo "<li>$m - <i>".tr($m)."</i></li>";
}
 ?>
</ul>
<h4><?php __("Menu available but not activated"); ?></h4>
<ul>
  <?php foreach($menu_diff as $m) { 
echo "<li>$m - <i>".tr($m)."</i></li>";
}
?>
</ul>


<?php include_once('foot.php');?>
