<?php

require("calendar-api-handler.php");
require_once('../auth-api/auth-api-handler.php');
header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new CalendarApiHandler();

$eventData = json_decode(file_get_contents("php://input"), true);

// check for required parameters
$reqParams = ['title', 'end_time', 'token'];
foreach($reqParams as $params){
    if(!isset($eventData[$params])){
        // echo json_encode([
        //     "status" => "error",
        //     "message" => "Missing parameter: " . $params 
        // ]);
        $message="Missing parameter: ".$params;
        $apiHandler->error($message, [], 400);
        exit;
    }
}



// set the variables
//$userId = $authResult[0]['userId'];
$token = $eventData['token'];

$title = $eventData['title'];
$eventInfo = $eventData['event_info'] ?? '';
$startTime = $eventData['start_time'] ?? '';
$endTime = $eventData['end_time'];
$comment = $eventData['comment'] ?? '';
$generalData = $eventData['general'] ?? '';

// call add event function
echo $apiHandler->addEvent($title, $token, $eventInfo, $startTime, $endTime, $comment, $generalData);


?>