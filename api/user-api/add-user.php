<?php

require "./user-api-handler.php";
require_once('../auth-api/auth-api-handler.php');
header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new UserApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);

//verify if essential accpunt creation info is included
$reqparameter=["username", "password", "type","token"];
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




$mail = $input["mail"] ?? "";
$adress = $input["adress"] ?? "";
$employmentNumber = $input["employment_number"] ?? 0;
$birthDate = $input["birthdate"] ?? "";
$general = $input["general"] ?? "";
$username = $input["username"];
$password = $input["password"];
$type = $input["type"];
$token = $input["token"];




echo $apiHandler->addUser($token, $mail, $adress, $employmentNumber, $birthDate, $username, $password, $type, $general);