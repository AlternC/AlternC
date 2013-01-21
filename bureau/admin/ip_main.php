<?php 
require_once("../class/config.php");
include_once("head.php");


$fields = array (
  "delete_id"           => array ("get",  "integer", ""),
  "delete_affected_id"  => array ("get",  "integer", ""),
  "id"                  => array ("post", "integer", 0),
  "ipsub"               => array ("post", "string", ""),
  "infos"               => array ("post", "string" ,""),
  "s_ipsub"             => array ("post", "integer", ""),
  "s_protocol"          => array ("post", "string", ""),
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

if (!empty($delete_affected_id)) {
  if (! $authip->ip_affected_delete($delete_affected_id)) {
    $error="Error during deletion";
  }
}

if (!empty($delete_id)) {
  if (! $authip->ip_delete($delete_id)) {
    $error="Error during deletion";
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

<?php if (isset($error) && $error) { ?>
  <p class="error"><?php echo $error ?></p>
<?php } ?>

<p><?php __("Here you can add rules to restrict access to AlternC's services, filtered by IP. First, add trusted IPs in the 'Known IP and networks' list. Then, add rules to grant access on services to the chosen IPs from this list.") ?></p>

<h3><?php __("Enabled rules"); ?></h3>

<table class="tlist">
<tr>
  <th><?php __("Protocol");?></th>
  <th><?php __("Target");?></th>
  <th><?php __("IP address");?></th>
</tr>
<?php 
  $col=1;
  foreach ($lac as $ll) {
    $col=3-$col;
    echo "<tr class='lst$col' >";
    echo "<td>".$ac[$ll['protocol']]['name']."</td>";
    echo "<td>".(isset($ac[$ll['protocol']]['values'][$ll['parameters']])?$ac[$ll['protocol']]['values'][$ll['parameters']]:'')."</td>";
    echo "<td>".$list_ip[$ll['authorised_ip_id']]['infos']."<br/>".$list_ip[$ll['authorised_ip_id']]['ip_human']."</td>"; ?>
    <td><div class="ina"><a href="ip_main.php?delete_affected_id=<?php echo urlencode($ll["id"]) ?>"><img src="images/delete.png" alt="<?php __("Delete")?>" /><?php __("Delete"); ?></a></div></td>
  <?php
    echo "</tr>";
  }
?>
</table>
</p>

<hr/>
<h3><?php __("Add a new rule"); ?></h3>
<form method="post" action="ip_main.php" name="main" id="main">
<table class="tlist">
  <thead>
    <th><?php __("Target"); ?></th>
    <th><?php __("IP address (or subnet)"); ?></th>
    <td/>
  </thead>
  <tbody>
    <tr valign="top">
    <td>
      <?php foreach ($ac as $a) { ?>
        <p>
        <input type="radio" name="s_protocol" id="s_protocol_<?php echo htmlentities($a['protocol']);?>" value="<?php echo htmlentities($a['protocol']);?>" />
        <label for="s_protocol_<?php echo htmlentities($a['protocol']);?>"><?php echo htmlentities($a['name']); ?></label>

        <?php if ( sizeof($a['values']) > 1 ) { ?>
           <select name="s_affect_<?php echo htmlentities($a['protocol']);?>" id="s_affect_<?php echo htmlentities($a['protocol']);?>">
             <?php foreach ($a['values'] as $k => $v) { ?>
               <option value="<?php echo htmlentities($k); ?>"><?php echo htmlentities($v); ?></option>
             <?php  } ?>
           </select>
        <?php } else { ?>
          <?php foreach ($a['values'] as $k => $v) { ?>
            <label><b><?php echo htmlentities($v); ?></b></label> 
            <input type=hidden name="s_affect_<?php echo htmlentities($a['protocol']);?>" id="s_affect_<?php echo htmlentities($a['protocol']);?>" value="<?php echo htmlentities($k); ?>" readonly>
          <?php  } ?>
        <?php } ?>
        </p>
      <?php } ?>
    </td><td valign="middle">
      <p>
      <select name="s_ipsub">
        <?php foreach ($list_ip as $li) { ?>
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

<hr/>
<h3><?php __("Known IP and networks");?></h3>
<table class="tlist">
  <tr>
    <td valign=top width=40%> 
      <table>
      <tr><th><?php __("Type"); ?></th><th><?php __("IP"); ?></th><th><?php __("Informations"); ?></th><th colspan=2/></tr>
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
        echo "<tr class='lst$col' ><td>$txt</td><td>{$i['ip_human']}</td><td>{$i['infos']}</td>";
        ?>
        <td><div class="ina"><a href="javascript:edit_ip(<?php echo "'".htmlentities($i['id'])."','".htmlentities($i['ip_human'])."','".htmlentities($i['infos'])."'"; ?>);"><img src="images/edit.png" alt="<?php __("Edit"); ?>" /><?php __("Edit"); ?></a></div></td>
        <td><div class="ina"><a href="ip_main.php?delete_id=<?php echo urlencode($i["id"]) ?>"><img src="images/delete.png" alt="<?php __("Delete"); ?>" /><?php __("Delete"); ?></a></div></td>
        </tr>

      <?php } ?>
      </table>
    </td>
    <td valign=top width=40%>
      <fieldset>
        <legend><?php __("Add an IP");?> - <a href="javascript:edit_ip('','<?php echo htmlentities(get_remote_ip())."','Home IP'";?>);" ><?php echo __("Add my current IP"); ?></a></legend>
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
            <input type="text" size=25 maxlength=200 name="infos" id="edit_infos" />
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
