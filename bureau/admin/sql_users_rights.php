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
if (isset($error) && $error) {
	echo "<p class=\"error\">$error</p><p>&nbsp;</p>";
}

if ($r) {

?>

<form method="post" action="sql_users_dorights.php">
<input type="hidden" name="id" value="<?php echo $id; ?>" />
<table cellspacing="0" cellpadding="4">
   <tr>
     <th>&nbsp;</th>
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
$sql_right=Array('select','insert','update','delete','create','drop','references','index','alter','create_tmp','lock');
for($i=0;$i<count($r);$i++) {
  $val=$r[$i];
  $col=3-$col;
?>
	<tr class="lst<?php echo $col; ?>">
    <td><strong><?php echo $mem->user["login"].($val["db"]?"_":"").$val["db"] ?></strong></td>
    <td><a href="javascript:inverse_sql_right('<?php echo htmlentities($val["db"]);?>');"><?php __('Reverse selection');?></a></td>
    <?php foreach($sql_right as $sr) { ?>
	  <td align="center">
            <input type="checkbox" class="inc" id="<?php echo $val["db"]."_$sr"; ?>" name="<?php echo $val["db"]."_$sr"; ?>"<?php if($val[$sr]=="Y") echo " checked=\"checked\""; ?> />
    </td>
    <?php } ?>
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
<script type="text/javascript">
function inverse_sql_right(db) {
  <?php foreach($sql_right as $sr) { ?>
    if ( document.getElementById(db+'_<?php echo $sr;?>').checked ) {
      document.getElementById(db+'_<?php echo $sr;?>').checked=false;
    } else {
      document.getElementById(db+'_<?php echo $sr;?>').checked=true;
    }
  <?php } ?>
}

</script>
<?php } ?>
<?php include_once("foot.php"); ?>
