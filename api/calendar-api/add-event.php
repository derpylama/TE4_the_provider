<?php

require("calendar-api-handler.php");
require_once('../auth-api/auth-api-handler.php');
header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new CalendarApiHandler();

$eventData = json_decode(file_get_contents("php://input"), true);

// check for required parameters
$reqParams = ['title', 'endTime', 'token'];
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
if ($authResult['type'] != 'user') {
    echo json_encode([
        "status" => "error",
        "message" => "Insufficient permissions"
    ]);
    exit;
}



// set the variables
$user_id = $authResult['userId'];

$title = $eventData['title'];
$eventInfo = $eventData['event_info'] ?? '';
$startTime = $eventData['start_time'] ?? '';
$endTime = $eventData['endTime'];

// call add event function
echo $apiHandler->addEvent($title, $userId, $eventInfo, $startTime, $endTime);


?>