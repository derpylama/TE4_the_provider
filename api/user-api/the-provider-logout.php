<?php

require "./user-api-handler.php";
require_once('../auth-api/auth-api-handler.php');
header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new UserApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);

//verify if essential accpunt creation info is included
$reqparameter=["session_key"];
foreach($reqparameter as $param){
    if(!isset($input[$param])){
        echo json_encode([
            "status"=>"error",
            "message"=>"Missing parameter: ".$param
        ]);
        exit;
    }
}

$sessionKey = $input["session_key"];
$token = $input["token"] ?? '';




echo $apiHandler->providerLogout($token, $sessionKey);