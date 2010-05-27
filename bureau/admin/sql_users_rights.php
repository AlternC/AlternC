<?php
/*
 $Id: sql_users_rights.php,v 1.8 2006/02/16 16:26:28 nahuel Exp $
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
 Original Author of file: Nahuel ANGELINETTI
 Purpose of file: Manage the MySQL users of a member
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"id" => array ("request", "string", ""),
);
getFields($fields);

$r=$mysql->get_user_dblist($id);

?>
<h3><?php printf(_("MySQL Rights for %s"),$mem->user["login"]."_".$id) ?></h3>
<hr id="topbar"/>
<br />
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p><p>&nbsp;</p>";
	}

if ($r) {

echo "<p>"._("help_sql_users_rights_ok")."</p>";
?>

<form method="post" action="sql_users_dorights.php">
<input type="hidden" name="id" value="<?php echo $id; ?>" />
<table cellspacing="0" cellpadding="4">
   <tr>
     <th>&nbsp;</th>
     <th>SELECT</th>
     <th>INSERT</th>
     <th>UPDATE</th>
     <th>DELETE</th>
     <th>CREATE</th>
     <th>DROP</th>
     <th>REFERENCES</th>
     <th>INDEX</th>
     <th>ALTER</th>
     <th>CREATE_TMP_TABLE</th>
     <th>LOCK</th>
  </tr>

<?php
$col=1;
for($i=0;$i<count($r);$i++) {
  $val=$r[$i];
  $col=3-$col;
?>
	<tr class="lst<?php echo $col; ?>">
          <td><strong><?php echo $mem->user["login"].($val["db"]?"_":"").$val["db"] ?></strong></td>
	  <td align="center">
            <input type="checkbox" class="inc" id="<?php echo $val["db"]; ?>_select" name="<?php echo $val["db"]; ?>_select"<?php if($val["select"]=="Y") echo " checked=\"checked\""; ?> />
          </td>
	  <td align="center">
            <input type="checkbox" class="inc" id="<?php echo $val["db"]; ?>_insert" name="<?php echo $val["db"]; ?>_insert"<?php if($val["insert"]=="Y") echo " checked=\"checked\""; ?> />
          </td>
	  <td align="center">
            <input type="checkbox" class="inc" id="<?php echo $val["db"]; ?>_update" name="<?php echo $val["db"]; ?>_update"<?php if($val["update"]=="Y") echo " checked=\"checked\""; ?> />
          </td>
	  <td align="center">
            <input type="checkbox" class="inc" id="<?php echo $val["db"]; ?>_delete" name="<?php echo $val["db"]; ?>_delete"<?php if($val["delete"]=="Y") echo " checked=\"checked\""; ?> />
          </td>
	  <td align="center">
            <input type="checkbox" class="inc" id="<?php echo $val["db"]; ?>_create" name="<?php echo $val["db"]; ?>_create"<?php if($val["create"]=="Y") echo " checked=\"checked\""; ?> />
          </td>
	  <td align="center">
            <input type="checkbox" class="inc" id="<?php echo $val["db"]; ?>_drop" name="<?php echo $val["db"]; ?>_drop"<?php if($val["drop"]=="Y") echo " checked=\"checked\""; ?> />
          </td>
	  <td align="center">
            <input type="checkbox" class="inc" id="<?php echo $val["db"]; ?>_references" name="<?php echo $val["db"]; ?>_references"<?php if($val["references"]=="Y") echo " checked=\"checked\""; ?> />
          </td>
	  <td align="center">
            <input type="checkbox" class="inc" id="<?php echo $val["db"]; ?>_index" name="<?php echo $val["db"]; ?>_index"<?php if($val["index"]=="Y") echo " checked=\"checked\""; ?> />
          </td>
	  <td align="center">
            <input type="checkbox" class="inc" id="<?php echo $val["db"]; ?>_alter" name="<?php echo $val["db"]; ?>_alter"<?php if($val["alter"]=="Y") echo " checked=\"checked\""; ?> />
          </td>
	  <td align="center">
            <input type="checkbox" class="inc" id="<?php echo $val["db"]; ?>_create_tmp" name="<?php echo $val["db"]; ?>_create_tmp"<?php if($val["create_tmp"]=="Y") echo " checked=\"checked\""; ?> />
          </td>
	  <td align="center">
            <input type="checkbox" class="inc" id="<?php echo $val["db"]; ?>_lock" name="<?php echo $val["db"]; ?>_lock"<?php if($val["lock"]=="Y") echo " checked=\"checked\""; ?> />
          </td>
	</tr>
<?php


 }
?>
<tr><td colspan="5">
   <input type="submit" value="<?php __("Apply"); ?>" class="inb" />
</td></tr>
</table>
</form>
<p>&nbsp;</p>
<?php } ?>
<?php include_once("foot.php"); ?>
