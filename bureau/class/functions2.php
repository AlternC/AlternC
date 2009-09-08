<?php

function getFields($fields, $requestOnly = false)
{
	$vars = array();
	$methodType = array ("get", "post", "request", "files");

	foreach ($fields AS $name => $options)
	{
		if (in_array($options[0], $methodType) === false)
			die ("Illegal method type used for field " . $name . " : " . $options[0]);

		if ($requestOnly === true)
			$method = "_REQUEST";
		else
			$method = "_" . strtoupper($options[0]);

		switch ($options[1])
		{
			case "integer":

				$vars[$name] = (isset($GLOBALS[$method][$name]) && is_numeric($GLOBALS[$method][$name]) ? intval($GLOBALS[$method][$name]) : $options[2]);
				break;

			case "float":

				$vars[$name] = (isset($GLOBALS[$method][$name]) && is_numeric($GLOBALS[$method][$name]) ? floatval($GLOBALS[$method][$name]) : $options[2]);
				break;

			case "string":

				$vars[$name] = (isset($GLOBALS[$method][$name]) ? trim($GLOBALS[$method][$name]) : $options[2]);
				break;

			case "array":

				$vars[$name] = (isset($GLOBALS[$method][$name]) && is_array($GLOBALS[$method][$name]) ? $GLOBALS[$method][$name] : $options[2]);
				break;

			case "boolean":

				$vars[$name] = (isset($GLOBALS[$method][$name]) ? $GLOBALS[$method][$name] : $options[2]);
				break;

			case "file":

				$vars[$name] = (isset($GLOBALS[$method][$name]) ? $GLOBALS[$method][$name] : $options[2]);
				break;

		   	default:
    		    die ("Illegal method type used for field " . $name . " : " . $options[1]);
		}
	}

	// Insert into $GLOBALS
	foreach ($vars AS $var => $value)
		$GLOBALS[$var] = $value;

	return $vars;
}

function printVar($array)
{
	echo "<pre style=\"border: 1px solid black; text-align: left; font-size: 9px\">\n";
	print_r($array);
	echo "</pre>\n";
}

?>