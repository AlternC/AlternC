<?php
/*
 $Id: adm_add.php,v 1.9 2006/01/24 05:03:30 joe Exp $
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2006 Le réseau Koumbit Inc.
 http://koumbit.org/
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
 Purpose of file: Member managment
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

if (!$admin->enabled) {
	__("This page is restricted to authorized staff");
	exit();
}

$fields = array (
	"canpass"    => array ("request", "integer", 1),
	"login"      => array ("request", "string", null),
	"pass"       => array ("request", "string", null),
	"passconf"   => array ("request", "string", null),
	"notes"      => array ("request", "string", null),
	"nom"        => array ("request", "string", null),
	"prenom"     => array ("request", "string", null),
	"nmail"      => array ("request", "string", null),
	"create_dom" => array ("request", "integer", 0),
);
getFields($fields);

?>
<h3><?php __("New AlternC account"); ?></h3>
<hr id="topbar"/>
<br />
<?php
if (isset($error) && $error) {
	echo "<p class=\"error\">$error</p>";
}
?>
<form method="post" action="adm_doadd.php" id="main" name="main">
<table class="tedit">
<tr><th><label for="login"><?php __("Username"); ?></label></th><td>
	<input type="text" class="int" name="login" id="login" value="<?php ehe($login); ?>" size="20" maxlength="16" />
</td></tr>
<tr>
	<th><label for="pass"><?php __("Initial password"); ?></label></th>
	<td><input type="password" id="pass" name="pass" class="int" value="<?php ehe($pass); ?>" size="20" maxlength="64" /><?php display_div_generate_password(DEFAULT_PASS_SIZE,"#pass","#passconf"); ?></td>
</tr>
<tr>
	<th><label for="passconf"><?php __("Confirm password"); ?></label></th>
	<td><input type="password" id="passconf" name="passconf" class="int" value="<?php ehe($passconf); ?>" size="20" maxlength="64" /></td>
</tr>
<tr>
	<th><label for="canpass"><?php __("Can he change its password"); ?></label></th>
	<td>
        <input type="radio" class="inc" id="canpass0" name="canpass" value="0"<?php cbox($canpass==0); ?>><label for="canpass0"><?php __("No"); ?></label><br />
	<input type="radio" class="inc" id="canpass1" name="canpass" value="1"<?php cbox($canpass==1); ?>><label for="canpass1"><?php __("Yes"); ?></label><br />	
	</td>
</tr>
<tr>
       <th><label for="notes"><?php __("Notes"); ?></label></th>
       <td><textarea name="notes" id="notes" class="int" cols="32" rows="5"><?php  ehe($notes); ?></textarea></td>
</tr>
<tr>
        	<th><label for="nom"><?php echo _("Surname")."</label> / <label for=\"prenom\">"._("First Name"); ?></label></th>
	<td><input class="int" type="text" id="nom" name="nom" value="<?php ehe($nom); ?>" size="20" maxlength="128" />&nbsp;/&nbsp;<input type="text" name="prenom" id="prenom" value="<?php ehe($prenom); ?>" class="int" size="20" maxlength="128" /></td>
</tr>
<tr>
	<th><label for="nmail"><?php __("Email address"); ?></label></th>
	<td><input type="text" name="nmail" id="nmail" class="int" value="<?php ehe($nmail); ?>" size="30" maxlength="128" /></td>
</tr>
<tr>
	<th><label for="type"><?php __("Account type"); ?></label></th>
	<td><select name="type" id="type" class="inl">
	<?php
	$db->query("SELECT distinct(type) FROM defquotas ORDER by type");
	while($db->next_record()) {
	  $type = $db->f("type");
	  echo "<option value=\"$type\"";
	  if($type == 'default')
	    echo " selected=\"selected\"";
	  echo ">$type</option>";
	}
?></select>
</td>
</tr>
<?php if (variable_get('hosting_tld') || $dom->enum_domains()) { ?>
<tr>
    <th colspan="2">
        <input type="checkbox" name="create_dom" value="1" class="inc" id="create_dom" <?php cbox($create_dom==1); ?>/>
        <label for="create_dom"><?php printf(_("Install the domain"),""); ?></label>
        <span class="int" id="create_dom_list_pfx">login.</span><select name="create_dom_list" class="int" id="create_dom_list">
            <?php if (variable_get('hosting_tld')) { ?>
             <option value="<?php echo variable_get('hosting_tld'); ?>" selected="selected"><?php echo variable_get('hosting_tld'); ?></option>
          <?php } 
            /* Enumeration des domaines : */
            $domain=$dom->enum_domains();
            reset($domain);
            while (list($key,$val)=each($domain)) { ?>
               <option value="<?php echo $val; ?>" > <?php echo $val?> </option>
            <?php } ?>
        </select>
    </th>
</tr>
 <?php } ?>
<tr class="trbtn"><td colspan="2">
  <input type="submit" class="inb" name="submit" value="<?php __("Create this AlternC account"); ?>" />
  <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='adm_list.php'" />
</td></tr>
</table>
</form>
<script type="text/javascript">
 document.forms['main'].login.focus();
 document.forms['main'].setAttribute('autocomplete', 'off');
</script>

<?php include_once("foot.php"); ?>
