<?php
/*
 mail_properties.php, author: squidly
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
 Purpose of file: Create a new mail account
 ----------------------------------------------------------------------
*/

require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"mail_id" => array ("request", "integer", ""),
);
getFields($fields);

if ( ! $mail_id ) die("Error on mail_id");

$details = $mail->mail_get_details($mail_id);
if (!$details) 	die("Error on mail details");
echo "<h3>";
echo sprintf(_("Edition of <b>%s</b>")."<br />",$details['address_full']);
echo "</h3>";?>

<hr/>
<h3><?php __("Select your action");?></h3>
<table class="tlist">
  <tr id='globalmail_title'><td colspan=2 class='advdom'><b><a href="javascript:toogle_properties('globalmail_');"><font id='globalmail_minus'>-</font><font id='globalmail_plus' style='display:none'>+</font> <?php __("Global options");?></a></b></td></tr>
  <tr id="globalmail_"><td>
    <?php $mail->form($mail_id); ?>
  </td>
  </tr>

<?php
$properties = $mail->list_properties($mail_id);
$prev_desc="";
$lst_toggle=Array();
$lst_advanced=Array();
$first_advanced=true;
$col=1;
foreach ($properties as $k => $v ) {
  $col=3-$col;

  if (isset($v['advanced']) && $v['advanced']) {
    $lst_advanced[]=md5($v['short_desc']);
    if ($first_advanced) {
      $col=2;
      $first_advanced=false; ?>
      <tr><td colspan="2" class="advdom"><hr/></td></tr>
      <tr><td colspan="2" class="advdom"></td></tr>
      <tr id='mailproperties_show' style='display:none'><td colspan=2>
        <a href="javascript:toggle_mailadv();"><b>+&nbsp;<?php __("Show advanced options"); ?></b></a></td>
      </tr>
      <tr id='mailproperties_hide'><td colspan=2>
        <a href="javascript:toggle_mailadv();"><b>-&nbsp;<?php __("Hide advanced options"); ?></b></a></td>
      </tr>
      <tr><td colspan="2" class="advdom"></td></tr>
    <?php
    }
  }

  $ok = true;
  if ( $v['pass_required'] && ! $details['password'] ) {
    $ok = false;
  }

/*
  $url=$v['url'];
  $cl = ($ok)?"lst_clic$col":"lst_$col";

  echo "<tr id='mp_$k' class=\"$cl\"";
  if ($ok) { 
    echo 'onclick="javascript:window.location.href=\'';
    echo addslashes($url)."';\"";
  }
  echo " ><td>";
  echo "<b>".$v['short_desc']."</b><br/>";
  echo $v['human_desc'];
*/
  if ($v['short_desc'] != "$prev_desc" ) {
    $prev_desc=$v['short_desc'];
    $lst_toggle[]=md5($prev_desc);
    echo "<tr id='".md5($prev_desc)."title'><td colspan=2 class='advdom'><b><a href=\"javascript:toogle_properties('".md5($prev_desc)."');\"><font id='".md5($prev_desc)."minus'>-</font><font id='".md5($prev_desc)."plus' style='display:none'>+</font> ".$v['short_desc']."</a></b></td></tr>";
  }

  echo "<tr id=".md5($prev_desc)."><td>";
  if (!$ok) {
    echo "<br/><font color='red'>";
    __("Unavaible, you need to set a password before");
    echo "</font>";
  } else {
    $hooks->invoke('form', $v['form_param'], Array($v['class']));
//    $$v['class']->form($v['form_param']);
  } 
  echo "<tr><td>";
  ?>
  </td>
  <td>
<!-- 
    <?php if ($ok) { ?>
    <div class="ina"><a href="<?php echo $url ?>"><img src="images/edit.png" alt="<?php __("Edit"); ?>" /><?php __("Edit"); ?></a></div>
    <?php } // if ok ?>
-->
  </td>
  </tr>
<?php
} // foreach


?>
</table>
<script type="text/javascript">

function toogle_properties(id) {
  $('#'+id).toggle();
  $('#'+id+'plus').toggle();
  $('#'+id+'minus').toggle();
}

function toggle_mailadv(){
  $("#mailproperties_show").toggle();
  $("#mailproperties_hide").toggle();
<?php foreach($lst_advanced as $o) { ?>
//  $("#<?php echo $o;?>").toggle();
  $("#<?php echo $o;?>title").toggle();
<?php } ?>
}

toogle_properties('globalmail_');
<?php 
foreach ($lst_toggle as $t) { ?>
toogle_properties('<?php echo $t ?>');
<?php } //foreach toggle ?>

toggle_mailadv();
</script>
<?php
include_once("foot.php");
?>
