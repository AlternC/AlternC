<?php
/*
 $Id: piwik_userlist.php, author: squidly
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
 Purpose of file: listing of mail accounts 
 ----------------------------------------------------------------------
*/

require_once("../class/config.php");
include_once("head.php");

if (isset($error) && $error) {
  	echo "<p class=\"error\">$error</p>";
}

//Mail creation.
if ($quota->cancreate("piwik")) { 
	$quotapiwik=$quota->getquota('piwik');
?>
<h3><?php __("Create a new piwik account");?></h3>
<form method="post" action="piwik_addaccount.php" id="main" name="addaccount" >
	<input type="text" class="int" name="account_name" size="20" id="account_name" maxlength="32" value="<?php if ($quotapiwik['u']==0) {echo $mem->user["login"];}?>"/>
	<input type="submit" name="submit" class="inb" value="<?php __("Create"); ?>" />
</form>

<br/>
<hr/>
<?php
} // cancreate piwik

if ($quotapiwik['u']>0) {
?>
<h3><?php __("Add a new website");?></h3>
<form method="post" action="piwik_addsites.php" id="main" name="addsites" >
	<input type="text" class="int" name="site_urls" size="50" id="site_name" maxlength="255" value="" placeholder="<?php __("URL of the website")?>"/>
	<input type="submit" name="submit" class="inb" value="<?php __("Create"); ?>" />
</form>

<br/>
<hr/>
<?php
} // cancreate piwik
?>

<h3><?php __("Existing Piwik accounts"); ?></h3>
<?php 

$userslist = $piwik->users_list();

// printVar($piwik->dev());

if (empty($userslist)){
	__("No existing Piwik users");
} else {
?>

<table class="tlist">
    <tr><th/><th><?php __("Username");?></th><th align=center><?php __("Connect"); ?></th></tr>
<?php

$col=1;
foreach ($userslist as $user ){
	$col=3-$col;
	?>
	<tr class="lst_clic<?php echo $col; ?>">
		<td><div class="ina"><a href="piwik_user_dodel.php?login=<?php echo urlencode($user->login); ?>"><img src="images/delete.png" alt="<?php __("Delete"); ?>" /><?php __("Delete"); ?></a></div></td>
	<td align=right><?php echo $user->login ?></td>
	   <td><div class="ina"><a href="<?php printf('%s?module=Login&action=logme&login=%s&password=%s', $piwik->url(), $user->login, $user->password); ?>" target="_blank"><?php __('Connect'); ?></a></td>
	</tr>
	<?php
} // foreach userlist 
} // empty userlist
?>

</table>
<?php include_once("foot.php"); ?>
