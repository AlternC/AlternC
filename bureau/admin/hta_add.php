<?php
/*
 $Id: hta_add.php,v 1.3 2003/06/10 13:16:11 root Exp $
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
 Original Author of file: Franck Missoum
 Purpose of file: Ask the required values to protect a folder
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

if (!isset($is_include)) {
  $fields = array (
    "dir"      => array ("request", "string", ""),
  );
  getFields($fields);
}

?>
<h3><?php __("Protect a folder"); ?></h3>
<hr id="topbar"/>
<br/>
<p>
<?php __("Enter the name of the folder you want to protect. It must already exists."); ?>
</p>
<?php
echo $msg->msg_html_all();
?>

<form method="post" action="hta_doadd.php" name="main" id="main">
  <?php csrf_get(); ?>
  <table border="1" cellspacing="0" cellpadding="4" class='tedit'>
    <tr>
      <th><label for="dir"><?php __("Folder"); ?></label></th>
      <td>
        <input type="text" class="int" name="dir" id="dir" value="<?php (isset($dir)) ? : $dir="";ehe($dir); ?>" maxlength="255" />
        <?php display_browser($dir, "dir" ); ?>
      </td>
    </tr>
  </table>
  <br />
  <input type="submit" class="inb lock" value="<?php __("Protect this folder"); ?>" onClick="return false_if_empty('dir', '<?php echo addslashes(_("Can't have empty directory."));?>');" />
</form>

<script type="text/javascript">
  document.forms['main'].dir.focus();
</script>

<?php include_once("foot.php"); ?>
