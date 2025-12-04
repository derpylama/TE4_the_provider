<?php

require "./user-api-handler.php";

header('Content-Type: application/json');

$apiHandler = new UserApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);

//verify if essential accpunt creation info is included
$reqparameter=["username", "password"];
foreach($reqparameter as $param){
    if(!isset($input[$param])){
        echo json_encode([
            "status"=>"error",
            "message"=>"Missing parameter: ".$param
        ]);
        exit;
    }
}
// $username = $input["username"];
// $password = $input["password"];
// $customerUsername = $input["customer_username"];
// $customerPassword = $input["customer_password"];

$username= $apiHandler->checkType($input["username"] , "string", "username");
$password= $apiHandler->checkType($input["password"], "string", "password");
$customerUsername= $apiHandler->checkType($input["customer_username"], "string", "customer_username");
$customerPassword= $apiHandler->checkType($input["customer_password"], "string", "customer_password");


echo $apiHandler->login($customerUsername, $customerPassword, $username, $password);
