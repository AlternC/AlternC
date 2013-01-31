<?php
/*
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2000-2012 by the AlternC Development Team. 
 https://alternc.org/ 
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
 Purpose of file: List awstats accounts of the user.
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

?>
<h3><?php __("Awstats allowed user list"); ?></h3>
<hr id="topbar"/>
<br />
<?php

if (isset($error) && $error) { ?>
<p class="error"><?php echo $error; $error=''; ?></p>
<?php }

$nologin=false;
if (!$r=$aws->list_login()) {
	$nologin=true;
	$error=$err->errstr();
}

	if ($quota->cancreate("aws")) { ?>
<p><span class="ina"><a href="aws_add.php"><?php __("Create new Statistics"); ?></a></span></p>
<?php  	} ?>

<form method="post" action="aws_useradd.php" name="main">
<table class="tedit">
<tr><th>
<label for="login"><?php __("Username"); ?></label></th><td>
	<select class="inl" name="prefixe"><?php $aws->select_prefix_list($prefixe); ?></select>&nbsp;<b>_</b>&nbsp;<input type="text" class="int" name="login" id="login" value="" size="20" maxlength="64" />
</td></tr>
<tr><th><label for="pass"><?php __("Password"); ?></label></th><td><input type="password" class="int" name="pass" id="pass" value="" size="20" maxlength="64" /><?php display_div_generate_password(DEFAULT_PASS_SIZE,"#pass","#passconf"); ?></td></tr>
<tr><th><label for="passconf"><?php __("Confirm password"); ?></label></th><td><input type="password" class="int" name="passconf" id="passconf" value="" size="20" maxlength="64" /></td></tr>
<tr class="trbtn"><td colspan="2">
  <input type="submit" class="inb" name="submit" value="<?php __("Create this new Awstats user"); ?>" />
  <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='aws_list.php'"/>

</td></tr>
</table>
</form>
<br />
<?php


if (isset($error) && $error) {
?>
<p class="error"><?php echo $error ?></p>
<?php }

if (!$nologin) {
?>


<form method="post" action="aws_userdel.php" name="main2" id="main2">
<table class="tlist">
    <tr><th colspan="2"><?php __("Action"); ?></th><th><?php __("Username"); ?></th></tr>
<?php
$col=1;
foreach ($r as $val) {
	$col=3-$col;
?>
	<tr class="lst<?php echo $col; ?>">
		<td align="center"><input type="checkbox" class="inc" id="del_<?php echo $val; ?>" name="del_<?php echo $val; ?>" value="<?php echo $val; ?>" /></td>
		<td><span class="ina"><a href="aws_pass.php?login=<?php echo $val ?>"><?php __("Change password"); ?></a></span></td>
		<td><label for="del_<?php echo $val; ?>"><?php echo $val ?></label></td>
	</tr>
<?php
	}
?>
<tr><td colspan="5"><input type="submit" name="submit" class="inb" onClick='return confirm("<?php __("Are you sure you want to delete the selected accounts?");?>");' value="<?php __("Delete checked accounts"); ?>" /></td></tr>
</table>
</form>


<?php
 }
?>
<script type="text/javascript">
document.forms['main'].login.focus();
document.forms['main'].setAttribute('autocomplete', 'off');
</script>

<?php include_once("foot.php"); ?>
