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
 * Manages the catch-all account of a domain for a user 
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */ 

 require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"domain_id" => array("request","integer",null),
	"target_type" => array("post", "string", null),
	"target_mail" => array("post", "string", null),
	"target_domain" => array("post", "string", null),
);
getFields($fields);

if (is_null($domain_id)) { 
  $msg->raise("ERROR", "mail", __("Problem with the domain", "alternc", true));
  include_once("mail_list.php"); 
  exit();
}

if (!is_null($target_type)) {
  switch ($target_type) {
    case "none":
      if ($mail->catchall_del($domain_id))
        $msg->raise("INFO", "mail", __("Catchall successfully deleted", "alternc", true));

      require_once("mail_list.php");
      exit();
      break;
    case "domain":
      if ($mail->catchall_set($domain_id, $target_domain)) {
	$msg->raise("INFO", "mail", __("Catchall successfully updated", "alternc", true));
	require_once("mail_list.php");
	exit();
      }
      break;
    case "mail":
      if ($mail->catchall_set($domain_id, $target_mail)) {
	$msg->raise("INFO", "mail", __("Catchall successfully updated", "alternc", true));
	require_once("mail_list.php");
	exit();
      }
      break;
    default:
      $error=__("Unknown target type", "alternc", true);
  }
}

$catch=$mail->catchall_getinfos($domain_id);
  
?>
<h3><?php printf(__("Manage catch-all configuration of %s", "alternc", true),$catch["domain"]); ?></h3>
<hr id="topbar"/>
<br />

<?php
echo $msg->msg_html_all();

__("You can choose what to do with emails sent to unexisting address of this domain");
?>
<br/><br/>

<form action="mail_manage_catchall.php?domain_id=<?php echo $domain_id;?>" method="post" name="main" id="main">
   <?php csrf_get(); ?>
<table class="tedit">

  <tr>
    <th colspan="3"><b><?php __("No catch-all");?></b></th>
  </tr>
  <tr>
    <td width="1px"><input type="radio" name="target_type" id='target_type_none' value="none" <?php if ($catch['type']=='none') {echo 'checked="checked"';}?> /></td>
    <td colspan='2' style="width: 50%; text-align: justify"><label for='target_type_none'><?php echo __("No catch-all for this domain.");?></label></td>
  </tr>

  <tr>
    <th colspan="3"><b><?php __("Redirect to same address on a different domain");?></b></th>
  </tr>
  <tr>
    <td width="1px"><input type="radio" name="target_type" id='target_type_domain' value="domain" <?php if ($catch['type']=='domain') {echo 'checked="checked"';}?> /></td>
    <td style="width: 50%; text-align: justify"><label for='target_type_domain'><?php echo sprintf(__("Mails sent to john.doe@%s will be redirect to john.doe@anotherdomain.tld", "alternc", true),$catch['domain']);?></label></td>
    <td>
      <p>
	<i><?php __("Enter the 'target' domain"); ?></i><br/>
        <input type="text" id="target_domain" name="target_domain" value="<?php if($catch['type']=='domain') { ehe(substr($catch['target'],1)); } ?>" placeholder="<?php __("example.tld");?>" />
      </p>
	<i><?php __("Or choose one of your own"); ?></i>
        <ul>
          <?php foreach ( $dom->enum_domains() as $d) { if ($d==$catch['domain']) {continue;} echo "<li><a href=\"javascript:set_target_domain_value('".addslashes($d)."');\">$d</a></li>"; } ?>
        </ul>
    </td>
  </tr>

  <tr>
    <th colspan="3"><b><?php __("Redirect to a specific email");?></b></th>
  </tr>
  <tr>
    <td width="1px"><input type="radio" name="target_type" id='target_type_mail' value="mail" <?php if ($catch['type']=='mail') { echo 'checked="checked"'; } ?> /></td>
    <td style="width: 50%; text-align: justify"><label for='target_type_mail'><?php echo sprintf(__("Mails sent to an unexisting email on '@%s' will be redirect to user@example.tld.", "alternc", true),$catch['domain']);?></label></td>
    <td>
      <p>
        <input type="text" name="target_mail" size="30" value="<?php if($catch['type']=='mail') { ehe($catch['target']); } ?>" placeholder="<?php __("john.doe@example.tld");?>" />
      </p>
    </td>
  </tr>
</table>

<br/>

<input type="submit" class="inb" name="submit" value="<?php __("Save"); ?>" />
<input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="window.history.go(-1);"/>

</form>

<script type="text/javascript">
  function set_target_domain_value(value) {
    $('#target_domain').val(value);
    $('#target_type_domain').prop('checked', true);
  }
</script>

<?php include_once("foot.php"); ?>
