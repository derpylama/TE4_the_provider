<?php 

require_once __DIR__ . "/blog-api-handler.php";
require_once __DIR__ . "/../auth-api/auth-api-handler.php";

header('Content-Type: application/json');

$authHandler = new AuthApiHandler();
$apiHandler = new BlogApiHandler();

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

$blogData = json_decode(file_get_contents("php://input"), true);

// check if the request method is GET
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    $apiHandler->error("Invalid request method", [], 405);
    exit;
}


$blogId=$_GET["blogId"] ?? "";
$searchQuery=$_GET["search_query"] ?? "";
$searchFilter=$_GET["search_filter"] ?? ["title"];
$amount=$_GET["amount"] ?? 10;
$offset=$_GET["offset"] ?? 0;

echo $apiHandler->getBlog($token, $blogId, $searchQuery, $searchFilter, $amount, $offset); //get all if no id is written
