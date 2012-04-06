<?php 
require_once("../class/config.php");
include_once("head.php");

if (isset($_POST['cron']) && is_array($_POST['cron'])) {
  if (! $cron->update($_POST['cron'])) {
    $error=_("There was an error during the record.");
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


<table>
  <tr>
    <th><?php __("URL"); ?></th>
    <th><?php __("Schedule"); ?></th>
    <th><?php __("User"); ?></th>
    <th><?php __("Password"); ?></th>
    <th><?php __("Email report"); ?></th>
  </tr>
<?php 
$max_cron = $quota->getquota("cron");
$max_cron = $max_cron['t'];
if ( sizeof($lst_cron) > $max_cron ) $max_cron=sizeof($lst_cron);

for ($i=0; $i < $max_cron ; $i++) { 
?>
  <tr>
    <?php if (isset($lst_cron[$i])) echo "<input type='hidden' name='cron[$i][id]' value='".$lst_cron[$i]['id']."' />"; ?> 
    <td><input type="text" name="<?php echo "cron[$i][url]";?>" size="30" maxlength="255" value="<?php if (isset($lst_cron[$i]['url'])) { echo htmlentities($lst_cron[$i]['url']);} ?>"/></td>
    <td>
      <select name='cron[<?php echo $i; ?>][schedule]'>
<?php
foreach ($cron->schedule() as $cs) {
  echo "<option value='".$cs['unit']."'";
  if (isset($lst_cron[$i]['schedule']) && ($lst_cron[$i]['schedule'] == $cs['unit'])){  
  echo " selected ";
  }
  echo " >".$cs['name'];
  echo "</option>";
}
?>
      </select>
    </td>
    <td><input type="text" name="<?php echo "cron[$i][user]";?>" size="10" maxlength="64" value="<?php if (isset($lst_cron[$i]['user'])) { echo htmlentities($lst_cron[$i]['user']);} ?>"/></td>
    <td><input type="text" name="<?php echo "cron[$i][password]";?>" size="10" maxlength="64" value="<?php if (isset($lst_cron[$i]['password'])) { echo htmlentities($lst_cron[$i]['password']);} ?>"/></td>
    <td><input type="text" name="<?php echo "cron[$i][email]";?>" size="10" maxlength="64" value="<?php if (isset($lst_cron[$i]['email'])) { echo htmlentities($lst_cron[$i]['email']);} ?>"/></td>
  </tr>
<?php } //foreach ?>
</table>

<input type="submit" name="submit" class="inb" value="<?php __("Save"); ?>" />
</form>


<?php include_once("foot.php"); ?>
