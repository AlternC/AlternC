<?php

$piwik_available_rights = array('noaccess', 'view', 'admin');

function piwik_right_widget($name, $subname, $cred) {
	global $piwik_available_rights;
	$elem = '';
	$i = 1;
	foreach ($piwik_available_rights AS $piwik_right) {
		$elem .= sprintf('<input type="radio" id="%s-%d" name="%s[%s]" value="%s" %s/>', $name . $subname, $i, $name, $subname, $piwik_right, (($cred === $piwik_right) ? ' checked ' : ''));
		$elem .= sprintf('<label for="%s-%d">%s</label>', $name . $subname, $i++, __($piwik_right, "alternc", true));
	}
	return $elem;
}


function piwik_select_element($name, $credz = 'noaccess') {
	$elem = '<select name="' . $name . '">';
	foreach (array('noaccess', 'view', 'admin') AS $cred)
		$elem .= "\t" . '<option value="' . $cred . '"' . (($cred === $credz) ? ' selected ' : '') . '>' . $cred . '</option>' . "\n";
	$elem .= '</select>';

	return $elem;
}
