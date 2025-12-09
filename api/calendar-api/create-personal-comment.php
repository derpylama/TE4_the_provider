<?php

require("calendar-api-handler.php");
require_once('../auth-api/auth-api-handler.php');

header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new CalendarApiHandler();

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

$eventData = json_decode(file_get_contents("php://input"), true);


//check if the request has the required parameters
$reqParams = ['event_id', 'comment'];
foreach($reqParams as $params){
    if(!isset($eventData[$params])){
        $message="Missing parameter: ".$params;
        $apiHandler->error($message, [], 400);
        exit;
    }
}

$eventId = $eventData['event_id'];
$comment = $eventData['comment'] ?? '';

$eventId= $apiHandler->checkType($eventId, "int", "event_id");
$comment= $apiHandler->checkType($comment, "string", "comment");

// echo the api call
echo $apiHandler->addPersonalComment($token, $eventId, $comment);
?>