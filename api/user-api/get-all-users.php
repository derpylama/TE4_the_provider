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
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    $apiHandler->error("Invalid request method", [], 405);
    exit;
}

$input = $_GET;

$searchAmount= $apiHandler->checkType($input["result_amount"] ?? "", "int", "result_amount");
$offset= $apiHandler->checkType($input["offset"] ?? 0, "int", "offset");
$userId= $apiHandler->checkType($input["user_id"] ?? "", "int", "user_id");
$orderBy= $apiHandler->checkType($input["order_by"] ?? "", "any", "order_by");
$general= $apiHandler->checkType($input["general"] ?? "", "any", "general");


//OLD
$request = $input["request"] ?? null;

if (!is_numeric($searchAmount) && !is_numeric($offset)) {
    $apiHandler->error("result_amount must be either null or int", [], 401);
}

echo $apiHandler->getAllUsers($token, $request, $searchAmount, $offset, $userId, $orderBy);