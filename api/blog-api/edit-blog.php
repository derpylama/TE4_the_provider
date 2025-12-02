<?php

require_once __DIR__ . "/blog-api-handler.php";
require_once __DIR__ . "/../auth-api/auth-api-handler.php";

header('Content-Type: application/json');

$authHandler = new AuthApiHandler();
$blogHandler = new BlogApiHandler();


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


$title = trim($blogData["title"] ?? "");
$content = trim($blogData["content"] ?? "");
$editUserid=$blogData["userId"] ?? 0;
$generalData = $blogData["general"] ?? "";

if ($title === "" && $content === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Provide at least one: title or content"
    ]);
    exit;
}


echo $blogHandler->editBlog($content, $title, $token, $editUserid, $generalData);