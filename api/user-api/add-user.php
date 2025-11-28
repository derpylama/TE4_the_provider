<?php

require "./user-api-handler.php";
require_once('../auth-api/auth-api-handler.php');
header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new UserApiHandler();

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

//get input data
$input=json_decode(file_get_contents('php://input'), true);

//verify if essential accpunt creation info is included
$reqparameter=["username", "password", "type"];
foreach($reqparameter as $param){
    if(!isset($input[$param])){
        $message="Missing parameter: ".$param;
        $apiHandler->error($message, [], 400);
        exit;
    }
}

$mail = $input["mail"] ?? "";
$firstName = $input["first_sname"] ?? "";
$lastName = $input["last_name"] ?? "";
$phoneNumber = $input["phone_number"] ?? "";
$adress = $input["adress"] ?? "";
$employmentNumber = $input["employment_number"] ?? 0;
$birthDate = $input["birthdate"] ?? "";
$general = $input["general"] ?? "";
$username = $input["username"];
$password = $input["password"];
$type = $input["type"];
$extraMail = $input["extra_mail"] ?? [];
$extraPhoneNumber = $input["extra_phone_number"] ?? [];
$extraAdress = $input["extra_adress"] ?? [];

echo $apiHandler->addUser($token, $mail, $firstName, $lastName, $phoneNumber, $adress, $employmentNumber, $birthDate, $username, $password, $type, $general, $extraMail, $extraPhoneNumber, $extraAdress);