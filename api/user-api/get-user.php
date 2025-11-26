<?php
require "./user-api-handler.php";
require_once('../auth-api/auth-api-handler.php');
header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new UserApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);

$reqparameter=["token"];
foreach($reqparameter as $param){
    if(!isset($input[$param])){
        echo json_encode([
            "status"=>"error",
            "message"=>"Missing parameter: ".$param
        ]);
        exit;
    }
}



$token = $input["token"];
$id=$input["user_id"] ?? 0;
$username=$input["username"] ?? "";

if ($username === "" && $id === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Provide at least one: username or id"
    ]);
    exit;
}


echo $apiHandler->getUser($token, $id, $username);













