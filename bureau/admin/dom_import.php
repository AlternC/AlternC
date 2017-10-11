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
 * Import of DNS informations from an existing zone, transformation into AlternC's system zone.
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"domain"          => array ("post", "string", ""),
	"zone"            => array ("post", "string", ""),
	"save"            => array ("post", "integer", "0"),
	"detect_redirect" => array ("post", "integer", "0"),
);
getFields($fields);
$domain=trim($domain);

?>
<h3><?php __("Import a DNS zone"); ?></h3>
<hr id="topbar"/>
<br />

<?php
if ( !empty($zone) && empty($domain) ) {
  $msg->raise("ALERT", "dom", _("The domain field seems to be empty"));
}

echo $msg->msg_html_all();
?>

<?php if ( !empty($zone) ) { 
if ( !empty($domain) ) {
  __("Here is my proposition. Modify your zone until my proposition seems good to you"); ?>
  <table class="tlist">
  <tr><th colspan=3><h2><?php printf(_("Working on %s"),$domain); ?></h2></th></tr>
  <tr>
    <th width="50%"><?php __("Zone"); ?></th><th width="50%"><?php __("Generated entry"); ?></th><?php if ($save) {echo "<th>"._("Result")."</th>"; } ?>
  </tr>
  <tbody>
  <?php foreach ($dom->import_manual_dns_zone($zone, $domain, $detect_redirect, $save) as $val) {  
    if (empty($val)) continue;
    //echo "<tr><td>"; print_r($val); 
    echo "<tr class='";
    if ($save) {
      echo "tab-".($val['did_it']==1?'ok':'err');
    } else {
      echo "tab-".$val['status'];
    }
    echo "'><td>".$val['entry_old']."</td><td>".$val['comment']."</td>";
    if ($save) { echo "<td>".($val['did_it']==1?_("OK"):_("ERROR"))."</td>"; }
    echo "</tr>\n";
  } // foreach
  ?>
  </tbody>
  </table>
<?php 
} // empty domain
echo "<hr/>";
} // empty $zone 


if ($save) {
  echo '<span class="inb"><a href="dom_edit.php?domain='.urlencode($domain).'" >'._("Click here to continue").'</a></span>';
} else {
?>


<form method="post" action="dom_import.php">
    <?php csrf_get(); ?>
  <table>
    <tr>
      <td>
        <label for="domain"><?php __("Enter the domain name you want to import") ; ?></label>
      </td>
      <td>
        <input type="text" size="40" name="domain" value="<?php ehe($domain); ?>" />
      </td>
    </tr>
    <tr>
      <td>
        <label for="detect_redirect"><?php __("Do you want to detect the redirection?"); ?></label>
      </td>
      <td>
        <input name="detect_redirect" type="checkbox" value="1" <?php cbox($detect_redirect); ?> />
      </td>
    </tr>
    <tr>
      <td colspan="2">

        <p>
          <label for="zone"><?php 
          __("Paste your existing DNS zone here.");
          echo '<br/>';
          __("If you don't know what it is, don't submit this form.");?></label>
          <textarea cols=100 rows=20 name="zone"><?php echo $zone; ?></textarea>
        </p>

        <?php if ( ! empty($zone) && ! empty($domain) ) { // only if you reviewed ?>
          <label><?php __("Do you want to import the zone as it?"); ?></label>
          <input name="save" type="checkbox" value="1" />
        <?php } ?>

        <p><input type="submit" name="submit" class="inb ok" value="<?php __("Submit"); ?>" /></p>
      </td>
    </tr>
  </table>
</form>

<?php 
} // $save

include_once("foot.php"); 

?>
