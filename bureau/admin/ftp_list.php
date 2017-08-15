<?php
/*
 $Id: ftp_list.php,v 1.5 2003/06/10 13:16:11 root Exp $
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
 Purpose of file: List ftp accounts of the user.
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"domain"    => array ("request", "string", ""),
);
getFields($fields);

$noftp=false;
if (!$r=$ftp->get_list($domain)) {
	$noftp=true;
}

?>
<h3><?php __("FTP accounts list"); ?></h3>
<hr id="topbar"/>
<br />
 
<?php
echo $msg->msg_html_all();

if ($quota->cancreate("ftp")) { ?>
  <p>
   <span class="inb add"><a href="ftp_edit.php?create=1"><?php __("Create a new ftp account"); ?></a></span> 
  </p>
<?php
}

if ($noftp) {
  $mem->show_help("ftp_list_no");
  include_once("foot.php");
  exit;
} 
?>

<form method="post" action="ftp_del.php">
   <?php csrf_get(); ?>
<table class="tlist" id="ftp_list_table">
<thead>
  <tr><th colspan="2"> </th><th><?php __("Enabled"); ?></th><th><?php __("Username"); ?></th><th><?php __("Folder"); ?></th></tr>
</thead>
<?php
reset($r);
while (list($key,$val)=each($r)) { ?>
	<tr class="lst">
		<td align="center"><input type="checkbox" class="inc" id="del_<?php ehe($val["id"]); ?>" name="del_<?php ehe($val["id"]); ?>" value="<?php ehe($val["id"]); ?>" /></td>
<td><div class="ina edit"><a href="ftp_edit.php?id=<?php eue($val["id"]); ?>"><?php __("Edit"); ?></a></div></td>

		<td><a href='ftp_switch_enable.php?id=<?php eue($val['id']); echo '&amp;status='.( ($val['enabled'])?'0':'1' ) ;?>' onClick='return confirm("<?php __("Are you sure you want to change his status?"); ?>");'><?php 
if ( $val['enabled']) {
  echo "<img src='images/check_ok.png' alt=\""._("Enabled")."\"/>";
  echo "<span style='display:none;'>ENABLED</span>"; // for tablesorter
} else {
  echo "<img src='images/check_no.png' alt=\""._("Disabled")."\"/>";
  echo "<span style='display:none;'>DISABLED</span>"; // for tablesorter
}

?></a></td>
    <td><label for="del_<?php ehe($val["id"]); ?>"><?php ehe($val["login"]); ?></label>
                  <input type="hidden" name="names[<?php ehe($val['id']); ?>]" value="<?php ehe($val["login"]); ?>" />
                </td>
		<td>
<a href="bro_main.php?R=<?php eue(str_replace(getuserpath(),'', $val["dir"])); ?>"><code><?php ehe(substr($val["dir"],strlen(getuserpath()) )); ?></code></a>
                  <?php if ( ! file_exists($val['dir'])) { echo " <span class=\"alerte\">"._("Directory not found")."</span>"; } ?>
                </td>
	</tr>
<?php
	}
?>
</table>
<p><input type="submit" name="submit" class="inb delete" value="<?php __("Delete checked accounts"); ?>" /></p>
</form>

<br/>
<hr/>

<h3><?php __("FTP configuration information");?></h3>

<?php __("Here are some configuration information you will need to configure your FTP application.");?>

<ul>
  <li><?php echo '<b>'._("Server:").'</b> '.$ftp->srv_name; ?></li>
  <li><?php echo '<b>'._("FTP mode for data transfer:").'</b> '._("passive");?></li>
  <li><?php echo '<b>'._("User/password:").'</b> '._("the one you specified when you created the account. You can edit them in the panel.");?></li>
</ul>

<?php
$mem->show_help("ftp_list");
?>
<script type="text/javascript">

$(document).ready(function() 
    { 
        $("#ftp_list_table").tablesorter(); 
    } 
); 
</script>

<?php include_once("foot.php"); ?>
