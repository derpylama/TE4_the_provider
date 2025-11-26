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

$token = $eventData['token'];
$eventId = $eventData['event_id'];
$sortInvitesBy = $eventData['sort_invites_by'] ?? 'all';
if($sortInvitesBy != "accepted" && $sortInvitesBy != "pending" && $sortInvitesBy != "all"){
    echo json_encode([
        "status" => "error",
        "message" => "invalid input on sort_invites_by"
    ]);
    exit;
}

// echo the api call
echo $apiHandler->getInvitations($token, $eventId, $sortInvitesBy);
?>