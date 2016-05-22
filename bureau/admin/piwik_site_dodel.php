<?php
/*
 $Id: piwik_site_dodel.php,v 1.2 2003/06/10 06:45:16 root Exp $
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
 Original Author of file: François Serman
 Purpose of file: Delete piwik websites
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$fields = array (
  "confirm_del"    	=> array ("post", "string", ""),
  "siteid"    		=> array ("request", "integer", -1),
);
getFields($fields);

if ($siteid === -1) {
    $error=_("Missing site parameters");
    include('piwik_sitelist.php'); 
    exit;
}

if(!empty($confirm_del) ) {

  if (! $piwik->site_delete($siteid) ) {
    $error=$err->errstr();
  } else {
    include_once('head.php');
    __("Site successfully deleted");
  }

  include('piwik_sitelist.php'); 
  exit;

}

include_once('head.php');

?>
<h3><?php __("Piwik site deletion confirm"); ?></h3>
<hr id="topbar"/>
<br />
  <?php __("Do you really want to delete this Piwik website ?");?>
<br />
<br />

  <form method="post" action="piwik_site_dodel.php" name="main" id="main">
  <?php csrf_get(); ?>
    <input type="hidden" name="siteid"  value="<?php ehe($siteid);?>" />
    <input type="submit" class="inb" name="confirm_del" value="<?php __("Delete")?>" />
    <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='piwik_sitelist.php'" />
  </form>
  
<?php
  include_once('foot.php');
  exit();
?>
