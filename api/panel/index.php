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
 * Authentication is done by asking for /api/auth/<method>?option1=value1&option2=value2
 * or POSTED data
 * a token is returned for this session
 *
 */


// bootstrap AlternC
require_once("bootstrap.php");

// Which api method is used ?
define("API_CALL_GET",       1 );
define("API_CALL_POST",      2 );
define("API_CALL_POST_REST", 3 );
define("API_CALL_GET_REST",  4 );

/**
 * Attempts to load a class in multiple path, the PSR-0 or old style way
 * 
 * @staticvar array $srcPathList
 * @staticvar boolean $init
 * @param string $class_name
 * @return boolean
 */

function __autoload($class_name)
{
    // Contains (Namespace) => directory
    static $srcPathList                 = array();
    static $init=null;

    // Attempts to set include path and directories once
    if( is_null( $init )){

        // Sets init flag
        $init                           = true;
        
        // Sets a contextual directory
        $srcPathList["standard"]        = "/usr/share/php";

        // Updates include_path according to this list
        $includePathList                = explode(PATH_SEPARATOR, get_include_path()); 

        foreach($srcPathList as $path){
            if ( !in_array($path, $includePathList)){
                $includePathList[]      = $path;
            }
        }
        // Reverses the path for search efficiency
        $finalIncludePathList           = array_reverse($includePathList);
        
        // Sets the updated include_path
        set_include_path(implode(PATH_SEPARATOR, $finalIncludePathList));
	
    }
    
    // Accepts old Foo_Bar namespacing
    if(preg_match("/_/", $class_name)){
        $file_name                      = str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';
        
    // Accepts 5.3 Foo\Bar PSR-0 namespacing 
    } else if(preg_match("/\\/", $class_name)){
        $file_name                      = str_replace('\\', DIRECTORY_SEPARATOR, ltrim($class_name,'\\')) . '.php';
        
    // Accepts non namespaced classes
    } else {
        $file_name                      = $class_name . '.php';        
    }

    // Attempts to find file in namespace
    foreach($srcPathList as $namespace => $path ){
        $file_path                      = $path.DIRECTORY_SEPARATOR.$file_name;
        if(is_file($file_path) && is_readable($file_path)){
            require $file_path;
            return true;
        }
    }
    
    // Failed to find file
    return false;
}


function apicall($data,$token,$mode) {
  global $dbh;
  $options["databaseAdapter"]=$dbh;
  $options["loginAdapterList"]=array("sharedsecret","login");
  // TODO (no loggerAdapter PSR3-Interface-compliant class as of now)
  try {
    $data["token_hash"]=$token;
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
    $response=new Alternc_Api_Response(array("code" => $e->getCode(), "message" => $e->getMessage() ));
    echo $response->toJson();
    exit();
  }
}

function apiauth($data,$mode) {
  global $dbh;
  $options["databaseAdapter"]=$dbh;
  // TODO (no loggerAdapter PSR3-Interface-compliant class as of now)
  try {

    $service=new Alternc_Api_Service($options);

    $response = $service->auth($data);

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


// Authentication 
if (preg_match("#^/api/auth/([^/\?]*)[/\?]?#",$_SERVER["REQUEST_URI"],$mat)) {
  if ($_SERVER["REQUEST_METHOD"]=="POST") {
    $data=array("options" => $_POST,
		"method" => $mat[1]);
    apiauth($data,API_CALL_GET);
    exit(); 
  } else {
    $data=array("options" => $_GET, 
		"method" => $mat[1]);
    apiauth($data,API_CALL_POST);
    exit(); 
  }  
}

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
if (preg_match("#^/api/rest/([^/]*)/([^/\?]*)[/\?]?#",$_SERVER["REQUEST_URI"],$mat)) {
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

echo "I did nothing. Did you call the api properly?";