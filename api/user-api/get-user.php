<?php
require "./user-api-handler.php";
require_once('../auth-api/auth-api-handler.php');
header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new UserApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);


echo $apiHandler->getUser($customerId, $username, $userId;









require "./user-api-handler.php";
header('Content-Type: application/json');

$apiHandler = new UserApiHandler();

$userInput = file_get_contents("php://input");

$input = json_decode($userInput, true);