<?php 

require_once __DIR__ . "/blog-api-handler.php";
require_once __DIR__ . "/../auth-api/auth-api-handler.php";

header('Content-Type: application/json');

$authHandler = new AuthApiHandler();
$blogHandler = new BlogApiHandler();


$blogData = json_decode(file_get_contents("php://input"), true);

$verifyResult = json_decode($authHandler->verifyAuthToken($blogData["token"]), true);

if ($verifyResult["status"] == "success") {
    if ((isset($verifyResult) && !empty($verifyResult)) && (isset($blogData["blogId"]) && !empty($blogData["blogId"]))) {
        echo $blogHandler->getBlog($verifyResult[0]["customer_id"], $blogData["blogId"]);
    }
    else if (isset($verifyResult) && !empty($verifyResult)) {
        echo $blogHandler->getBlog($verifyResult[0]["customer_id"]);
    }
    
}
else {
    echo $authHandler->verifyAuthToken($blogData["token"]);
}


