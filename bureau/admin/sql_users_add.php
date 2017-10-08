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
 * Form to add a MySQL user account
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */

require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"usern"     => array ("post", "string", ""),
	"password"    => array ("post", "string", ""),
	"passconf"    => array ("post", "string", ""),
);
getFields($fields);

$c=$admin->listPasswordPolicies();
$passwd_classcount = $c['mysql']['classcount'];

?>
<h3><?php __("Create a new MySQL user"); ?></h3>
<hr id="topbar"/>
<br />
<?php
echo $msg->msg_html_all();

if (isset($fatal) && $fatal) {
    include_once("foot.php");
    exit();
}
?>
<form method="post" action="sql_users_doadd.php" id="main" name="main" autocomplete="off">
  <?php csrf_get(); ?>

<table class="tedit">
<tr>
<?php
// We check the max length of a mysql user (defined in the variables of AlternC) 
// we use that for the maxlenght of the Input 
$len=variable_get('sql_max_username_length', NULL)-strlen($mem->user["login"]."_");
?>
  <th><label for="usern"><?php __("Username"); ?></label></th>
  <td><span class="int" id="usernpfx"><?php echo $mem->user["login"]; ?>_</span><input type="text" class="int" name="usern" id="usern" value="<?php ehe($usern); ?>" size="20" maxlength="<?php echo $len; ?>" /></td>
</tr>
<tr>
  <th><label for="password"><?php __("Password"); ?></label></th>
  <td><input type="password" class="int" autocomplete="off" name="password" id="password" size="26"/><?php display_div_generate_password(DEFAULT_PASS_SIZE,"#password","#passconf",$passwd_classcount); ?></td>
</tr>
<tr>
  <th><label for="password"><?php __("Confirm password"); ?></label></th>
  <td><input type="password" class="int" autocomplete="off" name="passconf" id="passconf" size="26"/></td>
</tr>

<tr class="trbtn"><td colspan="2">
  <input type="submit" class="inb ok" name="submit" value="<?php __("Create this new MySQL user"); ?>" />
  <input type="button" class="inb cancel" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='sql_users_list.php'"/>
</td></tr>
</table>
</form>
<script type="text/javascript">
  if (document.forms['main'].usern.value!='') {
    document.forms['main'].password.focus();
  } else {
    document.forms['main'].usern.focus();
  }
</script>
<?php include_once("foot.php"); ?>
