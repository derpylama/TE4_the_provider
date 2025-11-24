<?php

require "./user-api-handler.php";
require_once('../auth-api/auth-api-handler.php');
header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new UserApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);

$reqparameter=['token'];
foreach($reqparameter as $param){
    if(!isset($input[$param])){
        echo json_encode([
            "status"=>"error",
            "message"=>"Missing parameter: ".$param
        ]);
        exit;
    }
}

$mail = $input["mail"] ?? null;
$adress = $input["adress"] ?? null;
$employmentNumber = $input["employment_number"] ?? null;
$birthDate = $input["birthdate"] ?? null;
$username = $input["username"] ?? null;
$password = $input["password"] ?? null;
$type = $input["type"] ?? null;

//Chech if the inputed email is valid
if ($mail != null) {
    if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid email"
        ]);
        exit;
    }
}


echo $apiHandler->editUser($token, $usertoeditid, $mail, $adress, $employmentNumber, $birthDate, $username, $password, $type);