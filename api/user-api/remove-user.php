<?php
require "./user-api-handler.php";
require_once('../auth-api/auth-api-handler.php');
header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new UserApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);
//check required input parameters
$reqparameter=['user', 'token'];
foreach($reqparameter as $param){
    if(!isset($input[$param])){
        echo json_encode([
            "status"=>"error",
            "message"=>"Missing parameter: ".$param
        ]);
        exit;
    }
}
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

$removeUserId = $input["user"];
$customerId = $authResult[0]["customer_id"];


//check user permissions
if ($authResult[0]['userId'] == $removeUserId) {
    echo json_encode([
        "status" => "error",
        "message" => "Cant remove your own admin account"
    ]);
    exit;
}

echo $apiHandler->removeUser($removeUserId, $customerId);