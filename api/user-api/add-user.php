<?php

require "./user-api-handler.php";
header('Content-Type: application/json');

$apiHandler = new UserApiHandler();

$userInput = file_get_contents("php://input");

$input = json_decode($userInput, true);

$customerId = 0;
$mail = $input["mail"];
$adress = $input["adress"];
$employmentNumber = (int)$input["employment_number"];
$birthDate = $input["birthdate"];
$username = $input["username"];
$password = $input["password"];
$type = $input["type"];


echo $apiHandler->addUser($customerId, $mail, $adress, $employmentNumber, $birthDate, $username, $password, $type);