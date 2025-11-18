<?php

require "./user-api-handler.php";
require_once('../auth-api/auth-api-handler.php');
header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new UserApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);


/*
//verify token
$token=$input['token'] ?? '';
$authResult=json_decode($auth->verifyAuthToken($token), true);
if($authResult['status']!="success"){
    echo json_encode($authResult);
    exit;
}

//check user permissions
if ($authResult['type'] == 'admin') {
    echo json_encode([
        "status" => "error",
        "message" => "Insufficient permissions"
    ]);
    exit;
}
*/


$reqparameter=["username", "password", "type"];
foreach($reqparameter as $param){
    if(!isset($input[$param])){
        echo json_encode([
            "status"=>"error",
            "message"=>"Missing parameter: ".$param
        ]);
        exit;
    }
}


$customerId = 0;
$mail = $input["mail"] ?? "";
$adress = $input["adress"] ?? "";
$employmentNumber = $input["employment_number"] ?? 0;
$birthDate = $input["birthdate"] ?? "";
$username = $input["username"];
$password = $input["password"];
$type = $input["type"];


echo $apiHandler->addUser($customerId, $mail, $adress, $employmentNumber, $birthDate, $username, $password, $type);













