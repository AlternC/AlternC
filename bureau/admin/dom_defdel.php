<?php 
require_once("../class/config.php");
include_once("head.php");

$fields = array (
        "id"     => array ("request", "integer", ""),
        );
getFields($fields);

//checker admin rights

$dom->del_default_type($id);
include_once("adm_doms_def_type.php");


include_once("foot.php");
