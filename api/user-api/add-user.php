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

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $apiHandler->error("Invalid request method", [], 405);
    exit;
}

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



if (isset($input["mail"]) && !empty($input["mail"])) {
    $mail = $apiHandler->checkType($input["mail"], "string", "mail");
    if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $message="Mail is not valid: ";
        $apiHandler->error($message, [], 400);
        exit;
    }
} else {
    $mail = "";
}

$name = $apiHandler->checkType($input["first_name"] ?? "", "string", "name");
$lastName = $apiHandler->checkType($input["last_name"] ?? "", "string", "last_name");
$phoneNumber = $apiHandler->checkType($input["phone_number"] ?? "", "string", "phone_number");
$adress = $apiHandler->checkType($input["adress"] ?? "", ["string","object","array"], "adress");
$employmentNumber = $apiHandler->checkType($input["employment_number"] ?? "", "string", "employment_number");
$birthDate = $apiHandler->checkType($input["birthdate"] ?? "", "string", "birthDate");
$username = $apiHandler->checkType($input["username"] ?? "", "string", "username");
$password = $apiHandler->checkType($input["password"] ?? "", "string", "password");
$type = $apiHandler->checkType($input["type"] ?? "", "string", "type");
$general = $apiHandler->checkType($input["general"] ?? "", "any", "general");

if (!in_array($type, ["admin","end_user","user"])) {
    $message="Invalid user type:";
    $apiHandler->error($message, [], 400);
    exit;
}



$extraMail = [];
$extraAdress = [];
$extraPhoneNumber = [];









echo $apiHandler->addUser($token, $mail, $name, $lastName, $phoneNumber, $adress, $employmentNumber, $birthDate, $username, $password, $type, $general, $extraMail, $extraPhoneNumber, $extraAdress);
