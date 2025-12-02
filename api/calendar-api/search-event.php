<?php

require("calendar-api-handler.php");
require_once('../auth-api/auth-api-handler.php');

header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new CalendarApiHandler();

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

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $apiHandler->error("Invalid request method", [], 405);
    exit;
}

$eventData = json_decode(file_get_contents("php://input"), true);

//check if the request has the required parameters
$reqParams = ['search_query'];
foreach($reqParams as $params){
    if(!isset($eventData[$params])){
        $message="Missing parameter: ".$params;
        $apiHandler->error($message, [], 400);
        exit;
    }
}

$searchQuery = $eventData['search_query'];
$orderBy = $eventData['order_by'] ?? "creation_date";
$orderDirection = $eventData['order_direction'] ?? "asc";
$amount = $eventData['amount'] ?? "";
$offset = $eventData['offset'] ?? "";

if($orderBy != "start_time" && $orderBy != "event_info" && $orderBy != "title" && $orderBy != "end_time" && $orderBy != "creation_date" && $orderBy != "latest_update"){
    $message="Illegal order by input: ".$orderBy;
    $apiHandler->error($message, [], 400);
    exit;
}

// echo the api call
$apiHandler->searchForEvent($token, $searchQuery, $orderBy, $orderDirection, $amount, $offset);
?>