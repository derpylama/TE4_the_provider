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

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $apiHandler->error("Invalid request method", [], 405);
    exit;
}

$blogData = json_decode(file_get_contents("php://input"), true);

$title = $apiHandler->checkType(trim($blogData["title"] ?? ""), "string", "title");
$content = $apiHandler->checkType(trim($blogData["content"] ?? ""), "string", "content");
$blogPostId = $apiHandler->checkType($blogData["blog_post_id"] ?? 0, "int", "blog_post_id");
$generalData = $apiHandler->checkType($blogData["general"] ?? "", "array", "general");

if ($title === "" && $content === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Provide at least one: title or content"
    ]);
    exit;
}

echo $apiHandler->editBlogPost($content, $title, $token, $blogPostId, $generalData);