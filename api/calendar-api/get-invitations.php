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

//$eventData = json_decode(file_get_contents("php://input"), true);

// // fallback to get eventData as get
// if(!$eventData) {
//     $eventData = $_GET;
// }

// check if the request method is GET
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    $apiHandler->error("Invalid request method", [], 405);
    exit;
}

$eventData = $_GET;

//check if the request has the required parameters
// $reqParams = ['event_id'];
// foreach($reqParams as $params){
//     if(!isset($eventData[$params])){
//         $message="Missing parameter: ".$params;
//         $apiHandler->error($message, [], 400);
//         exit;
//     }
// }


$eventId = $eventData['event_id'] ?? "";
$sortInvitesBy = $eventData['sort_invites_by'] ?? 'all';

if($sortInvitesBy != "accepted" && $sortInvitesBy != "pending" && $sortInvitesBy != "all"){
    $apiHandler->error("Invalid parameter: sort_invites_by", [], 400);
    exit;
}

$eventId= $apiHandler->checkType($eventId, "int", "event_id");
$sortInvitesBy= $apiHandler->checkType($sortInvitesBy, "string", "sort_invites_by");

// echo the api call
if($eventId == ""){
    $apiHandler->getOwnInvitations($token);
}
else {
    echo $apiHandler->getInvitations($token, $eventId, $sortInvitesBy);
}
?>