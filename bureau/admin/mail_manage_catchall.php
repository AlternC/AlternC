<?php
/*
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2000-2012 by the AlternC Development Team.
 https://alternc.org/
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
 Purpose of file: Manage catch-all
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"domain_id" => array("get","integer",null),
	"target_type" => array("post", "string", null),
	"target_mail" => array("post", "string", null),
	"target_domain" => array("post", "string", null),
);
getFields($fields);

if (is_null($domain_id)) { 
  echo "<p class=\"error\">";
  __("Problem with the domain");
  echo"</p>";
  include_once("foot.php"); 
  exit();
}

if (!is_null($target_type)) {
  switch ($target_type) {
    case "none":
      $mail->catchall_del($domain_id);
      break;
    case "domain":
      $mail->catchall_set($domain_id, $target_domain);
      break;
    case "mail":
      $mail->catchall_set($domain_id, $target_mail);
      break;
    default:
      $error=_("Unknown target type");
  }
}

$catch=$mail->catchall_getinfos($domain_id);
  
?>
<h3><?php printf(_("Manage catch-all configuration of %s"),$catch["domain"]); ?></h3>
<hr id="topbar"/>
<br />

<?php
if (isset($error)) {
  	echo "<p class=\"error\">$error</p>";
}

__("You can choose what to do with emails sent to unexisting address of this domain");
?>
<br/><br/>

<form action="mail_manage_catchall.php?domain_id=<?php echo $domain_id;?>" method="post" name="main" id="main">
<table class="tedit">

  <tr>
    <th colspan="3"><b><?php __("No catch-all");?></b></th>
  </tr>
  <tr>
    <td width=1px><input type="radio" name="target_type" id='target_type_none' value="none" <?php if ($catch['type']=='none') {echo 'checked';}?> /></td>
    <td colspan=2 style="width: 50%; text-align: justify"><label for='target_type_none'/><?php echo __("No catch-all for this domain.");?></label></td>
  </tr>

  <tr>
    <th colspan="3"><b><?php __("Redirect to same address on a different domain");?></b></th>
  </tr>
  <tr>
    <td width=1px><input type="radio" name="target_type" id='target_type_domain' value="domain" <?php if ($catch['type']=='domain') {echo 'checked';}?> />
    <td style="width: 50%; text-align: justify"><label for='target_type_domain'/><?php echo sprintf(_("Mails sent to john.doe@%s will be redirect to john.doe@anotherdomain.tld"),$catch['domain']);?></label></td>
    <td>
      <p>
        <input type="text" id="target_domain" name="target_domain" value="<?php if($catch['type']=='domain') { echo substr($catch['target'],1); } ?>" placeholder="example.tld" />
        <ul>
          <?php foreach ( $dom->enum_domains() as $d) { if ($d==$catch['domain']) {continue;} echo "<li><a href=\"javascript:set_target_domain_value('".addslashes($d)."');\">$d</a></li>"; } ?>
        </ul>
      </p>
    </td>
  </tr>

  <tr>
    <th colspan="3"><b><?php __("Redirect to a specific email");?></b></th>
  </tr>
  <tr>
    <td width=1px><input type="radio" name="target_type" id='target_type_mail' value="mail" <?php if ($catch['type']=='mail') {echo 'checked';}?> />
    <td style="width: 50%; text-align: justify"><label for='target_type_mail'/><?php echo sprintf(_("Mails sent to an unexisting email on '@%s' will be redirect to user@example.tld."),$catch['domain']);?></label></td>
    <td>
      <p>
        <input type="text" name="target_mail" value="<?php if($catch['type']=='mail') { echo $catch['target']; } ?>" placeholder="john.doe@example.tld" />
      </p>
    </td>
  </tr>


  <tr>
    <td colspan=3 >
      <input type="submit" class="inb" name="submit" value="<?php __("Save"); ?>" />
      <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="window.history.go(-1);"/>
    </td>
  </tr>
</table>
</form>
<script type="text/javascript">
  function set_target_domain_value(value) {
    $('#target_domain').val(value);
    $('#target_type_domain').prop('checked', true);
  }
</script>

<?php include_once("foot.php"); ?>
