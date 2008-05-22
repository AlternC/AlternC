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

if (!$admin->enabled) {
	__("This page is restricted to authorized staff");
	exit();
}

if (!isset($canpass)) $canpass=1;

include("head.php");

?>
</head>
<body>
<h3><?php __("New member"); ?></h3>
<?php
if ($error) {
	echo "<p class=\"error\">$error</p>";
}
?>
<form method="post" action="adm_doadd.php">
<table border="1" cellspacing="0" cellpadding="4">
<tr><th><label for="login"><?php __("Username"); ?></label></th><td>
	<input type="text" class="int" name="login" id="login" value="<?php echo $login; ?>" size="20" maxlength="64" />
</td></tr>
<tr>
	<th><label for="pass"><?php __("Initial password"); ?></label></th>
	<td><input type="password" id="pass" name="pass" class="int" value="<?php echo $pass; ?>" size="20" maxlength="64" /></td>
</tr>
<tr>
	<th><label for="passconf"><?php __("Confirm password"); ?></label></th>
	<td><input type="password" id="passconf" name="passconf" class="int" value="<?php echo $passconf; ?>" size="20" maxlength="64" /></td>
</tr>
<tr>
	<th><label for="canpass"><?php __("Can he change its password"); ?></label></th>
	<td><select class="inl" name="canpass" id="canpass">
	<?php 
	for($i=0;$i<count($bro->l_icons);$i++) {
	  echo "<option";
	  if ($canpass==$i) echo " selected=\"selected\"";
	  echo " value=\"$i\">"._($bro->l_icons[$i])."</option>";
	}
?></select>
	</td>
</tr>
<tr>
	<th><label for="nom"><?php echo _("Surname")."</label> / <label for=\"prenom\">"._("First Name"); ?></label></th>
	<td><input class="int" type="text" id="nom" name="nom" value="<?php echo $nom; ?>" size="20" maxlength="128" />&nbsp;/&nbsp;<input type="text" name="prenom" id="prenom" value="<?php echo $prenom; ?>" class="int" size="20" maxlength="128" /></td>
</tr>
<tr>
	<th><label for="nmail"><?php __("Email address"); ?></label></th>
	<td><input type="text" name="nmail" id="nmail" class="int" value="<?php echo $nmail; ?>" size="30" maxlength="128" /></td>
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

<tr>
    <th colspan="2">
        <input type="checkbox" name="create_dom" value="1" />
        <label><?php printf(_("Create the domain <b>username.%s</b>"),""); ?></label>
        <select name="create_dom_list">
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
<tr>
	<td colspan="2"><input type="submit" class="inb" name="submit" value="<?php __("Create a new member"); ?>" /></td>
</tr>
</table>
</form>

</body>
</html>
