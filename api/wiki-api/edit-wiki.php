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

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $apiHandler->error("Invalid request method", [], 405);
    exit;
}

//get input data
$input=json_decode(file_get_contents('php://input'), true);

//check required parameters         MARK:parameters
$reqparameter=['wiki_article_id'];
foreach($reqparameter as $param){
    if(!isset($input[$param])){
        $message="Missing parameter: ".$param;
        $apiHandler->error($message, [], 400);
        exit;
    }
}

//set all parameters 

//required parameters

$wiki_article_id=$input['wiki_article_id'];

//optional parameters
$content=$input['content'] ?? ''; //default to empty string if not provided only needed for non required parameters
$general=$input['general'] ?? '';
$title=$input['title'] ?? '';


$wiki_article_id= $apiHandler->checkType($wiki_article_id, "int", "wiki_article_id");
$content= $apiHandler->checkType($content, "string", "content");
$general= $apiHandler->checkType($general, "array", "general");
$title= $apiHandler->checkType($title, "string", "title");


//example method call
$response=$apiHandler->editWikiArticle($content, $wiki_article_id, $token, $general, $title);
echo $response;

?>