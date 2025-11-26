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
$reqParams = ['token', 'invited_user_id', 'event_id'];
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


$token = $eventData['token'];
$invitedUserId = $eventData['invited_user_id'];
$eventId = $eventData['event_id'];

// echo the api call
echo $apiHandler->inviteUserToEvent($token, $invitedUserId, $eventId);
?>