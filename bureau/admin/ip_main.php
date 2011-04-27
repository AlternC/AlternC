<?php 
require_once("../class/config.php");
include_once("head.php");


$fields = array (
  "delete_id"    => array ("get",  "integer", ""),
  "is_subnet"    => array ("post", "string", ""),
  "id"           => array ("post", "integer", 0),
  "ip"           => array ("post", "string", ""),
  "subnet"       => array ("post", "integer" ,0),
  "infos"        => array ("post", "string" ,""),
);
getFields($fields);

if (!empty($delete_id)) {
  if (! $authip->ip_delete($delete_id)) {
    $error="Error during recording";
  }
}

if (!empty($is_subnet) && !empty($ip)) {
  if (! $authip->ip_save($id, $ip, $subnet, $infos)) {
    $error="Error during recording";
  }
}

?>

<h3><?php __("Access security"); ?></h3>
<hr id="topbar"/>
<br />

<?php if ($error) { ?>
  <p class="error"><?php echo $error ?></p>
<?php } ?>

<table>
  
  <tr>
    <th colspan=2><?php __("IP address");?></th>
  </tr>
  <tr>
    <td valign=top>
      <table>
      <tr><th><?php __("Type"); ?></th><th><?php __("IP"); ?></th><th><?php __("Informations"); ?></th><th colspan=2/></tr>
      <?php foreach($authip->list_ip() as $i) {
        if (checkip($i['ip'])) {
          if ($i['subnet']==32) {
            $txt="Address IPv4";
            $ip="${i['ip']}";
          } else {
            $txt="Subnet IPv4";
            $ip="${i['ip']}/${i['subnet']}";
          }
        } elseif (checkipv6($i['ip'])) {
          if ($i['subnet']==128) {
            $txt="Address IPv6";
            $ip="${i['ip']}";
          } else {
            $txt="Subnet IPv6";
            $ip="${i['ip']}/${i['subnet']}";
          }
        } 
        echo "<tr><td>$txt</td><td>$ip</td><td>${i['infos']}</td>";
        ?>
        <td><div class="ina"><a href="javascript:edit_ip(<?php echo "'".htmlentities($i['id'])."','".htmlentities($i['ip'])."','".htmlentities($i['subnet'])."','".htmlentities($i['infos'])."'"; ?>);"><img src="images/edit.png" alt="<?php __("Edit"); ?>" /><?php __("Edit"); ?></a></div></td>
        <td><div class="ina"><a href="ip_main.php?delete_id=<?php echo urlencode($i["id"]) ?>"><img src="images/delete.png" alt="<?php __("Delete"); ?>" /><?php __("Delete"); ?></a></div></td>
        </tr>

      <?php } ?>
      </table>
    </td>
    <td valign=top>
      <fieldset>
        <legend><a href='javascript:switch_from_add_ip();'><?php __("Add an IP");?></a> - <a href="javascript:edit_ip('','<?php echo htmlentities($_SERVER['REMOTE_ADDR'])."','".(checkip($_SERVER['REMOTE_ADDR'])?"32":"128")."','Home IP'";?>);" ><?php echo __("Add my actual IP"); ?></a></legend>
        <span id="form_add_ip">
        <form method="post" action="ip_main.php" name="main" id="main">
          <input type="hidden" name="id" value="" id="edit_id" >
          <p><?php __("Do you want to add");?><br/>
            <input type="radio" name="is_subnet" value="no" id="is_subnet_no" checked OnClick=$("#subnet_info").hide(); >
            <label for="is_subnet_no"><?php __("Only 1 IP address");?></label><br/>
            <input type="radio" name="is_subnet" value="yes" id="is_subnet_yes" OnClick=$("#subnet_info").show(); >
            <label for="is_subnet_yes"><?php __("An entire subnet");?></label>
          </p>
          <p>
            <?php __("Enter here the address you want (IPv4 or IPv6)"); ?> <br/>
            <input type="text" size=20 maxlength=39 name="ip" id="edit_ip" /><span id="subnet_info">/<input type="text" size=4 maxlength=3 name="subnet" id="edit_subnet" /></span>
          </p>
          <p>
            <?php __("Add a commentary");?><br/>
            <input type="text" size=30 maxlength=200 name="infos" id="edit_infos" />
          </p>
          <input type="submit" class="inb" value="<?php __("Save")?>" />
        </form>
        </span>
      </fieldset>
    </td>
  </tr>
</table>

<script type="text/javascript">
  $("#subnet_info").hide();
  $("#form_add_ip").hide();

  function switch_from_add_ip() {
    $("#form_add_ip").toggle();
  }

  function edit_ip(id, ip, subnet, infos) {
    $("#form_add_ip").show();
    $("#edit_id").val(id);
    $("#edit_ip").val(ip);
    $("#edit_subnet").val(subnet);
    $("#edit_infos").val(infos);
    if ( (subnet == 32) || (subnet == 128) ) {
      $("is_subnet_no").attr('checked', true );
      $("is_subnet_yes").attr('checked', false);
      $("#subnet_info").hide();
    } else {
      $("is_subnet_no").attr('checked', false);
      $("is_subnet_yes").attr('checked', true);
      $("#subnet_info").show();
    }
  }

</script>
<?php include_once("foot.php"); ?>
