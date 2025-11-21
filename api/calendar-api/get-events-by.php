<?php

require_once('../auth-api/auth-api-handler.php');
require_once("calendar-api-handler.php");
header('Content-Type: application/json');

$apiHandler = new CalendarApiHandler();
$auth = new AuthApiHandler();

$eventData = json_decode(file_get_contents("php://input"), true);

// fallback to get eventData as get
if(!$eventData) {
    $eventData = $_GET;
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


// check if the request has the required parameters
$reqParams = ['span', 'year', 'token'];
if($eventData['span'] != "day" && $eventData['span'] != "week" && $eventData['span'] != "month" && $eventData['span'] != "year"){
    echo json_encode([
        "status" => "error",
        "message" => "Invalid timespan"
    ]);
    exit;
}

foreach($reqParams as $params){
    if(!isset($eventData[$params])){
        echo json_encode([
            "status" => "error",
            "message" => "Missing parameter: " . $params 
        ]);
        exit;
    }
}

$token = $eventData['token'];
$span = $eventData['span'];
$year = $eventData['year'];

if($span == 'day'){
    $day = $eventData['day_number'];
    $week = $eventData['week_number'];
    $month = $eventData['month_number'] ?? '';
}else if($span == 'week'){
    $day = $eventData['day_number'] ?? '';
    $week = $eventData['week_number'];
    $month = $eventData['month_number'] ?? '';
}else if($span == 'month'){
    $day = $eventData['day_number'] ?? '';
    $week = $eventData['week_number'] ?? '';
    $month = $eventData['month_number'];
}else if($span == 'year'){
    $day = $eventData['day_number'] ?? '';
    $week = $eventData['week_number'] ?? '';
    $month = $eventData['month_number'] ?? '';
}



// echo the api call
echo $apiHandler->getUserEventsBy($token, $span, $year, $month, $week, $day);
?>