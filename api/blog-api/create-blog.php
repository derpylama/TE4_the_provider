<?php
require_once __DIR__ . "/../auth-api/auth-api-handler.php";
require_once __DIR__ . "/blog-api-handler.php";

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

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $apiHandler->error("Invalid request method", [], 405);
    exit;
}

$blogData = json_decode(file_get_contents("php://input"), true);

$reqParams = ["content", "title"];

foreach($reqParams as $params){
    if(!isset($blogData[$params])){

        $message="Missing parameter: ".$param;
        $apiHandler->error($message, [], 400);
        exit;
    }
}

$content = $blogData["content"];
$title = $blogData["title"];
$generalData = $blogData["general"] ?? "";


$generalData= $apiHandler->checkType($generalData, "array", "general");
$content= $apiHandler->checkType($content, "any", "content");
$title= $apiHandler->checkType($title, "any", "title");



echo $apiHandler->createBlog($content, $token, $title, $generalData);
