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


//set all parameters 

//required parameters

//optional parameters
$query=$input['query'] ?? '';


//example method call
$response=$apiHandler->getWiki($token, $query); //maybe chanmge into getwiki with parameter all  
echo $response;

?>