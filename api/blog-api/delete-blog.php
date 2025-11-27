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
        // echo json_encode([
        //     "status" => "error",
        //     "message" => "Missing parameter: " . $params 
        // ]);
        $message="Missing parameter: ".$param;
        $apiHandler->error($message, [], 400);
        exit;
    }
}

$editUserId=$blogData["userId"] ?? 0;




echo $blogHandler->deleteBlog($blogData["token"], $editUserId);