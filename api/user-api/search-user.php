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

$filter=$input["filter"] ?? "";
$searchQuery=$input["query"] ?? "";
$searchAmount=$input["result_amount"] ?? 0;

echo $apiHandler->searchUsers($token, $filter, $searchQuery);