<?php

require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"action" => array ("get", "string", ''),
);
getFields($fields);

if (in_array($action, array('start', 'stop', 'monit'))) {
        $res = $hooks->invoke($action, array(), 'lxc');
printvar($res);
}

$infos = $lxc->getvm();

printvar($infos);
?>

<h3><?php __('Console access'); ?></h3>
<hr/>
<br/>

<?php if (isset($res) && ! $res['lxc']) { ?>
<div>
<span class="error">
  <?php echo $err->errstr(); ?>
</span>
</div>
<br/>
<br/>
<?php } //isset $res ?>

<div>
<?php if (empty($infos)) { 
  echo '<span class="error">';
  __("You can start a virtual machine.");
  echo "<a href='vm.php?action=start'>"._("Click here to do so.")."</a>";
  echo '</span>';
} else {
 echo "<table class='tedit'>";
 echo "<tr><th>"._("Hostname")."</th><td>".$infos['serialized_object']['hostname']."</td></tr>";
 echo "<tr><th>"._("Start time")."</th><td>".$infos['date_start']."</td></tr>";
 echo "<tr><td colspan='2'><a href='vm.php?action=stop'>"._("Click here to stop the machine")."</a></td></tr>";
 echo "</table>"; 


} // empty infos ?>
</div>

<?php
include_once("foot.php");
?>
