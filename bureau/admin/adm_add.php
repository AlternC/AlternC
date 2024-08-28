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
 * Form to add a new account to AlternC
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");
include_once("head.php");

if (!$admin->enabled) {
	$msg->raise("ERROR", "admin", _("This page is restricted to authorized staff"));
	echo $msg->msg_html_all();
	exit();
}

$fields = array (
	"canpass"    => array ("post", "integer", 1),
	"login"      => array ("post", "string", null),
	"pass"       => array ("post", "string", null),
	"passconf"   => array ("post", "string", null),
	"notes"      => array ("post", "string", null),
	"nom"        => array ("post", "string", null),
	"prenom"     => array ("post", "string", null),
	"nmail"      => array ("post", "string", null),
	"create_dom" => array ("post", "integer", 0),
);
getFields($fields);

$c=$admin->listPasswordPolicies();
$passwd_classcount = $c['adm']['classcount'];

?>
<h3><?php __("New AlternC account"); ?></h3>
<hr id="topbar"/>
<br />
<?php
echo $msg->msg_html_all();
?>
<form method="post" action="adm_doadd.php" id="main" name="main" autocomplete="off">
  <?php csrf_get(); ?>

<table class="tedit">
<tr><th><label for="login"><?php __("Username"); ?></label><span class="mandatory">*</span></th><td>
	<input type="text" class="int" name="login" id="login" autocomplete="off" value="<?php ehe($login); ?>" size="20" maxlength="16" />
</td></tr>
<tr>
	<th><label for="pass"><?php __("Initial password"); ?></label><span class="mandatory">*</span></th>
	<td><input type="password" id="pass" name="pass" autocomplete="off" class="int" value="<?php ehe($pass); ?>" size="20" maxlength="64" /><?php display_div_generate_password(DEFAULT_PASS_SIZE,"#pass","#passconf",$passwd_classcount); ?></td>
</tr>
<tr>
	<th><label for="passconf"><?php __("Confirm password"); ?></label><span class="mandatory">*</span></th>
	<td><input type="password" id="passconf" name="passconf" autocomplete="off" class="int" value="<?php ehe($passconf); ?>" size="20" maxlength="64" /></td>
</tr>
<tr>
	<th><label><?php __("Can he change its password"); ?></label></th>
	<td>
        <input type="radio" class="inc" id="canpass0" name="canpass" value="0"<?php cbox($canpass==0); ?>/><label for="canpass0"><?php __("No"); ?></label><br />
	<input type="radio" class="inc" id="canpass1" name="canpass" value="1"<?php cbox($canpass==1); ?>/><label for="canpass1"><?php __("Yes"); ?></label><br />	
	</td>
</tr>
<tr>
       <th><label for="notes"><?php __("Notes"); ?></label></th>
       <td><textarea name="notes" id="notes" class="int" cols="32" rows="5"><?php  ehe($notes); ?></textarea></td>
</tr>
<tr>
        	<th><label for="nom"><?php echo _("Surname")."</label> / <label for=\"prenom\">"._("First Name"); ?></label></th>
	<td><input class="int" type="text" id="nom" name="nom" value="<?php ehe($nom); ?>" size="16" maxlength="128" />&nbsp;/&nbsp;<input type="text" name="prenom" id="prenom" value="<?php ehe($prenom); ?>" class="int" size="16" maxlength="128" /></td>
</tr>
<tr>
	<th><label for="nmail"><?php __("Email address"); ?></label><span class="mandatory">*</span></th>
	<td><input type="text" name="nmail" id="nmail" class="int" value="<?php ehe($nmail); ?>" size="30" maxlength="128" /></td>
</tr>
<tr>
	<th><label for="type"><?php __("Account type"); ?></label></th>
	<td><select name="type" id="type" class="inl">
	<?php
	eoption($quota->listtype(), 'default', true);
?></select>
</td>
</tr>
<tr>
    <th>
        <?php 
          __("Associate this new user to this database server:");
          echo "<br/>";
          echo "<i>"._("Warning: you can't change it after the creation of the user.")."</i>";
        ?>
     </th>
     <td><?php
          echo "<select name='db_server_id' id='db_server_id' >";
          foreach ($mysql->list_db_servers() as $ldb ) {
            echo "<option value='".$ldb['id']."'>".$ldb['name']."</option>";
          }
          echo "</select>";
        ?>
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
            foreach($domain as $val) { ?>
               <option value="<?php echo $val; ?>" > <?php echo $val?> </option>
            <?php } ?>
        </select>
    </th>
</tr>
 <?php } ?>
<tr class="trbtn"><td colspan="2">
  <input type="submit" class="inb ok" name="submit" value="<?php __("Create this AlternC account"); ?>" />
  <input type="button" class="inb cancel" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='adm_list.php'" />
</td></tr>
</table>
</form>
<script type="text/javascript">
 document.forms['main'].login.focus();
</script>

<?php include_once("foot.php"); ?>
