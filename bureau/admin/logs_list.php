<?php
/*
 $Id: log_list.php,v 1.8 2006/02/16 16:26:28 benjamin Exp $
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
 Original Author of file: Lerider Steven
 Purpose of file: Manage the log listing of a user
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$list=$log->list_logs_directory_all($log->get_logs_directory());
?>
<h3><?php __("Logs Listing"); ?></h3>
<hr id="topbar"/>
<br />
<?php
if (isset($error) && $error) {
	echo "<p class=\"error\">$error</p>";
}
if(!$list || empty($list['dir'])){
	echo "<p class=\"error\">"._("You have no web logs to list a the moment.")."</p>";  
	include_once('foot.php');
	exit;
}
?>
<p>
<?php __("Here are web logs of your account.<br/>You can download them to do specific extract and statistics.");?>
</p>
<table class="tlist">
<tr><th><?php __("Name");?></th><th align=center><?php __("Creation Date"); ?></th><th><?php __("Size"); ?></th><th><?php __("Download link");?></th></tr>
<?php

$col=1;
//listing of every mail of the current domain.
echo "<pre>";
while (list($key,$val)=each($list)){
	$col=3-$col;
	foreach($val as $k => $v){
	?>
	<tr>
	<td><?php echo $v['name']; ?></td>	
	<td><?php echo $v['creation_date']; ?></td>	
	<td><?php echo format_size($v['filesize']); ?></td>	
	<td><?php echo "<a href=\"".$v['downlink']."\">"._("Download")."</a>";?></td>
	</tr>

<?php
}
}
?>
</table>
