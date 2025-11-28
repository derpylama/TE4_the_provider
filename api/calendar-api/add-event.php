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

$eventData = json_decode(file_get_contents("php://input"), true);

// check for required parameters
$reqParams = ['title', 'end_time'];
foreach($reqParams as $params){
    if(!isset($eventData[$params])){
        $message="Missing parameter: ".$params;
        $apiHandler->error($message, [], 400);
        exit;
    }
}



// set the variables
//$userId = $authResult[0]['userId'];

$title = $eventData['title'];
$eventInfo = $eventData['event_info'] ?? '';
$startTime = $eventData['start_time'] ?? '';
$endTime = $eventData['end_time'];
$comment = $eventData['comment'] ?? '';
$generalData = $eventData['general'] ?? '';

// call add event function
echo $apiHandler->addEvent($title, $token, $eventInfo, $startTime, $endTime, $comment, $generalData);


?>