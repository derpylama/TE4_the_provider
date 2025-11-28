<?php
require_once('./wiki-api-handler.php');
require_once('../auth-api/auth-api-handler.php');
$auth = new AuthApiHandler();
$apiHandler = new WikiApiHandler();

// Get headers
$header = getallheaders();

// Check Authorization Header
if (!isset($header["Authorization"])) {
    $apiHandler->error("Missing Authorization Header", [], 401);
    exit;
}

// Check if it is a Bearer Token
if (substr($header["Authorization"], 0, 7) !== "Bearer ") {
    $apiHandler->error("Invalid Authorization Header", [], 401);
    exit;
}

$token = substr($header["Authorization"], 7);

//get input data
$input=json_decode(file_get_contents('php://input'), true);

//check required parameters         MARK:parameters
$reqparameter=['wikiChanges_id'];
foreach($reqparameter as $param){
    if(!isset($input[$param])){
        $message="Missing parameter: ".$param;
        $apiHandler->error($message, [], 400);
        exit;
    }
}


//set all parameters 

//required parameters
$wikiChanges_id=$input['wikiChanges_id'];

//optional parameters



//example method call
$response=$apiHandler->restoreWiki($wikiChanges_id, $token);
echo $response;

?>