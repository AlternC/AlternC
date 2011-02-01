<?php
require_once("../class/config.php");
include_once("head.php");


# Function to create/edit subdomain
# Take the values of the subdomain in arguments

function sub_domains_edit($domain, $sub=false,$type=false,$value=false) {

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
  <?php __("Create a subdomain:"); ?></td><td>
<input type="text" class="int" name="sub" style="text-align:right" value="<?php ehe($sub); ?>" size="22" id="sub" /><span class="int" id="newsubname">.<?php echo $domain; ?></span></td>
		</tr>
    <?php foreach($dom->domains_type_lst() as $dt) { 
        if (! $dt['enable']) continue;
        if ( (! $r['dns'] ) and ($dt['need_dns']) ) continue;
        //if ( strtoupper($type)!=strtoupper($dt['name']) ) continue;
        $targval=(strtoupper($type)==strtoupper($dt['name']))?$sd['dest']:'';
    ?>
    <tr>
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
            <script type="text/javascript">
            <!--
            document.write("&nbsp;<input type=\"button\" name=\"bff\" onclick=\"browseforfolder('main.t_<?php echo $dt['name'];?>');\" value=\" <?php __("Choose a folder..."); ?> \" class=\"bff\">");
            //  -->
            </script><?php
            break;
          case "URL": ?>
			        <input type="text" class="int" name="t_<?php echo $dt['name']?>" id="t_<?php echo $dt['name']?>" value="<?php ehe($targval); ?>" size="50" />
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
            <small><?php __("(enter a server address or a subdomain)"); ?></small><?php
              break;
        } // switch ?>
      </td>
    </tr>
    <?php } // foreach ?>

		<tr class="trbtn">
			<td colspan="2"><input type="submit" class="inb" name="add" value="<?php __("Add this subdomain"); ?>" /></td>
		</tr>
	</table>
</form>

<?php
} // sub_domains_edit
?>

