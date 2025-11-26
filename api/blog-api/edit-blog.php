<?php

require_once __DIR__ . "/blog-api-handler.php";
require_once __DIR__ . "/../auth-api/auth-api-handler.php";

header('Content-Type: application/json');

$authHandler = new AuthApiHandler();
$blogHandler = new BlogApiHandler();

$blogData = json_decode(file_get_contents("php://input"), true);

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


echo $blogHandler->editBlog($content, $title, $blogData["token"], $editUserid, $generalData);