<?php
/*
 $Id: mail_edit.php,v 1.6 2006/01/12 01:10:48 anarcat Exp $
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
 Purpose of file: Edit a mailbox.
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
if (!$admin->enabled) {
    __("This page is restricted to authorized staff");
    exit();
}

include_once("head.php");

$fields = array (
    "id"            => array ("request", "integer", ""),
    "name"          => array ("request", "string", ""),
    "description"   => array ("request", "string", ""),
    "ask_dest"      => array ("request", "boolean", ""),
    "entry"         => array ("request", "string", ""),
    "compatibility" => array ("request", "string", ""),
);
getFields($fields);


if (! $d=$dom->domains_type_get($id)) {
	$error=$err->errstr();
	echo $error;
} else {
?>

<h3><?php __("Edit a domains type"); ?> </h3>
<hr id="topbar"/>
<br />
<?php
if ($error_edit) {
	echo "<p class=\"error\">$error_edit</p>";
	$error_edit="";

} ?>

<form action="adm_domstypedoedit.php" method="post" name="main" id="main">
    <input type="hidden" name="id" value="<?php echo $d['id']; ?>" />
    <table class="tedit">
	    <tr>
            <th><?php __("Name");?></th>
            <td><input name="name" type=text size="15" value="<?php echo $d['name']; ?>" /></td>
        </tr>
	    <tr>
            <th><?php __("Description");?></th>
            <td><input name="description" type=text size="30" value="<?php echo $d['description']; ?>" /></td>
        </tr>
	    <tr>
            <th><?php __("Ask destination ?");?></th>
            <td><input name="ask_dest" type=checkbox value=1 <?php echo $d['ask_dest']?"checked":""; ?>/></td>
        </tr>
	    <tr>
            <th><?php __("Entry");?></th>
            <td><input name="entry" type=text size="30" value="<?php echo $d['entry']; ?>" /></td>
        </tr>
	    <tr>
            <th><?php __("Compatibility");?></th>
            <td><input name="compatibility" type=text size="15" value="<?php echo $d['compatibility']; ?>" /></td>

        </tr>
        <tr class="trbtn">
          <td colspan="2">
             <input type="submit" class="inb" name="submit" value="<?php __("Change this domains type"); ?>" />
          </td>
        </tr>
</table>
</form>

<?php } ?>
