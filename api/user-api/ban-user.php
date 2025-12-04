<?php
require "./user-api-handler.php";
require_once('../auth-api/auth-api-handler.php');
header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new UserApiHandler();

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
//check required input parameters
$reqparameter=['user_id','expiration_date'];
foreach($reqparameter as $param){
    if(!isset($input[$param])){
        $message="Missing parameter: ".$param;
        $apiHandler->error($message, [], 400);
        exit;
    }
}

$banUserId = $input["user_id"];

$expirationDate = $input["expiration_date"];

$blogBan = $input["blog_ban"] ?? 0;
$wikiBan = $input["wiki_ban"] ?? 0;
$calendarBan = $input["calendar_ban"] ?? 0;

$reason = $input["reason"] ?? "";


$apiHandler->validateDateInput($expirationDate, "dateSeconds");


echo $apiHandler->banUser($token, $banUserId, $expirationDate, $blogBan, $wikiBan, $calendarBan, $reason);