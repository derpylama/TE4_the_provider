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



// check if the request has the required parameters
$reqParams = ['span', 'year', 'token'];
if($eventData['span'] != "day" && $eventData['span'] != "week" && $eventData['span'] != "month" && $eventData['span'] != "year"){
    // echo json_encode([
    //     "status" => "error",
    //     "message" => "Invalid timespan"
    // ]);
    $message="Invalid timespan";
    $apiHandler->error($message, [], 400);
    exit;
}

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
$span = $eventData['span'];
$year = $eventData['year'];
$orderBy = $eventData['order_by'] ?? "creation_date";
$orderDirection = $eventData['order_direction'] ?? "asc";
$amount = $eventData['amount'] ?? "";
$offset = $eventData['offset'] ?? "";

if($orderBy != "start_time" && $orderBy != "event_info" && $orderBy != "title" && $orderBy != "end_time" && $orderBy != "creation_date" && $orderBy != "latest_update"){
    $message="Illegal order by input: ".$orderBy;
    $apiHandler->error($message, [], 400);
    exit;
}

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
echo $apiHandler->getUserEventsBy($token, $span, $year, $month, $week, $day, $orderBy, $orderDirection, $amount, $offset);
?>