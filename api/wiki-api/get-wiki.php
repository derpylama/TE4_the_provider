<?php
require_once('./wiki-api-handler.php');
require_once('../auth-api/auth-api-handler.php');

$auth = new AuthApiHandler();
$apiHandler = new WikiApiHandler();

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

// Ensure GET request
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    $apiHandler->error("Invalid request method", [], 405);
    exit;
}

$input = $_GET;

// Optional parameters
$searchQuery = $input["search_query"] ?? [];
$amount = $input["amount"] ?? 20;
$offset = $input["offset"] ?? 0;
$orderDirection = $input["order_direction"] ?? "DESC";  // newest first by default

// Type checking
$searchQuery = $apiHandler->checkType($searchQuery, "array", "search_query");
$amount = $apiHandler->checkType($amount, "int", "amount");
$offset = $apiHandler->checkType($offset, "int", "offset");
$orderDirection = $apiHandler->checkType($orderDirection, "string", "order_direction");

// Validate order direction
$orderDirection = strtoupper($orderDirection);
if (!in_array($orderDirection, ['ASC', 'DESC'])) {
    $apiHandler->error("order_direction must be ASC or DESC. You entered: " . $orderDirection, [], 400);
}

// Call method
$response = $apiHandler->getAllWiki($token, $searchQuery, $amount, $offset, $orderDirection);

echo $response;

?>
