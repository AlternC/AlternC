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
 * Manages global variables of AlternC
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/  
 */

require_once("../class/config.php");

if (!$admin->enabled) {
	$msg->raise("ERROR", "admin", _("This page is restricted to authorized staff"));
	echo $msg->msg_html_all();
	exit();
}

$conf = variable_init();
foreach ($conf as $name => $val) {
  if (isset($GLOBALS['_POST'][$name])) {
    variable_set($name, $GLOBALS['_POST'][$name]);
  }
}

include_once ("head.php");

?>
<h3><?php __("Configure AlternC variables"); ?></h3>
<hr id="topbar"/>
<br />
<?php echo $msg->msg_html_all(); ?>
<p>
<?php __("Here are the internal AlternC variables that are currently being used."); ?>
</p>

<form method="post" action="adm_variables.php">
  <?php csrf_get(); ?>
<table border="0" cellpadding="4" cellspacing="0" class='tlist'>
<tr><th><?php __("Names"); ?></th><th><?php __("Value"); ?></th><th><?php __("Comment"); ?></th></tr>
<?php

foreach( variables_list() as $vars) {  ?>

 <tr class="lst">
    <td><?php ehe($vars['name']); ?></td>
 <td><input type="text" class="int" name="<?php ehe($vars['name']); ?>" value="<?php ehe($vars['value']); ?>" style="width: 200px"/></td>
    <td><?php ehe($vars['comment']); ?></td>
 </tr>
<?php } ?>
</table>
<p><input type="submit" class="inb" value="<?php __("Save variables"); ?>" /></p>
</form>
<?php include_once("foot.php"); ?>
