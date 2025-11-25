<?php
require_once __DIR__ . "/../auth-api/auth-api-handler.php";
require_once __DIR__ . "/blog-api-handler.php";

header('Content-Type: application/json');

$authHandler = new AuthApiHandler();
$blogHandler = new BlogApiHandler();


$blogData = json_decode(file_get_contents("php://input"), true);

$reqParams = ["content", "title", "token"];

foreach($reqParams as $params){
    if(!isset($blogData[$params])){
        echo json_encode([
            "status" => "error",
            "message" => "Missing parameter: " . $params 
        ]);
        exit;
    }
}

$generalData = $blogData["general"] ?? "";

echo $blogHandler->createBlog($blogData["content"], $blogData["token"], $blogData["title"], $generalData);
