<?php
require_once("../class/config.php");
include_once("head.php");


# Function to create/edit subdomain
# Take the values of the subdomain in arguments

function sub_domains_edit($domain, $sub=false,$type=false,$value=false) {
  global $admin, $err, $oldid, $isedit;

$dom=new m_dom();
$dom->lock();
if (!$r=$dom->get_domain_all($domain)) {
  $error=$err->errstr();
}
/*
if (! empty($sub)) {
   if (!$sd=$dom->get_sub_domain_all($domain,$sub,$type,$value)) {
     $error=$err->errstr();
   }
}
*/
$sd=$dom->get_sub_domain_all($domain,$sub,$type,$value);

$dom->unlock();
?>

<form action="dom_subdoedit.php" method="post" name="main" id="main">
	<table border="0">
		<tr>
			<td>
			<input type="hidden" name="domain" value="<?php ehe($domain); ?>" />
			<input type="hidden" name="sub_old" value="<?php ehe($sub); ?>" />
			<input type="hidden" name="type_old" value="<?php ehe($type); ?>" />
			<input type="hidden" name="value_old" value="<?php ehe($value); ?>" />
			<input type="hidden" name="action" value="add" />
  <?php
   if ($isedit) {
     __("Edit a subdomain:"); 
   } else {
     __("Create a subdomain:"); 
   }
?></td><td>
<input type="text" class="int" name="sub" style="text-align:right" value="<?php ehe($sub); ?>" size="22" id="sub" /><span class="int" id="newsubname">.<?php echo $domain; ?></span></td>
		</tr>
    <?php 
      $first_advanced=true;
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
	    echo "<tr><td colspan=\"2\" class=\"advdom\"></td></tr>";
            echo "<tr id='domtype_show' onClick=\"domtype_advanced_show();\"><td colspan=2><a href=\"javascript:domtype_advanced_show();\"><b>+ "; __("Show advanced options"); echo "</b></a></td></tr>";
            echo "<tr id='domtype_hide' onClick=\"domtype_advanced_hide();\" style='display:none'><td colspan=2><a href=\"javascript:domtype_advanced_hide();\"><b>- "; __("Hide advanced options"); echo "</b></a></td></tr>";
	    echo "<tr><td colspan=\"2\" class=\"advdom\"></td></tr>";
          }
        }
    ?>
    <tr id="tr_<?php echo $dt['name']; ?>">
      <td>
        <input type="radio" id="r_<?php echo $dt['name']?>" class="inc" name="type" value="<?php echo $dt['name']; ?>" <?php cbox(strtoupper($type)==strtoupper($dt['name'])); ?> />
        <label for="r_<?php echo $dt['name']?>"><?php __($dt['description']); ?></label>
      </td>
      <td>
        <?php 

        switch ($dt['target']) {
          case "NONE":
          default:
            break;
          case "DIRECTORY": ?>
            <input type="text" class="int" name="t_<?php echo $dt['name']?>" id="t_<?php echo $dt['name']?>" value="<?php ehe($targval); ?>" size="28" />
						<?php display_browser( $targval , "main.t_".$dt['name'] ); ?>
						<?php
            break;
          case "URL": ?>
			        <input type="text" class="int" name="t_<?php echo $dt['name']?>" id="t_<?php echo $dt['name']?>" value="<?php ehe( (empty($targval)?'http://':$targval) ); ?>" size="50" />
              <small><?php __("(enter an URL here)"); ?></small><?php
              break;;
          case 'IP':?>
		        <input type="text" class="int" name="t_<?php echo $dt['name']?>" id="t_<?php echo $dt['name']?>"  value="<?php ehe($targval); ?>" size="16" />
            <small><?php __("(enter an IPv4 address, for example 192.168.1.2)"); ?></small><?php
              break;
          case 'IPV6':?>
            <input type="text" class="int" name="t_<?php echo $dt['name']?>" id="t_<?php echo $dt['name']?>" value="<?php ehe($targval); ?>" size="32" /> 
            <small><?php __("(enter an IPv6 address, for example 2001:0910::0)"); ?></small><?php
              break;
          case 'TXT':?>
		        <input type="text" class="int" name="t_<?php echo $dt['name']?>" id="t_<?php echo $dt['name']?>" value="<?php ehe($targval);?>" size="32" />
            <small><?php __("(enter a TXT informations for this domain)"); ?></small></td><?php
              break;
          case 'DOMAIN':?>
		        <input type="text" class="int" name="t_<?php echo $dt['name']?>" id="t_<?php echo $dt['name']?>" value="<?php ehe($targval);?>" size="32" /> 
            <small><?php __("(enter a domain name or subdomain)"); ?></small><?php
              break;
        } // switch ?>
      </td>
    </tr>
    <?php } // foreach ?>

		<tr class="trbtn">
			<td colspan="2"><input type="submit" class="inb" name="add" value="<?php
   if ($isedit) {
 __("Edit this subdomain");
} else {
 __("Add this subdomain");
} 
?>" /></td>
		</tr>
	</table>
</form>

<script type="text/javascript">
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

domtype_advanced_hide();

</script>

<?php
} // sub_domains_edit
?>

