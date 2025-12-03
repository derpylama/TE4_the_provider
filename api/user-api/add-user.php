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
    $mail = filter_var($input["mail"], FILTER_SANITIZE_EMAIL);
    if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $message="Mail is not valid: ";
        $apiHandler->error($message, [], 400);
        exit;
    }
} else {
    $mail = "";
}



$name = filter_var($input["first_name"] ?? "", FILTER_SANITIZE_STRING);

$lastName = filter_var($input["last_name"] ?? "", FILTER_SANITIZE_STRING);
$phoneNumber = filter_var($input["phone_number"] ?? "", FILTER_SANITIZE_STRING);
$adress = filter_var($input["adress"] ?? "", FILTER_SANITIZE_STRING);
$employmentNumber = filter_var($input["employmet_number"] ?? "", FILTER_SANITIZE_STRING);
$birthDate = filter_var($input["birthdate"] ?? "", FILTER_SANITIZE_STRING);

$general = htmlspecialchars($input["general"] ?? "", ENT_QUOTES, 'UTF-8');


$username = filter_var($input["username"], FILTER_SANITIZE_STRING);
$password = filter_var($input["password"], FILTER_SANITIZE_STRING);
$type = filter_var($input["type"], FILTER_SANITIZE_STRING);
if (!in_array($type, ["admin","end_user","user"])) {
    $message="Invalid user type:";
    $apiHandler->error($message, [], 400);
    exit;
}
$extraMail = [];
$extraAdress = [];
$extraPhoneNumber = [];


echo $apiHandler->addUser($token, $mail, $name, $lastName, $phoneNumber, $adress, $employmentNumber, $birthDate, $username, $password, $type, $general, $extraMail, $extraPhoneNumber, $extraAdress);