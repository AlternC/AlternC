<?php 
require_once("../class/config.php");
include_once("head.php");

$fields = array (
        "cronupdate"                => array ("post", "array", ""),
);
getFields($fields);

if (!empty($cronupdate)) {
  if (! $cron->update($cronupdate)) {
    $error=$err->errstr();
  } else {
    $error=_("Save done.");
  }
}

$lst_cron = $cron->lst_cron();
?>

<h3><?php __("Scheduled tasks"); ?></h3>
<hr id="topbar"/>
<br />

<?php if (isset($error) && $error) { ?>
  <p class="error"><?php echo $error ?></p>
<?php } ?>

<form method="post" action="cron.php" id="main" name="cron" >


<table class="tlist">
<!--
  <tr>
    <th/>
    <th><?php __("URL"); ?></th>
    <th><?php __("Schedule"); ?></th>
    <th><?php __("User"); ?></th>
    <th><?php __("Password"); ?></th>
    <th><?php __("Email report"); ?></th>
  </tr>
-->
<?php 
$max_cron = $quota->getquota("cron");
$max_cron = $max_cron['t'];
if ( sizeof($lst_cron) > $max_cron ) $max_cron=sizeof($lst_cron);

for ($i=0; $i < $max_cron ; $i++) { 
?>
  <tr class="<?php echo ($i%2)?"lst1":"lst2"; ?>">
    <td rowspan='2'>
      <?php if (isset($lst_cron[$i])) echo "<input type='hidden' name='cronupdate[$i][id]' value='".$lst_cron[$i]['id']."' />"; ?> 
      <?php if (isset($lst_cron[$i])) { echo '#'.$lst_cron[$i]['id']; } ?><br/>
      <a href="javascript:cleancron('<?php echo $i ?>');"><img src="images/delete.png" alt="<?php __("Delete");?>" title="<?php __("Delete");?>"/></a>
    </td>
    <td colspan='2'>
      <input type="text" placeholder="<?php __("Called URL"); ?>" id="crup_url_<?php echo $i?>" name="<?php echo "cronupdate[$i][url]";?>" size="40" maxlength="255" value="<?php if (isset($lst_cron[$i]['url'])) { echo htmlentities($lst_cron[$i]['url']);} ?>"/>
    </td>
    <td>
      <?php __("Period:");?> <select name='cronupdate[<?php echo $i; ?>][schedule]'>
<?php
foreach ($cron->schedule() as $cs) {
  echo "<option value='".$cs['unit']."'";
  if (isset($lst_cron[$i]['schedule']) && ($lst_cron[$i]['schedule'] == $cs['unit'])){  
  echo " selected='selected' ";
  }
  echo " >".$cs['name'];
  echo "</option>";
}
?>
      </select>
      <?php if (isset($lst_cron[$i])) {__("Next execution: "); echo $lst_cron[$i]['next_execution'];}?>
    </td>
  </tr><tr class="<?php echo ($i%2)?"lst1":"lst2"; ?>">
    <td><input type="text" placeholder="<?php __("HTTP user (optional)"); ?>" id="crup_user_<?php echo $i?>" name="<?php echo "cronupdate[$i][user]";?>" size="20" maxlength="64" value="<?php if (isset($lst_cron[$i]['user'])) { echo htmlentities($lst_cron[$i]['user']);} ?>"/></td>
    <td><input type="text" placeholder="<?php __("HTTP password (optional)"); ?>" id="crup_pass_<?php echo $i?>" name="<?php echo "cronupdate[$i][password]";?>" size="20" maxlength="64" value="<?php if (isset($lst_cron[$i]['password'])) { echo htmlentities($lst_cron[$i]['password']);} ?>"/></td>
    <td><input type="text" placeholder="<?php __("Mail address (optional)"); ?>" id="crup_mail_<?php echo $i?>" name="<?php echo "cronupdate[$i][email]";?>" size="25" maxlength="64" value="<?php if (isset($lst_cron[$i]['email'])) { echo htmlentities($lst_cron[$i]['email']);} ?>"/></td>
  </tr>
<?php } //foreach ?>
</table>
<p><input type="submit" name="submit" class="inb" value="<?php __("Apply the modifications"); ?>" /></p>

</form>

<script type="text/javascript">
function cleancron(i) {
  document.getElementById('crup_url_'+i).value = ''; 
  document.getElementById('crup_user_'+i).value = ''; 
  document.getElementById('crup_pass_'+i).value = ''; 
  document.getElementById('crup_mail_'+i).value = ''; 
}

</script>

<?php include_once("foot.php"); ?>
