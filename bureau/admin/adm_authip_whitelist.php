<?php 
require_once("../class/config.php");
include_once("head.php");


$fields = array (
  "delete_id"           => array ("get",  "integer", ""),
  "id"                  => array ("post", "integer", 0),
  "ipsub"               => array ("post", "string", ""),
  "infos"               => array ("post", "string" ,""),
  "s_ipsub"             => array ("post", "integer", ""),
  "s_protocol"          => array ("post", "string", ""),
);
getFields($fields);

if (!empty($delete_id)) {
  if (! $authip->ip_delete($delete_id)) {
    $error="Error during deletion";
  }
}

if (!empty($ipsub)) {
  if (! $authip->ip_save_whitelist($id, $ipsub, $infos)) {
    $error="Error during recording";
  }
}

$list_ip = $authip->list_ip_whitelist();
?>

<h3><?php __("Access security"); ?></h3>
<hr id="topbar"/>
<br />

<?php if (isset($error) && $error) { ?>
  <p class="error"><?php echo $error ?></p>
<?php } ?>
<center>
  <p class="error"><?php __("Warning"); echo "<br/>"; __("The IP and subnet you have here are allowed for ALL users and ALL usages"); ?></p>
</center>

<p>
      <fieldset>
        <legend><?php __("Add an IP");?> - <a href="javascript:edit_ip('','<?php echo htmlentities(get_remote_ip())."','Home IP'";?>);" ><?php echo __("Add my current IP"); ?></a></legend>
        <span id="form_add_ip">
        <form method="post" action="adm_authip_whitelist.php" name="main" id="main">
          <p id="reset_edit_ip" style="display:none;"><a href="javascript:reset_edit_ip();"><?php __("Cancel edit")?></a></p>
          <input type="hidden" name="id" value="" id="edit_id" >
          <p>
            <?php __("Enter here the IP address you want. <br/> <i>IPv4, IPv6 and subnet allowed</i>"); ?> <br/>
            <input type="text" size=20 maxlength=39 name="ipsub" id="edit_ip" />
          </p>
          <p>
            <?php __("Add a comment");?><br/>
            <input type="text" size=25 maxlength=200 name="infos" id="edit_infos" />
          </p>
          <input type="submit" class="inb" value="<?php __("Save")?>" />
        </form>
        </span>
      </fieldset>
 
</p>
      <table class='tlist'>
      <tr><th><?php __("Type"); ?></th><th><?php __("IP"); ?></th><th><?php __("Informations"); ?></th><th colspan='2' /></tr>
      <?php 
      $col=1;
      foreach($list_ip as $i) {
        $col=3-$col;
        if (checkip($i['ip'])) {
          if ($i['subnet']==32) {
            $txt="Address IPv4";
          } else {
            $txt="Subnet IPv4";
          }
        } elseif (checkipv6($i['ip'])) {
          if ($i['subnet']==128) {
            $txt="Address IPv6";
          } else {
            $txt="Subnet IPv6";
          }
        } 
        echo "<tr class='lst$col'><td>$txt</td><td>{$i['ip_human']}</td><td>{$i['infos']}</td>";
        ?>
        <td><div class="ina"><a href="javascript:edit_ip(<?php echo "'".htmlentities($i['id'])."','".htmlentities($i['ip_human'])."','".htmlentities($i['infos'])."'"; ?>);"><img src="images/edit.png" alt="<?php __("Edit"); ?>" /><?php __("Edit"); ?></a></div></td>
        <td><div class="ina"><a href="adm_authip_whitelist.php?delete_id=<?php echo urlencode($i["id"]) ?>"><img src="images/delete.png" alt="<?php __("Delete"); ?>" /><?php __("Delete"); ?></a></div></td>
        </tr>

      <?php } ?>
      </table>

<script type="text/javascript">
  function reset_edit_ip() {
    $("#reset_edit_ip").hide();
    $("#edit_id").val('');
    $("#edit_ip").val('');
    $("#edit_infos").val('');
  }

  function edit_ip(id, iph, infos) {
    if ( id != '' ) { 
      $("#reset_edit_ip").show();
    }
    $("#edit_id").val(id);
    $("#edit_infos").val(infos);
    $("#edit_ip").val(iph);
  }

</script>
<?php include_once("foot.php"); ?>
