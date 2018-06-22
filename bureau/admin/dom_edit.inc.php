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
 * Form to edit / add subdomains, 
 * using domaine_type table to show a synamic form.
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");
include_once("head.php");

function sub_domains_edit($domain, $sub_domain_id=false) {
  global $admin, $msg, $oldid, $isedit;

$dom=new m_dom();
$dom->lock();

$r=$dom->get_domain_all($domain);
/*
if (! empty($sub)) {
   $sd=$dom->get_sub_domain_all($domain,$sub,$type,$value);
}
*/
$sd=$dom->get_sub_domain_all($sub_domain_id);

$type=$sd['type'];
$sub=$sd['name'];

$dom->unlock();

?>

<form action="dom_subdoedit.php" method="post" name="main" id="main">
   <?php csrf_get(); ?>
    <table class="dom-edit-table">
        <tr>
            <td>
            <input type="hidden" name="domain" value="<?php ehe($domain) ?>" />
            <input type="hidden" name="sub_domain_id" value="<?php echo intval($sub_domain_id); ?>" />
            <input type="hidden" name="action" value="add" />
  <?php
   if ($isedit) {
     __("Edit a subdomain:"); 
   } else {
     __("Create a subdomain:"); 
   }
?></td><td>
   <input type="text" class="int" name="sub" style="text-align:right" value="<?php ehe($sub); ?>" size="22" id="sub" /><span class="int" id="newsubname">.<?php ehe($domain); ?></span></td>
   <td></td>
        </tr>
    <?php 
      $first_advanced=true;
      $lst_advanced=array();
      foreach($dom->domains_type_lst() as $dt) { 
        // If this type is disabled AND it's not the type in use here, continue
        if ( $dt['enable'] == 'NONE' && strtoupper($type)!=strtoupper($dt['name'])) continue ;
        // If this type is only for ADMIN and i'm not an admin, continue (oldid is to check if we are an admin who take user identity)
        if (( $dt['enable'] == 'ADMIN') && (! $admin->enabled and ! intval($oldid))) continue;

        if ( (! $r['dns'] ) and ($dt['need_dns']) ) continue;
        $targval=(strtoupper($type)==strtoupper($dt['name']))?$sd['dest']:'';

        if ($dt['advanced']) {
          $lst_advanced[]=$dt['name'];
          if ($first_advanced) {
            $first_advanced=false;
            echo "<tr id='domtype_show' onClick=\"domtype_advanced_show();\"><td colspan='2'><a href=\"javascript:domtype_advanced_show();\"><b>+ "; __("Show advanced options"); echo "</b></a></td></tr>";
            echo "<tr id='domtype_hide' onClick=\"domtype_advanced_hide();\" style='display:none'><td colspan='2'><a href=\"javascript:domtype_advanced_hide();\"><b>- "; __("Hide advanced options"); echo "</b></a></td></tr>";
          }
        }
    ?>
    <tr id="tr_<?php echo $dt['name']; ?>">
      <td>
        <input type="radio" id="r_<?php ehe($dt['name']); ?>" class="inc" name="type" value="<?php ehe($dt['name']); ?>" <?php cbox(strtoupper($type)==strtoupper($dt['name'])); ?> OnClick="getElementById('t_<?php ehe($dt['name']); ?>').focus();"/>
        <label for="r_<?php ehe($dt['name']); ?>"><?php __($dt['description']); ?></label>
      </td>
      <td>
        <?php 

        switch ($dt['target']) {
          case "DIRECTORY": ?>
            <input type="text" class="int" name="t_<?php ehe($dt['name']); ?>" id="t_<?php ehe($dt['name']); ?>" value="<?php ehe($targval); ?>" size="28" onKeyPress="getElementById('r_<?php ehe($dt['name']); ?>').checked=true;" />
            <?php display_browser( $targval , "t_".$dt['name'] ); 
            break;
          case "URL": ?>
              <input type="text" class="int" name="t_<?php ehe($dt['name']); ?>" id="t_<?php ehe($dt['name']); ?>" value="<?php ehe( (empty($targval)?'http://':$targval) ); ?>" size="50" onKeyPress="getElementById('r_<?php ehe($dt['name']); ?>').checked=true;" />
              <small><?php __("(enter an URL here)"); ?></small><?php
              break;;
          case 'IP':?>
              <input type="text" class="int" name="t_<?php ehe($dt['name']); ?>" id="t_<?php ehe($dt['name']); ?>"  value="<?php ehe($targval); ?>" size="16" onKeyPress="getElementById('r_<?php ehe($dt['name']); ?>').checked=true;" />
            <small><?php __("(enter an IPv4 address, for example 192.168.1.2)"); ?></small><?php
              break;
          case 'IPV6':?>
            <input type="text" class="int" name="t_<?php ehe($dt['name']); ?>" id="t_<?php ehe($dt['name']); ?>" value="<?php ehe($targval); ?>" size="32" onKeyPress="getElementById('r_<?php ehe($dt['name']); ?>').checked=true;" /> 
            <small><?php __("(enter an IPv6 address, for example 2001:0910::0)"); ?></small><?php
              break;
          case 'TXT':?>
              <input type="text" class="int" name="t_<?php ehe($dt['name']); ?>" id="t_<?php ehe($dt['name']); ?>" value="<?php ehe($targval);?>" size="32" onKeyPress="getElementById('r_<?php ehe($dt['name']); ?>').checked=true;" />
            <small><?php __("(enter a TXT content for this domain)"); ?></small><?php
              break;
          case 'DOMAIN':?>
              <input type="text" class="int" name="t_<?php ehe($dt['name']); ?>" id="t_<?php ehe($dt['name']); ?>" value="<?php ehe($targval);?>" size="32" onKeyPress="getElementById('r_<?php ehe($dt['name']); ?>').checked=true;" /> 
            <small><?php __("(enter a domain name or subdomain)"); ?></small><?php
              break;
          case "NONE":
          default:
            break;
        } // switch ?>
      </td>
        <td>
<?php if ($dt['has_https_option']) { ?>

     <select class="inl" name="https_<?php ehe($dt['name']); ?>" id="https_<?php ehe($dt['name']); ?>">
            <option value="http"<?php selected((strtoupper($type)==strtoupper($dt['name']) && $sd["https"]=="http") || false); ?>><?php __("HTTP Only (redirect HTTPS to HTTP)"); ?></option>
            <option value="https"<?php selected((strtoupper($type)==strtoupper($dt['name']) && $sd["https"]=="http") || true); ?>><?php __("HTTPS Only (redirect HTTP to HTTPS)"); ?></option>
            <option value="both"<?php selected((strtoupper($type)==strtoupper($dt['name']) && $sd["https"]=="http") || false); ?>><?php __("Both HTTP and HTTPS hosted at the same place"); ?></option>
            </select>
<?php  } ?>
        </td>
    </tr>
    <?php } // foreach ?>

        <tr class="trbtn">
            <td colspan="2"><input type="submit" class="inb ok" name="add" onclick='return check_type_selected();' value="<?php
   if ($isedit) {
 __("Edit this subdomain");
} else {
 __("Add this subdomain");
} 
?>" />
<?php if ($isedit) { ?>
              <input type="button" class="inb cancel" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location = 'dom_edit.php?domain=<?php echo $domain; ?>'"/>
<?php } ?>
</td>
        </tr>
    </table>
</form>

<script type="text/javascript">

function check_type_selected() {
  if ( $('input[name=type]:radio:checked').val() ) {
    // there is a value
    var ll = $('input[name=type]:radio:checked').val();
    var tt = $('#t_'+ll);
    if ( tt.length == 0 ) {
      // this element do not exist, so OK
      return true;
    }
    if ( tt.val() == '' ) {
      alert("<?php __("Missing value for this sub-domain"); ?>");
      return false;
    }
  
    return true;
  }
  alert("<?php __("Please select a type for this sub-domain"); ?>");
  return false;
}

function domtype_advanced_hide() { 
  <?php foreach ($lst_advanced as $adv) echo "$(\"#tr_$adv\").hide();\n"?>
  $("#domtype_show").show();
  $("#domtype_hide").hide();
}
function domtype_advanced_show() { 
  <?php foreach ($lst_advanced as $adv) echo "$(\"#tr_$adv\").show();\n"?>
  $("#domtype_show").hide();
  $("#domtype_hide").show();
}

<?php if (isset($type) && in_array($type, $lst_advanced) ) { // if it's an edit of an advanced option, we need to show the advanced options ?>
  domtype_advanced_show();
<?php } else { ?>
  domtype_advanced_hide();
<?php } // if advanced ?>

</script>
<?php
} // sub_domains_edit
?>

