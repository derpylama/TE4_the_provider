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
$reqParams = ['token', 'event_id', 'invited_user_id'];
foreach($reqParams as $params){
    if(!isset($eventData[$params])){
        echo json_encode([
            "status" => "error",
            "message" => "Missing parameter: " . $params 
        ]);
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

$userId = $authResult[0]['userId'];
$invitedUserId = $eventData['invited_user_id'];
$eventId = $eventData['event_id'];

// echo the api call
echo $apiHandler->deleteInvitation($userId, $invitedUserId, $eventId);
?>