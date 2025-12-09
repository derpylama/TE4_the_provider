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
$input=json_decode(file_get_contents('php://input'), true);

// check if the request method is GET
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $apiHandler->error("Invalid request method", [], 405);
    exit;
}

if (isset($input["mail"]) && !empty($input["mail"])) {
    $mail = $apiHandler->checkType($input["mail"], "array", "mail");
    if(isset($mail['add']) && !empty($mail["add"])){
        foreach($mail['add'] as $value){
            $apiHandler->checkType($value, "string", "mail");
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $message="Mail is not valid: ";
                $apiHandler->error($message, [], 400);
                exit;
            }
        }
    }
    if(isset($mail['update']) && !empty($mail["update"])){
        foreach($mail['update'] as $index => $value){
            $apiHandler->checkType($index, "string", "mail");
            $apiHandler->checkType($value, "string", "mail");
            if (!filter_var($index, FILTER_VALIDATE_EMAIL)) {
                $message="Mail is not valid: ";
                $apiHandler->error($message, [], 400);
                exit;
            }
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $message="Mail is not valid: ";
                $apiHandler->error($message, [], 400);
                exit;
            }
        }
    }
    if(isset($mail['delete']) && !empty($mail["delete"])){
        foreach($mail['delete'] as $value){
            $apiHandler->checkType($value, "string", "mail");
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $message="Mail is not valid: ";
                $apiHandler->error($message, [], 400);
                exit;
            }
        }
    }
    $apiHandler->checkType($mail['main'], "string", "mail");
    if (!filter_var($mail['main'], FILTER_VALIDATE_EMAIL)) {
        $message="Mail is not valid: ";
        $apiHandler->error($message, [], 400);
        exit;
    }
} else {
    $mail = "";
}

//$input = $_GET;
$editUserId= $apiHandler->checkType($input["user_id"] ?? "", "int", "user_id");

$mail= $apiHandler->checkType($input["mail"] ?? "", "array", "mail");
$firstName= $apiHandler->checkType($input["first_name"] ?? "", "string", "first_name");
$lastName= $apiHandler->checkType($input["last_name"] ?? "", "string", "last_name");
$phoneNumber= $apiHandler->checkType($input["phone_number"] ?? "", "array", "phone_number");
$adress= $apiHandler->checkType($input["adress"] ?? "", "array", "adress");
$employmentNumber= $apiHandler->checkType($input["employment_number"] ?? "", "string", "employment_number");
$birthDate= $apiHandler->checkType($input["birthdate"] ?? "", "string", "birthdate");
$username= $apiHandler->checkType($input["username"] ?? "", "string", "username");
$password= $apiHandler->checkType($input["password"] ?? "", "string", "password");
$type= $apiHandler->checkType($input["type"] ?? "", "string", "type");
$general= $apiHandler->checkType($input["general"] ?? "", "any", "general");


// $extraMail = $input["mail"] ?? null;
// $extraPhoneNumber = $input["extra_phone_number"] ?? null;
// $extraAdress = $input["extra_adress"] ?? null;

//Chech if the inputed email is valid
// if ($mail != null) {
//     if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
//         echo json_encode([
//             "status" => "error",
//             "message" => "Invalid email"
//         ]);
//         exit;
//     }
// }


$apiHandler->validateDateInput($birthDate);



echo $apiHandler->editUser($token, $editUserId, $mail, $firstName, $lastName, $phoneNumber, $adress, $employmentNumber, $birthDate, $username, $password, $type, $general);