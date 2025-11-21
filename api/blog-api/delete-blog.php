<?php

require_once __DIR__ . "/blog-api-handler.php";
require_once __DIR__ . "/../auth-api/auth-api-handler.php";

header('Content-Type: application/json');

$authHandler = new AuthApiHandler();
$blogHandler = new BlogApiHandler();

$blogData = json_decode(file_get_contents("php://input"), true);

// Check if a token has been sent
if (!isset($blogData["token"])) {
    echo json_encode([
        "status" => "error",
        "message" => "token is missing"
    ]);
    exit;
}

$verifyResult = json_decode($authHandler->verifyAuthToken($blogData["token"]), true);

if ($verifyResult["status"] == "success") {

    if (isset($blogData["userId"]) && !empty($blogData["userId"]) && $verifyResult[0]["type"] == "admin") {
        echo $blogHandler->deleteBlog($verifyResult[0]["customer_id"], $blogData["userId"], $verifyResult[0]["type"]);
    }
    else {
        echo $blogHandler->deleteBlog($verifyResult[0]["customer_id"], $verifyResult[0]["userId"], $verifyResult[0]["type"]);
    }
}
else {
    echo $authHandler->verifyAuthToken($blogData["token"]);
}