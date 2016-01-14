<?php
/*
 $Id: hta_edit.php,v 1.4 2003/06/10 13:16:11 root Exp $
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
 Original Author of file: Franck Missoum, Benjamin Sonntag
 Purpose of file: Edit a protected folder
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"dir"      => array ("request", "string", ""),
);
getFields($fields);

if (!$dir) {
  echo "<p class=\"alert alert-warning\">"._("No folder selected!")."</p>";
  require_once('foot.php');
  die();
} else {
  $r=$hta->get_hta_detail($dir);
  if (!$r) {
    $error=$err->errstr();
  }
} // if !$dir

?>
<h3><?php printf(_("List of authorized user in folder %s"),$dir); ?></h3>
<hr id="topbar"/>
<br />
<?php
  if (!count($r)) {
    echo "<p class=\"alert alert-warning\">".sprintf(_("No authorized user in %s"),$dir)."</p>";
  } else {
     reset($r);
?>
<form method="post" action="hta_dodeluser.php">
<table cellspacing="0" cellpadding="4" class='tlist'>
  <tr>
    <th colspan="2" ><input type="hidden" name="dir" value="<?php echo $dir?>"> </th>
    <th><?php __("Username"); ?></th>
  </tr>
<?php
for($i=0;$i<count($r);$i++){ ?>
  <tr class="lst">
    <td align="center"><input type="checkbox" class="inc" name="d[]" value="<?php echo $r[$i]?>" /></td>
    <td>
      <div class="ina"><a href="hta_edituser.php?user=<?php echo urlencode($r[$i])?>&amp;dir=<?php echo urlencode($dir); ?>"><img src="icon/encrypted.png" alt="<?php __("Change this user's password"); ?>" /><?php __("Change this user's password"); ?></a></div>
    </td>
    <td><?php echo $r[$i]; ?></td>
  </tr>
<?php
} // for $i
?>
</table>

<br />
<input type="submit" class="inb" name="submit" value="<?php __("Delete the checked users"); ?>" />
</form>

<?php } // else !count $r ?>
<p>
<span class="inb"><a href="bro_main.php?R=<?php echo $dir ?>"><?php __("Show this folder's content in the File Browser"); ?></a></span>
</p>

<p>&nbsp;</p>

<fieldset>
  <legend><h3><?php __("Adding an authorized user"); ?></h3></legend>

  <form method="post" action="hta_doadduser.php" name="main" id="main" autocomplete="off">

<!-- honeypot fields -->
<input type="text" style="display: none" id="fakeUsername" name="fakeUsername" value="" />
<input type="password" style="display: none" id="fakePassword" name="fakePassword" value="" />

    <table class="tedit">
      <tr>
        <th><input type="hidden" name="dir" value="<?php echo $dir ?>" /><?php __("Folder"); ?></th>
        <td><?php echo '<a href="bro_main.php?R='.urlencode($dir).'">'.htmlspecialchars($dir).'</a>'; ?></td>
      </tr>
      <tr>
        <th><label for="user"><?php __("Username"); ?></label></th>
        <td><input type="text" class="int" name="user" id="user" value="" size="20" maxlength="64" /></td>
      </tr>
      <tr>
        <th><label for="password"><?php __("Password"); ?></label></th>
        <td><input type="password" class="int" name="password" autocomplete="off" id="password" value="" size="20" maxlength="64" /><?php display_div_generate_password(DEFAULT_PASS_SIZE,"#password","#passwordconf"); ?></td>
      </tr>
      <tr>
        <th><label for="passwordconf"><?php __("Confirm password"); ?></label></th>
        <td><input type="password" class="int" name="passwordconf" autocomplete="off" id="passwordconf" value="" size="20" maxlength="64" /></td>
      </tr>
    </table>

    <br />
    <input type="submit" class="inb ok" value="<?php __("Add this user"); ?>" />
  <input type="button" class="inb cancel" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='hta_list.php';"/>
  </form>
</fieldset>
  
<script type="text/javascript">
  document.forms['main'].user.focus();
</script>

<?php include_once("foot.php"); ?>
