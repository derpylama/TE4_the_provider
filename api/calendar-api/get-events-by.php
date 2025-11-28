<?php

require_once('../auth-api/auth-api-handler.php');
require_once("calendar-api-handler.php");
header('Content-Type: application/json');

$apiHandler = new CalendarApiHandler();
$auth = new AuthApiHandler();

$eventData = json_decode(file_get_contents("php://input"), true);


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


// fallback to get eventData as get
if(!$eventData) {
    $eventData = $_GET;
}

// check if the request has the required parameters
$reqParams = ['span', 'year'];
if($eventData['span'] != "day" && $eventData['span'] != "week" && $eventData['span'] != "month" && $eventData['span'] != "year"){
    $message="Invalid timespan";
    $apiHandler->error($message, [], 400);
    exit;
}

foreach($reqParams as $params){
    if(!isset($eventData[$params])){
        $message="Missing parameter: ".$params;
        $apiHandler->error($message, [], 400);
        exit;
    }
}


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