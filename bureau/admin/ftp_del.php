<?php
/*
 $Id: ftp_del.php,v 1.2 2003/06/10 06:45:16 root Exp $
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
 Original Author of file: Benjamin Sonntag
 Purpose of file: Delete ftp accounts
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

// On parcours les POST_VARS et on repere les del_.
reset($_POST);
$lst_todel=array();
while (list($key,$val)=each($_POST)) {
  if (substr($key,0,4)=="del_") {
    $lst_todel[]=$val;
  }
}

if (empty($lst_todel)) {
  header ("Location: /ftp_list.php");
  exit();
}

$fields = array (
  "confirm_del"    	=> array ("post", "string", ""),
  "names"    		=> array ("post", "array", ""),
);
getFields($fields);


if(!empty($confirm_del)) {
  foreach($lst_todel as $v) {
    $r=$ftp->delete_ftp($v);
    if ($r) {
      $msg->raise("INFO", "ftp", _("The FTP account %s has been successfully deleted"),$r);
    }
  }
  include("ftp_list.php");
  exit();
} else {
  include_once('head.php');
?>
<h3><?php __("Confirm the FTP accounts deletion"); ?></h3>
<hr id="topbar"/>
<br />
  <?php __("Do you really want to delete those accounts?");?>
  <ul>
  <?php foreach($lst_todel as $t) {
    echo "<li><b>".$names[$t]."</b></li>\n";
  } ?>
  </ul>

  <form method="post" action="ftp_del.php" name="main" id="main">
      <?php csrf_get(); ?>
    <?php foreach($lst_todel as $t) {
      echo '<input type="hidden" name="del_'.ehe($t,false).'" value="'.ehe($t,false).'" >'."\n";
    } ?>
    <input type="submit" class="inb" name="confirm_del" value="<?php __("Delete")?>" />
    <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='ftp_list.php'" />
  </form>
  
<?php
  include_once('foot.php');
  exit();
}

?>
