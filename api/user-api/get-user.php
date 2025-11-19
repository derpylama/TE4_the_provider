<?php
require "./user-api-handler.php";
require_once('../auth-api/auth-api-handler.php');
header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new UserApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);
//verify token
$token=$input['token'] ?? '';
$authResult=json_decode($auth->verifyAuthToken($token), true);
if($authResult['status']!="success"){
    echo json_encode($authResult);
    exit;
}
//check user permissions
if ($authResult[0]['type'] != 'admin') {
    echo json_encode([
        "status" => "error",
        "message" => "Insufficient permissions"
    ]);
    exit;
}



print_r($authResult);
$customerId = $authResult["customer_id"];
$id=$input["id"] ?? 0;
$username=$input["username"] ?? "";

echo $apiHandler->getUser($customerId, $id, $username);











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






