<?php
/*
 $Id: ftp_edit.php,v 1.5 2006/01/12 01:10:48 anarcat Exp $
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
 Purpose of file: Edit an FTP account
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"id"      => array ("request", "integer", ""),
	"create"  => array ("get", "integer", "0"),
	"dir"     => array ("get", "string", "0"),
);
getFields($fields);

if (!$id && !$create) {
  $error=_("Neither a creation nor a edition");
  include_once("foot.php");
  exit();
}

if (!$id && $create) { //creation
  echo "<h3>"._("Create a FTP account")."</h3>";
} else {
   echo "<h3>"._("Editing a FTP account")."</h3>";
  $r=$ftp->get_ftp_details($id);
  if (!$r) {
    $error=$err->errstr();
  }
}

?>
<?php
if (isset($error) && $error) {
	echo "<p class=\"error\">$error</p>";
	include_once("foot.php");
	exit();
}
?>
<form method="post" action="ftp_doedit.php" name="main" id="main">
  <input type="hidden" name="id" value="<?php echo $id ?>" />
  <input type="hidden" name="create" value="<?php echo $create ?>" />
  <table border="1" cellspacing="0" cellpadding="4" class="tedit">
    <tr>
      <th><label for="login"><?php __("Username"); ?></label></th>
      <td><select class="inl" name="prefixe"><?php @$ftp->select_prefix_list($r["prefixe"]); ?></select>&nbsp;<b>_</b>&nbsp;<input type="text" class="int" name="login" id="login" value="<?php @ehe($r[0]["login"]); ?>" size="20" maxlength="64" /></td>
    </tr>
    <tr>
      <th><label for="dir"><?php __("Folder"); ?></label></th>
      <td>
        <input type="text" class="int" name="dir" id="dir" value="<?php empty($dir)?@ehe("/".$r[0]["dir"]):@ehe($dir); ?>" size="20" maxlength="64" />
	<script type="text/javascript">
	<!--
	  document.write("&nbsp;<input type=\"button\" name=\"bff\" onclick=\"browseforfolder('main.dir');\" value=\" <?php __("Choose a folder..."); ?> \" class=\"bff\">");
	//  -->
	</script>
      </td>
    </tr>
    <tr>
      <th><label for="pass"><?php __("Password"); ?></label></th>
      <td><input type="password" class="int" name="pass" id="pass" size="20" maxlength="64" value=""/><?php display_div_generate_password(DEFAULT_PASS_SIZE,"#pass","#passconf"); ?></td>
    </tr>
    <tr>
      <th><label for="passconf"><?php __("Confirm password"); ?></label></th>
      <td><input type="password" class="int" name="passconf" id="passconf" size="20" maxlength="64" value=""/></td>
    </tr>
    <tr class="trbtn">
      <td colspan="2">
        <input type="submit" class="inb" name="submit" value="<?php __("Save"); ?>" />
        <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='ftp_list.php'"/>
      </td>
    </tr>
  </table>
</form>

<script type="text/javascript">
document.forms['main'].login.focus();
document.forms['main'].setAttribute('autocomplete', 'off'); 
</script>
<?php include_once("foot.php"); ?>
