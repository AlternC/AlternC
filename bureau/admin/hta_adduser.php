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
 * Add a user to a protected folder (using .htaccess for apache)
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"dir"      => array ("request", "string", ""),
	"user"     => array ("post", "string", ""),
);
getFields($fields);

?>
<h3><?php printf(_("Adding a username in %s"),$dir); ?></h3>
<?php
echo $msg->msg_html_all();

$c=$admin->listPasswordPolicies();
$passwd_classcount = $c['hta']['classcount'];
?>

<form method="post" action="hta_doadduser.php" name="main" id="main" autocomplete="off">
  <?php csrf_get(); ?>

<table border="1" cellspacing="0" cellpadding="4" class='tedit'>
	<tr>
		<th><input type="hidden" name="dir" value="<?php ehe($dir); ?>" /><?php __("Folder"); ?></th>
		<td><code><?php echo $dir; ?></code></td>
	</tr>
	<tr>
		<th><label for="user"><?php __("Username"); ?></label></th>
		<td><input type="text" class="int" name="user" id="user" value="<?php ehe($user); ?>" size="20" maxlength="64" /></td>
	</tr>
	<tr>
		<th><label for="password"><?php __("Password"); ?></label></th>
		<td><input type="password" class="int" name="password" autocomplete="off" id="password" value="" size="20" maxlength="64" /><?php display_div_generate_password(DEFAULT_PASS_SIZE,"#password","#passwordconf",$passwd_classcount); ?></td>
	</tr>
	<tr>
		<th><label for="passwordconf"><?php __("Confirm password"); ?></label></th>
		<td><input type="password" class="int" name="passwordconf" autocomplete="off" id="passwordconf" value="" size="20" maxlength="64" /></td>
	</tr>
</table>
<br />
<input type="submit" class="inb" value="<?php __("Add this user"); ?>" />
  <input type="button" class="inb" value="<?php __("Cancel"); ?>" onclick="document.location='hta_edit.php?dir=<?php echo urlencode($dir);  ?>';" />
</form>
<script type="text/javascript">
  document.forms['main'].user.focus();
</script>
<?php include_once("foot.php"); ?>
