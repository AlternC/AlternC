<?php

/**
 * multiple call-mode API for Alternc
 * you can call this HTTP(s) API as follow: 
 * from the base url https://panel.example.fr/api/
 * 1. /api/post use GETted data (?token=xx&object=xx&action=yy&option1=value1&option2=value2
 * 2. /api/post use POSTED data using the same keys 
 * 3. use a sub-url (rest-style) of the form /api/rest/object/action?token=xx&option1=value1&option2=value2
 * 4. the same (REST) but options and value are POSTED
 * 
 * the json-object contains:
 *  ->object = the Alternc_Api_Object_<classname> to call
 *  ->action = the method to call in this class
 *  ->options = an object passed as it is while calling the method.
 * 
 */


// bootstrap AlternC
require_once("bootstrap.php");

// Which api method is used ?
define("API_CALL_GET",       1 );
define("API_CALL_POST",      2 );
define("API_CALL_POST_REST", 3 );
define("API_CALL_GET_REST",  4 );

// TODO : __autoload of classes ? 

function apicall($data,$token,$mode) {
  global $dbh;
  $options["databaseAdapter"]=$dbh;
  $options["loginAdapterList"]=array("sharedsecret","login");
  // TODO (no loggerAdapter PSR3-Interface-compliant class as of now)
  try {

    $service=new Alternc_Api_Service($options);

    $response = $service->call(
			       new Alternc_Api_Request($data)
			       );

    header("Content-Type: application/json");
    echo $response->toJson();
    exit();

  } catch (Exception $e) {
    // something went wrong, we spit out the exception as an Api_Response
    // TODO : Don't do that on production! spit out a generic "fatal error" code and LOG the exception !
    header("Content-Type: application/json");
    $response=new Alternc_Api_Response(array("code" => $e->code, "message" => $e->message));
    echo $response->toJson();
    exit();
  }
}

// Authentication is done by asking for /api/auth/<method>?option1=value1&option2=value2 
// or POSTED data
// a token is returned for this session

// We support 4 api calls methods:

if ($_SERVER["REQUEST_URI"]=="/api/post") {
  // simple ?q or POST of json data
  if ($_SERVER["REQUEST_METHOD"]=="POST") {
    $data=array("options" => $_POST,
		"object" => $_POST["object"],
		"action" => $_POST["action"],
		);
    $token=$_POST["token"];
    apicall($data,$token,API_CALL_POST);
    exit();
  } else {
    $data=array("options" => $_GET,
		"object" => $_GET["object"],
		"action" => $_GET["action"],
		);
    $token=$_GET["token"];
    apicall($data,$token,API_CALL_GET);
    exit();
  }
}

if (preg_match("#^/api/rest/([^/]*)/([^/]*)/?#$",$_SERVER["REQUEST_URI"],$mat)) {
  if ($_SERVER["REQUEST_METHOD"]=="POST") {
    $data=array("options" => $_POST, 
		"object" => $mat[1],
		"action" => $mat[2]
		);
    $token=$_POST["token"];
    apicall($data,$token,API_CALL_POST_REST);
    exit(); 
  } else {
    $data=array("options" => $_GET, 
		"object" => $mat[1],
		"action" => $mat[2]
		);
    $token=$_GET["token"];
    apicall($data,$token,API_CALL_GET_REST);
    exit(); 
  }
}
