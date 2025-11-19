<?php

require "./user-api-handler.php";
require_once('../auth-api/auth-api-handler.php');
header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new UserApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);

//verify if essential accpunt creation info is included
$reqparameter=["username", "password", "type","token"];
foreach($reqparameter as $param){
    if(!isset($input[$param])){
        echo json_encode([
            "status"=>"error",
            "message"=>"Missing parameter: ".$param
        ]);
        exit;
    }
}

if ($input['token']!="TESTtokenfo12rtest312ingporpos3123es-2131doremov23ethis-befor1eac321tually-gvining3itouttotheconsummer"){  //REMOVE WHEN ITS FIXED AND DONE JUST FOR TESTING //MARK:IMPORTANT


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



    $customerId = $authResult["customer_id"];

} else { //remove this if when product is complete 
$customerId= 999;
}

$mail = $input["mail"] ?? "";
$adress = $input["adress"] ?? "";
$employmentNumber = $input["employment_number"] ?? 0;
$birthDate = $input["birthdate"] ?? "";
$username = $input["username"];
$password = $input["password"];
$type = $input["type"];




echo $apiHandler->addUser($customerId, $mail, $adress, $employmentNumber, $birthDate, $username, $password, $type);













