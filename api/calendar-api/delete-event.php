<?php

require("calendar-api-handler.php");
require_once('../auth-api/auth-api-handler.php');

header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new CalendarApiHandler();

$eventData = json_decode(file_get_contents("php://input"), true);

// fallback to get eventData as get
if(!$eventData) {
    $eventData = $_GET;
}

//check if the request has the required parameters
$reqParams = ['token', 'event_id'];
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

//verify token
$token = $eventData['token'] ?? '';
$authResult = json_decode($auth->verifyAuthToken($token), true);
if($authResult['status'] != "success"){
    echo json_encode($authResult);
    exit;
}

//check user permissions
if ($authResult[0]['type'] == 'user') {
    echo json_encode([
        "status" => "error",
        "message" => "Insufficient permissions"
    ]);
    exit;
}


$token = $eventData['token'];
$eventId = $eventData['event_id'];
$editEvent = true;

// echo the api call
echo $apiHandler->deleteEvent($token, $eventId, $editEvent);
?>