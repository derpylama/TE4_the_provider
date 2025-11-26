<?php
require "./user-api-handler.php";
require_once('../auth-api/auth-api-handler.php');
header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new UserApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);
//check required input parameters
$reqparameter=['user_id', 'token'];
foreach($reqparameter as $param){
    if(!isset($input[$param])){
        // echo json_encode([
        //     "status"=>"error",
        //     "message"=>"Missing parameter: ".$param
        // ]);
        $message="Missing parameter: ".$param;
        $apiHandler->error($message, [], 400);
        exit;
    }
}


$removeUserId = $input["user_id"];

$token = $input["token"];


echo $apiHandler->removeUser($removeUserId, $token);