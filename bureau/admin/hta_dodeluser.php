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
 * Delete a username from a protected folder
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");

$fields = array (
        "d"                => array ("post", "array", array()),
        "dir"              => array ("post", "string", ""),
	"confirm_del"      => array ("post", "string", ""),
);
getFields($fields);

if (!empty($confirm_del)) {
  reset($d);
  if ($hta->del_user($d,$dir)) {
    foreach ($d as $v) {
      $msg->raise("INFO", "hta", _("The user '%s' was successfully deleted"), $v);
    }
  }
  $is_include=true;
  include_once("hta_edit.php");
  exit();
}
include_once('head.php');
?>
<h3><?php __("Authorized user deletion confirm"); ?></h3>
<hr id="topbar"/>
<br />
  <?php __("Do you really want to delete those users ?");?>
  <ul>
  <?php foreach($d as $t) {
    echo "<li><b>".ehe($t,false)."</b></li>\n";
  } ?>
  </ul>

  <form method="post" action="hta_dodeluser.php" name="main" id="main">
  <?php csrf_get(); ?>
    <input type="hidden" name="dir" value="<?php ehe($dir); ?>" >
    <?php foreach($d as $t) {
    echo '<input type="hidden" name="d['.ehe($t,false).']" value="'.ehe($t,false).'" >'."\n";
    } ?>
    <input type="submit" class="inb" name="confirm_del" value="<?php __("Delete")?>" />
    <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='/hta_edit.php?dir=<?php echo urlencode($dir);?>'" />
  </form>

<?php
include_once('foot.php');
exit();
?>
