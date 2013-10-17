<?php

require_once("../class/config.php");

$fields = array (
	"action" => array ("request", "string", ''),
	"script" => array ("request", "boolean", 0),
);
getFields($fields);

if (in_array($action, array('start', 'stop', 'monit'))) {
        $res = $hooks->invoke($action, array(), 'lxc');
}
switch ($action) {
case "start":
  $lxc->start();
  break;
case "stop":
  $lxc->stop();
  break;
}
if ($lxc->error && !$script) {
  $error=$lxc->error;
}

$infos = $lxc->getvm();

if ($script) {
  header("Content-Type: text/plain");
  if (isset($res)) {
  echo "ACTION:".$action."\n";
  echo "RETURN:".intval($res['lxc'])."\n";
  }
  if ($infos) {
    echo "VM_STATUS:OK\n";
    echo "VM_START:".$infos['starttime']."\n";
    echo "VM_HOSTNAME:".$infos['hostname']."\n";
  } else {
    echo "VM_STATUS:NONE\n";
  }
  die();
}

# Show the header after the "if script" ;)
include_once("head.php");

?>

<h3><?php __('Console access'); ?></h3>
<hr/>
<br/>

<?php if ($error) { ?>
<div>
<span class="error">
   <?php echo implode('<br />', $error); ?>
</span>
</div>
<br/>
<br/>
<?php } ?>

<div>
<?php if (empty($infos)) { 
?>
<p class="error"><?php __("You can start a virtual machine."); ?></p>
<form method="post" action="vm.php">
   <input type="hidden" name="action" value="start" />
<input type="submit" class="inb" name="go" value="<?php __("Click here to start a virtual machine."); ?>" />
</form>
<?php
} else {
 echo "<table class='tedit'>";
 echo "<tr><th>"._("Hostname")."</th><td>".$infos['hostname']."</td></tr>";
 echo "<tr><th>"._("Start time")."</th><td>".date('Y-m-d H:i:s',$infos['starttime'])."</td></tr>";
 echo "<tr><th>"._("SSH Fingerprint")."</th><td style=\"font-family: Courier, fixed;\">".implode('<br />',$infos['ssh-keys'])."</td></tr>";
 echo "<tr><th>"._("Useful command")."</th><td><pre>";
   echo "ssh ".$mem->user['login']."@".$infos['hostname']."\n";
   echo "rsync ".$mem->user['login']."@".$infos['hostname']."\n";
 echo "</pre></td></tr>";
 echo "</table>";
?>
<p class="error"><?php __("You can stop your virtual machine."); ?></p>
<form method="post" action="vm.php">
   <input type="hidden" name="action" value="stop" />
<input type="submit" class="inb" name="go" value="<?php __("Click here to stop your running virtual machine."); ?>" />
</form>
<?php
} // empty infos ?>
</div>

<br/>
<br/>
<hr/>
<h3><?php __("Tips"); ?></h3>
<a href="javascript:;" onclick="$('#tips-script').toggle();"><?php __("Can I start or stop my console access with a script?"); ?></a>
<div id='tips-script'>
<fieldset>
<?php __("You can script the launch the console access in command line by using this url:"); ?>
<pre>
http://<?php echo $mem->user['login'].':ALTERNC_PASSWORD@'.$host.'/vm.php?http_auth=1&amp;script=1&amp;action=start' ?>
</pre>
<?php __("You can halt the vm by using:"); ?>
<pre>
http://<?php echo $mem->user['login'].':ALTERNC_PASSWORD@'.$host.'/vm.php?http_auth=1&amp;script=1&amp;action=stop' ?>
</pre>
<?php __("And you can see existing vm information (if the vm is running) by using:"); ?>
<pre>
http://<?php echo $mem->user['login'].':ALTERNC_PASSWORD@'.$host.'/vm.php?http_auth=1&amp;script=1' ?>
</pre>
<i><?php __("Warning: if you do not use HTTPS, your password will be transfered without any protection"); ?></i>
</fieldset>
</div>

<br/>
<a href="javascript:;" onclick="$('#tips-soft').toggle();"><?php __("Which software can I use?"); ?></a>
<div id='tips-soft'>
<fieldset>
<?php __("To access a remote console with SSH, you can use Putty. To transfer files, you can use Filezilla in SFTP mode."); ?>
</fieldset>
</div>

<script type="text/javascript">
$('#tips-script').toggle();
$('#tips-soft').toggle();
</script>

<?php
include_once("foot.php");
?>
