<?php
/*
 $Id: adm_domstypeedit.php,v 1.6 2006/01/12 01:10:48 anarcat Exp $
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
 Original Author of file: Alan Garcia
 Purpose of file: Edit the domain types
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
if (!$admin->enabled) {
    __("This page is restricted to authorized staff");
    exit();
}

include_once("head.php");

$fields = array (
    "name"          => array ("request", "string", ""),
    "description"   => array ("request", "string", ""),
    "target"        => array ("request", "string", ""),
    "entry"         => array ("request", "string", ""),
    "compatibility" => array ("request", "string", ""),
    "enable"        => array ("request", "string", ""),
    "only_dns"      => array ("request", "boolean", ""),
    "need_dns"      => array ("request", "boolean", ""),
    "advanced"      => array ("request", "boolean", ""),
);
getFields($fields);


if (! $d=$dom->domains_type_get($name)) {
	$error=$err->errstr();
	echo $error;
} else {
?>

<h3><?php __("Edit a domain type"); ?> </h3>
<hr id="topbar"/>
<br />
<?php
if ($error_edit) {
	echo "<p class=\"error\">$error_edit</p>";
	$error_edit="";

} ?>

<form action="adm_domstypedoedit.php" method="post" name="main" id="main">
    <input type="hidden" name="name" value="<?php echo $d['name']; ?>" />
    <table class="tedit">
      <tr>
            <th><?php __("Name");?></th>
	    <td><b><?php echo $d["name"]; ?></b></td>
      </tr>
      <tr>
            <th><?php __("Description");?></th>
            <td><input name="description" type="text" size="30" value="<?php echo $d['description']; ?>" /></td>
      </tr>
	    <tr>
            <th><?php __("Target type");?></th>
            <td>
              <select name="target">
                <?php foreach ($dom->domains_type_target_values() as $k) { ?>
                  <option value="<?php echo $k ?>" <?php echo ($d['target']==$k)?"selected":"";?> ><?php echo $k;?></option>
                <?php } ?>
              </select>
            </td>
      </tr>
	    <tr>
            <th><?php __("Entry");?></th>
            <td><input name="entry" type="text" size="30" value="<?php echo $d['entry']; ?>" /></td>
      </tr>
	    <tr>
          	<th><?php __("Compatibility");?><br /><small><?php __("Enter comma-separated name of other types"); ?></small></th>
            <td><input name="compatibility" type="text" size="15" value="<?php echo $d['compatibility']; ?>" /></td>
      </tr>
	    <tr>
            <th><?php __("Enabled");?></th>
            <td>
              <select name="enable">
                <?php foreach ($dom->domains_type_enable_values() as $k) { ?>
                  <option value="<?php echo $k ?>" <?php echo ($d['enable']==$k)?"selected":"";?> ><?php __($k);?></option>
                <?php } ?>
              </select>
            </td>
      </tr>
	    <tr>
            <th><?php __("Do only a DNS entry");?></th>
            <td><input name="only_dns" type="checkbox" value="1" <?php cbox($d['only_dns']); ?> /></td>
      </tr>
	    <tr>
            <th><?php __("Domain must have our DNS");?></th>
            <td><input name="need_dns" type="checkbox" value="1" <?php cbox($d['need_dns']); ?> /></td>
      </tr>
	    <tr>
            <th><?php __("Is it an advanced option ?");?></th>
            <td><input name="advanced" type="checkbox" value="1" <?php cbox($d['advanced']); ?> /></td>
      </tr>
      <tr class="trbtn">
          <td colspan="2">
             <input type="submit" class="inb" name="submit" value="<?php __("Change this domain type"); ?>" />
	           <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='adm_domstype.php'"/>
          </td>
        </tr>
</table>
</form>

<?php } ?>

<?php include_once("foot.php"); ?>
