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
        $message="Missing parameter: ".$param;
        $apiHandler->error($message, [], 400);
        exit;
    }
}

$editUserId = $input["user_id"] ?? null;

$mail = $input["main_mail"] ?? null;
$firstName = $input["first_name"] ?? null;
$lastName = $input["last_name"] ?? null;
$phoneNumber = $input["phone_number"] ?? "";
$adress = $input["adress"] ?? null;
$employmentNumber = $input["employment_number"] ?? null;
$birthDate = $input["birthdate"] ?? null;
$username = $input["username"] ?? null;
$password = $input["password"] ?? null;
$type = $input["type"] ?? null;
$general = $input["general"] ?? null;

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


echo $apiHandler->editUser($token, $editUserId, $mail, $firstName, $lastName, $adress, $employmentNumber, $birthDate, $username, $password, $type, $general);