<?php
require_once("../class/config.php");

if (!$admin->enabled) {
  __("This page is restricted to authorized staff");
    exit();
    }

    include_once("head.php");

    ?>
    <h3><?php __("Manage defaults domains type"); ?></h3>
    <hr id="topbar" />
      <p><?php __("If you don't know what this page is about, don't touch anything, and read AlternC documentation about domain types"); ?></p>

      <br />
      <?php
      if (isset($error) && $error) {
        echo "<p class=\"error\">$error</p>";
        }


$fields = array (
        "domup"                => array ("post", "array", ""),
);
getFields($fields);

if (!empty($domup)) {
  if (!$dom->update_default_subdomains($domup) ) {
    $error=_("There was an error during the record.");
  } else {
    $error=_("Save done.");
  }
}

$tab=$dom->lst_default_subdomains();
?>
<form method="post" action="dom_defedit.php" name="main" id="main">
<table class="tlist">
   <tr><th>&nbsp;</th><th><?php __("Sub"); ?></th><th><?php __("Type"); ?></th><th><?php __("settings"); ?></th><th><?php __("Concerned"); ?></th><th><?php __("Activation"); ?></th></tr>
<?php
$col=1;
for($i=0;$i<count($tab)+1;$i++) {
  @$val=$tab[$i];
  $col=3-$col;
    if (isset($tab[$i])){ 
    echo "<input type='hidden' name='domup[$i][id]' value='".$val['id']."' />";  
    }
?>
	<tr  class="lst<?php echo $col; ?>">
  <td></td>
  <td><input type='text' name='domup[<?php echo $i; ?>][sub]' value="<?php echo $val['sub']; ?>"/></td>
  <?php $type=array("VHOST","URL","WEBMAIL","");
  if(in_array($val['domain_type'],$type)){?> 
  <td><select name='domup[<?php echo $i; ?>][domain_type]'>
        <option value='VHOST' <?php if($val['domain_type']=='VHOST') echo "selected=\"selected\""; ?> >VHOST</option>
        <option value='URL' <?php if($val['domain_type']=='URL') echo "selected=\"selected\""; ?> >URL</option>
        <option value='WEBMAIL' <?php if($val['domain_type']=='WEBMAIL') echo "selected=\"selected\""; ?> >WEBMAIL</option>
      </select>
  <?php }else{?>
    <td><input type ='text' name='domup[<?php echo $i; ?>][domain_type]' value='<?php echo $val['domain_type']?>' ></td>
 <? }?>
  </td>
  <td><input type ='text' name='domup[<?php echo $i; ?>][domain_type_parameter]' value='<?php echo $val['domain_type_parameter']?>' ></td>
  <td><select name='domup[<?php echo $i; ?>][concerned]'>
        <option value='MAIN' <?php if($val['concerned']=='MAIN') echo "selected=\"selected\""; ?> >MAIN</option>
        <option value='SLAVE' <?php if($val['concerned']=='SLAVE') echo "selected=\"selected\""; ?> >SLAVE</option>
        <option value='BOTH' <?php if($val['concerned']=='BOTH') echo "selected=\"selected\""; ?> >BOTH</option>
      </select>
  </td>
  <td><input type="checkbox"  id="enabled" name="domup[<?php echo $i; ?>][enabled]" value="1" <?php if ($val['enabled']==1) echo "checked=\"checked\""; ?> /></td>
  </tr>
<?php
}
?>

</table>
  <p><input type="submit" class="inb" name="submit" value="<?php __("Save"); ?>" /></p>
</form>
<?php
 include_once("foot.php"); ?>
