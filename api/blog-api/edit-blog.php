<?php

require_once __DIR__ . "/blog-api-handler.php";
require_once __DIR__ . "/../auth-api/auth-api-handler.php";

header('Content-Type: application/json');

$authHandler = new AuthApiHandler();
$blogHandler = new BlogApiHandler();

$reqparameter=['title','content','token'];
$blogData = json_decode(file_get_contents("php://input"), true);



// foreach($reqparameter as $param){
//     if(!isset($blogData[$param])){
//         echo json_encode([
//             "status"=>"error",
//             "message"=>"Missing parameter: " . $param
//         ]);
//         exit;
//     }
// }

if (!isset($blogData["token"])) {
    echo json_encode([
        "status" => "error",
        "message" => "token is missing"
    ]);
    exit;
}

$title = trim($blogData["title"] ?? "");
$content = trim($blogData["content"] ?? "");

if ($title === "" && $content === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Provide at least one: title or content"
    ]);
    exit;
}

$verifyResult = json_decode($authHandler->verifyAuthToken($blogData["token"]), true);

if ($verifyResult["status"] == "success") {

    if (isset($blogData["userId"]) && !empty($blogData["userId"]) && $verifyResult[0]["type"] == "admin") {
        echo $blogHandler->editBlog($content, $title, $verifyResult[0]["customer_id"], $blogData["userId"], $verifyResult[0]["type"]);
    }
    else {
        echo $blogHandler->editBlog($content, $title, $verifyResult[0]["customer_id"], $verifyResult[0]["userId"]);
    }
}
else {
    echo $authHandler->verifyAuthToken($blogData["token"]);
}