<?php
require "./user-api-handler.php";
require_once('../auth-api/auth-api-handler.php');
header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new UserApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);
//check required input parameters
$reqparameter=['user_id','expiration_date', 'token'];
foreach($reqparameter as $param){
    if(!isset($input[$param])){
        echo json_encode([
            "status"=>"error",
            "message"=>"Missing parameter: ".$param
        ]);
        exit;
    }
}





$banUserId = $input["user_id"];

$expirationDate = $input["expiration_date"];

$blogBan = $input["blog_ban"] ?? 0;
$wikiBan = $input["wiki_ban"] ?? 0;
$calendarBan = $input["calendar_ban"] ?? 0;

$reason = $input["reason"] ?? "";
$token = $input["token"];

echo $apiHandler->banUser($token, $banUserId, $expirationDate, $blogBan, $wikiBan, $calendarBan, $reason) ;


