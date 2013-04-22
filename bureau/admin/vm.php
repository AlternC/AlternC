<?php

require_once("../class/config.php");

$fields = array (
	"action" => array ("get", "string", ''),
	"script" => array ("get", "boolean", 0),
);
getFields($fields);

if (in_array($action, array('start', 'stop', 'monit'))) {
        $res = $hooks->invoke($action, array(), 'lxc');
}

$infos = $lxc->getvm();

if ($script) {
  // FIXME afficher les variables pertinentes de manière pertinente pour quelqu'un qui veux scripter :)
  print_r($infos);
  if (isset($res)) print_r($res);
  die();
}

# Show the header after the "if script" ;)
include_once("head.php");

# Debug
echo "<fieldset><legend>debug</legend>res<br/>";
if (isset($res)) { printvar($res); }
echo "infos getvm";
printvar($infos);
echo "</fieldset>";
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

<br/>
<br/>
<hr/>
<br/>
<fieldset><legend><?php __("Tips");?></legend>
<?php __("You can script the launch the console access in command line by using this url:"); ?>
<pre>
http://<?php echo $mem->user['login'].':ALTERNC_PASSWORD@'.$host.'/vm.php?http_auth=1&amp;script=1&amp;action=start' ?>
</pre>
<?php __("You can halt the vm by using:"); ?>
<pre>
http://<?php echo $mem->user['login'].':ALTERNC_PASSWORD@'.$host.'/vm.php?http_auth=1&amp;script=1&amp;action=stop' ?>
</pre>
<?php __("And you can see existing vm informations (if exist) by using:"); ?>
<pre>
http://<?php echo $mem->user['login'].':ALTERNC_PASSWORD@'.$host.'/vm.php?http_auth=1&amp;script=1' ?>
</pre>
</fieldset>
<?php
include_once("foot.php");
?>
