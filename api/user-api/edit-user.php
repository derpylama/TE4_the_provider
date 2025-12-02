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

// //get input data
// $input=json_decode(file_get_contents('php://input'), true);

// check if the request method is GET
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $apiHandler->error("Invalid request method", [], 405);
    exit;
}

$input = $_GET;


$editUserId = $input["user_id"] ?? null;

$mail = $input["mail"] ?? null;
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
$extraMail = $input["extra_mail"] ?? null;
$extraPhoneNumber = $input["extra_phone_number"] ?? null;
$extraAdress = $input["extra_adress"] ?? null;

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


echo $apiHandler->editUser($token, $editUserId, $mail, $firstName, $lastName, $adress, $employmentNumber, $birthDate, $username, $password, $type, $general, $extraMail, $extraPhoneNumber, $extraAdress);