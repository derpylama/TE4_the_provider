<?php

require_once('../auth-api/auth-api-handler.php');
require_once("calendar-api-handler.php");
header('Content-Type: application/json');

$apiHandler = new CalendarApiHandler();
$auth = new AuthApiHandler();

//$eventData = json_decode(file_get_contents("php://input"), true);


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


// check if the request method is GET
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    $apiHandler->error("Invalid request method", [], 405);
    exit;
}

$eventData = $_GET;

$reqParams = ['mode'];
foreach($reqParams as $params){
    if(!isset($eventData[$params])){
        $message="Missing parameter: ".$params;
        $apiHandler->error($message, [], 400);
        exit;
    }
}


$orderBy = $eventData['order_by'] ?? "creation_date";
$orderDirection = $eventData['order_direction'] ?? "asc";
$amount = $eventData['amount'] ?? "";
$offset = $eventData['offset'] ?? "";


$mode = $eventData['mode'] ?? "";
$startTime = $eventData['start_time'] ?? "";
$endTime = $eventData['end_time'] ?? "";

$eventId = $eventData['event_id'] ?? "";

$searchQuery = $eventData['search_query'] ?? "";
$searchFilter = $eventData['search_filter'] ?? "";

$orderBy= $apiHandler->checkType($orderBy, "string", "order_by");
$orderDirection= $apiHandler->checkType($orderDirection, "string", "order_direction");
$amount= $apiHandler->checkType($amount, "int", "amount");
$offset= $apiHandler->checkType($offset, "int", "offset");
$mode= $apiHandler->checkType($mode, "string", "mode");
$startTime= $apiHandler->checkType($startTime, "string", "start_time");
$endTime= $apiHandler->checkType($endTime, "string", "end_time");
$eventId= $apiHandler->checkType($eventId, "int", "event_id");
$searchQuery= $apiHandler->checkType($searchQuery, "string", "search_query");
$searchFilter= $apiHandler->checkType($searchFilter, "array", "search_filter");


// Call the function
$apiHandler->getEvents(
    $token, 
    [
        "mode" => $mode,
        "startTime" => $startTime,
        "endTime" => $endTime,
        "eventId" => $eventId,
        "orderBy" => $orderBy,
        "orderDirection" => $orderDirection,
        "limit" => $amount,
        "offset" => $offset,
        "searchQuery" => $searchQuery,
        "searchFilter" => $searchFilter
    ]
);



?>