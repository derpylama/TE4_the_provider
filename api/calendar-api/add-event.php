<?php

require("calendar-api-handler.php");
header('Content-Type: application/json');

$apiHandler = new CalendarApiHandler();

$eventData = json_decode(file_get_contents("php://input"), true);

// check for required parameters
$reqParams = ['title', 'user_id', 'endTime'];
foreach($reqParams as $params){
    if(!isset($eventData[$params])){
        echo json_encode([
            "status" => "error",
            "message" => "Missing parameter: " . $params 
        ]);
        exit;
    }
}

// set the variables
$title = $eventData['title'];
$userId = $eventData['user_id'];
$eventInfo = $eventData['event_info'] ?? '';
$startTime = $eventData['start_time'] ?? '';
$endTime = $eventData['endTime'];

// call add event function
echo $apiHandler->addEvent($title, $userId, $eventInfo, $startTime, $endTime);


?>