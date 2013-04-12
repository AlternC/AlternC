<?php

require_once("../class/config.php");
include_once("head.php");

echo '<h1>youpi gestion des VM</h1>';


$fields = array (
	"action"    => array ("request", "string", FALSE),
        "login" => array ("request", "string", FALSE),
	"pass"  => array("request", "string", FALSE),
	"uid"   => array("request", "integer", FALSE),
);
getFields($fields);

if (in_array($action, array('start', 'stop', 'monit')))
{
	$res = call_user_func(array($lxc, $action));
	var_dump ($res);
}

?>

<h1>Dev only</h1>
<form method="post">
	<p>Action: <select name="action"><option value="monit">Monitoring</option><option value="start">Start</option><option value="stop">Stop</option></select></p>
	<p>Login: <input type="text" name="login" /></p>
	<p>Pass (hash): <input type="text" name="pass" /></p>
	<p>Uid: <input type="text" name="uid" /></p>
	<p><input type="submit" name="tester" /></p>

</form>
