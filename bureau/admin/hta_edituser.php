<?php
/*
 $Id: hta_edituser.php,v 1.4 2006/01/12 01:10:48 anarcat Exp $
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
 Purpose of file: Edit a username from a protected folder
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once ("head.php");

$fields = array (
	"user"     => array ("request", "string", ""),
	"dir"      => array ("request", "string", ""),
);
getFields($fields);

?>
<h3><?php printf(_("Editing user %s in the protected folder %s"),$user,$dir); ?></h3>
<hr id="topbar"/>
<br />

<?php if (!empty($error) ) { echo "<p class=\"alert alert-danger\">$error</p>"; } ?>

<form method="post" action="hta_doedituser.php" name="main" id="main" autocomplete="off">

<!-- honeypot fields -->
<input type="text" style="display: none" id="fakeUsername" name="fakeUsername" value="" />
<input type="password" style="display: none" id="fakePassword" name="fakePassword" value="" />

  <input type="hidden" name="dir" value="<?php echo $dir ?>">
  <input type="hidden" name="user" value="<?php echo $user ?>">
  <table border="1" cellspacing="0" cellpadding="4" class='tedit'>
    <tr>
      <th><?php __("Folder"); ?></th>
      <td><code><?php echo $dir; ?></code></td>
    </tr>
    <tr>
      <th><?php __("User"); ?></th>
      <td><code><?php echo $user; ?></code></td>
    </tr>
    <tr>
      <th><label for="newpass"><?php __("New password"); ?></label></th>
      <td><input type="password" class="int" name="newpass" autocomplete="off" id="newpass" value="" size="20" maxlength="64" /><?php display_div_generate_password(DEFAULT_PASS_SIZE,"#newpass","#newpassconf"); ?></td>
    </tr>
    <tr>
      <th><label for="newpassconf"><?php __("Confirm password"); ?></label></th>
      <td><input type="password" class="int" name="newpassconf" autocomplete="off" id="newpassconf" value="" size="20" maxlength="64" /></td>
    </tr>
  </table>
  <br/>
  <input type="submit" class="inb" value="<?php __("Change the password"); ?>" />
</form>

<script type="text/javascript">
  document.forms['main'].newpass.focus();
</script>

<?php include_once("foot.php"); ?>
