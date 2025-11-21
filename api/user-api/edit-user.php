<?php

require "./user-api-handler.php";
require_once('../auth-api/auth-api-handler.php');
header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new UserApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);

//verify token
$token=$input['token'] ?? '';
$authResult=json_decode($auth->verifyAuthToken($token), true);
if($authResult['status']!="success"){
    echo json_encode($authResult);
    exit;
}

$id = $input["user_id"];

//verify if user wants to edit their own account or if they are an admin
if ($authResult[0]["userId"] != $id) {
    if ($authResult[0]['type'] != 'admin') {
        echo json_encode([
            "status" => "error",
            "message" => "Insufficient permissions"
        ]);
        exit;
    }
}
$customerId = $authResult[0]["customer_id"];
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


echo $apiHandler->editUser($customerId, $id, $mail, $adress, $employmentNumber, $birthDate, $username, $password, $type);