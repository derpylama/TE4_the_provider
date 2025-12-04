<?php
require_once('./wiki-api-handler.php');
require_once('../auth-api/auth-api-handler.php');
$auth = new AuthApiHandler();
$apiHandler = new WikiApiHandler();
//get input data

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

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $apiHandler->error("Invalid request method", [], 405);
    exit;
}

$input=json_decode(file_get_contents('php://input'), true);

//check required parameters         MARK:parameters
$reqparameter=['title'];

foreach($reqparameter as $param){
    if(!isset($input[$param])){
        $message="Missing parameter: ".$param;
        $apiHandler->error($message, [], 400);
        exit;
    }
}

//verify token
//happens in createwiki now




//set all parameters 

//required parameters
$title=$input['title'];

//optional parameters
$general=$input['general'] ?? '';
$content=$input['content'] ?? ''; //default to empty string if not provided only needed for non required parameters

$title= $apiHandler->checkType($title, "string", "title");
$general= $apiHandler->checkType($general, "array", "general");
$content= $apiHandler->checkType($content, "string", "content");


//example method call
$response=$apiHandler->createWiki($title, $content, $token, $general);
echo $response;

?>