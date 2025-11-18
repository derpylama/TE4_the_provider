<?php
require "./user-api-handler.php";
require_once('../auth-api/auth-api-handler.php');
header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new UserApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);




print_r($input["customer_id"]);
if(isset($input["customer_id"])){
    $customerId = $input["customer_id"];
    if(isset($input["id"])){
        $userId=$input["id"];
        echo $apiHandler->getUser($customerId, $userId);
    } elseif(isset($input["username"]) && !empty($input["username"])){
        $username=$input["username"];
        echo $apiHandler->getUser($customerId, $username);
    } else {
        echo json_encode([
            "status"=>"error",
            "message"=>"Missing parameter"
        ]);
        exit;
    }
} else {
    echo json_encode([
        "status"=>"error",
        "message"=>"customer_id not set"
    ]);
    exit;
}






