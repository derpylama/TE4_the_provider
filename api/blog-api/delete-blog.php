<?php

require_once __DIR__ . "/blog-api-handler.php";
require_once __DIR__ . "/../auth-api/auth-api-handler.php";

header('Content-Type: application/json');

$authHandler = new AuthApiHandler();
$blogHandler = new BlogApiHandler();

$blogData = json_decode(file_get_contents("php://input"), true);

// Check if a token has been sent
$reqParams = ["token"];

foreach($reqParams as $params){
    if(!isset($blogData[$params])){
        echo json_encode([
            "status" => "error",
            "message" => "Missing parameter: " . $params 
        ]);
        exit;
    }
}

$editUserId=$blogData["userId"] ?? 0;

$verifyResult = json_decode($authHandler->verifyAuthToken($blogData["token"]), true);


echo $blogHandler->deleteBlog($blogData["token"], $editUserId);