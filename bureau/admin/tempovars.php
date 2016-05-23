<?php 

// Faut finir de le developper. Se fait appeler avec en POST les infos a stocker dans la variable de session
// @TODO: have a whitelist of allowed key/values...
// Mis en pause => voir commentaire en bas de la classe m_mem
define("NOCSRF","1"); // no csrf for js-posted data
require_once("../class/config.php");

// but (csrf) we check POST, not REQUEST!
if ( empty($_POST['key']) || empty($_POST['val']) ) {
  die('1');
}

$key=$_POST['key'];
$val=$_POST['val'];

if (empty($val)) {
  die('2');
}

if ( $mem->session_tempo_params_set($key, $val) ) {
  die('0');
}

die('3');


?>
