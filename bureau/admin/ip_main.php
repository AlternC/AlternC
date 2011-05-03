<?php 
require_once("../class/config.php");
include_once("head.php");


$fields = array (
  "delete_id"    => array ("get",  "integer", ""),
  "id"           => array ("post", "integer", 0),
  "ipsub"        => array ("post", "string", ""),
  "infos"        => array ("post", "string" ,""),
  "s_ipsub"      => array ("post", "integer", ""),
  "s_protocol"   => array ("post", "string", ""),
);
getFields($fields);

if (!empty($s_protocol)) {
  $val="s_affect_".$s_protocol;
  $fields = array( $val => Array('post','string', '') );
  getFields($fields);

  if (! $authip->ip_affected_save($s_ipsub, $s_protocol, $$val) ) {
    $error="Error during ip_affected_save";
  }
}

if (!empty($delete_id)) {
  if (! $authip->ip_delete($delete_id)) {
    $error="Error during recording";
  }
}

if (!empty($ipsub)) {
  if (! $authip->ip_save($id, $ipsub, $infos)) {
    $error="Error during recording";
  }
}

$list_ip = $authip->list_ip();
$ac  = $authip->get_auth_class();
$lac = $authip->list_affected();
?>

<h3><?php __("Access security"); ?></h3>
<hr id="topbar"/>
<br />

<?php if ($error) { ?>
  <p class="error"><?php echo $error ?></p>
<?php } ?>

<table>
<tr>
  <th><?php __("Protocol");?></th>
  <th><?php __("Target");?></th>
  <th><?php __("IP address");?></th>
</tr>
<?php 
  foreach ($lac as $ll) {
    echo "<tr>";
    echo "<td>".$ac[$ll['protocol']]['name']."</td>";
    echo "<td>".$ac[$ll['protocol']]['values'][$ll['parameters']]."</td>";
    echo "<td>".$list_ip[$ll['authorised_ip_id']]['ip_human']."</td>";
    echo "</tr>";
  }
?>
</table>
</p>

<fieldset><legend><?php __("Add a new rule"); ?></legend>
<form method="post" action="ip_main.php" name="main" id="main">
<table>
  <thead>
    <th><?php __("Target"); ?></th>
    <th><?php __("IP address (or subnet)"); ?></th>
    <th/>
  </thead>
  <tbody>
    <tr valign="top">
    <td>
      <?php foreach ($ac as $a) { ?>
        <p>
        <input type="radio" name="s_protocol" id="protocol_<?php echo htmlentities($a['protocol']);?>" value="<?php echo htmlentities($a['protocol']);?>" />
        <label for="s_protocol_<?php echo htmlentities($a['protocol']);?>"><?php echo htmlentities($a['name']); ?></label>
        <select name="s_affect_<?php echo htmlentities($a['protocol']);?>" id="s_affect_<?php echo htmlentities($a['protocol']);?>">
          <?php foreach ($a['values'] as $k => $v) { ?>
            <option value="<?php echo htmlentities($k); ?>"><?php echo htmlentities($v); ?></option>
          <?php  } ?>
        </select>
        </p>
      <?php } ?>
    </td><td valign="middle">
      <p>
      <select name="s_ipsub">
        <?php foreach ($list_ip as $li) { ?>
          <option value="<?php echo $li['id']; ?>"><?php echo htmlentities($li['infos']); echo " - ".$li['ip'] ; if (!($li['subnet']==32 || $li['subnet'] == 128)) echo "/".$li['subnet'];?></option>
        <?php } ?>
      </select>
      </p>
    </td>
    <td valign=middle>
      <input type="submit" class="inb" value="<?php __("Save")?>" />
    </td>
    </tr>
  </tbody>
</table>
</form>
</fieldset>

<table>
  
  <tr>
    <th colspan=2><?php __("IP address");?></th>
  </tr>
  <tr>
    <td valign=top>
      <table>
      <tr><th><?php __("Type"); ?></th><th><?php __("IP"); ?></th><th><?php __("Informations"); ?></th><th colspan=2/></tr>
      <?php foreach($list_ip as $i) {
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
        <td><div class="ina"><a href="javascript:edit_ip(<?php echo "'".htmlentities($i['id'])."','".htmlentities($i['ip_human'])."','".htmlentities($i['infos'])."'"; ?>);"><img src="images/edit.png" alt="<?php __("Edit"); ?>" /><?php __("Edit"); ?></a></div></td>
        <td><div class="ina"><a href="ip_main.php?delete_id=<?php echo urlencode($i["id"]) ?>"><img src="images/delete.png" alt="<?php __("Delete"); ?>" /><?php __("Delete"); ?></a></div></td>
        </tr>

      <?php } ?>
      </table>
    </td>
    <td valign=top>
      <fieldset>
        <legend><?php __("Add an IP");?> - <a href="javascript:edit_ip('','<?php echo htmlentities($_SERVER['REMOTE_ADDR'])."','Home IP'";?>);" ><?php echo __("Add my current IP"); ?></a></legend>
        <span id="form_add_ip">
        <form method="post" action="ip_main.php" name="main" id="main">
          <p id="reset_edit_ip" style="display:none;"><a href="javascript:reset_edit_ip();"><?php __("Cancel edit")?></a></p>
          <input type="hidden" name="id" value="" id="edit_id" >
          <p>
            <?php __("Enter here the IP address you want. <br/> <i>IPv4, IPv6 and subnet allowed</i>"); ?> <br/>
            <input type="text" size=20 maxlength=39 name="ipsub" id="edit_ip" />
          </p>
          <p>
            <?php __("Add a comment");?><br/>
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
