<?php

require("calendar-api-handler.php");
header('Content-Type: application/json');

$apiHandler = new CalendarApiHandler();

$eventData = json_decode(file_get_contents("php://input"), true);

// fallback to get eventData as get
if(!$eventData) {
    $eventData = $_GET;
}

// check if the request has the required parameters
$reqParams = ['user_id'];
foreach($reqParams as $params){
    if(!isset($eventData[$params])){
        echo json_encode([
            "status" => "error",
            "message" => "Missing parameter: " . $params 
        ]);
        exit;
    }
}

$userId = $eventData['user_id'];

// echo the api call
echo $apiHandler->getUserEvents($userId);
?>