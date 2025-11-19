<?php
require "./user-api-handler.php";
require_once('../auth-api/auth-api-handler.php');
header('Content-Type: application/json');

$auth = new AuthApiHandler();
$apiHandler = new UserApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);
//check required input parameters
$reqparameter=['user','expiration_date', 'token'];
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

//Get the ban target users customer id
$banUserId = $input["user"];
$stmt = $this->conn->prepare("SELECT customer_id, id FROM user WHERE id =:id ");
$stmt->execute([":id"=>$banUserId]);
$userCustomerId = $stmt->fetch();

//verify user is accociated with customer
if (!$userCustomerId["customer_id"] && !$userCustomerId["customer_id"] == $authResult[0]["customer_id"]) {
    echo json_encode([
        "status" => "error",
        "message" => "Insufficient permissions"
    ]);
    exit;
}
//verify that the ban target user is not an admin
if ($userCustomerId["id"] != 'admin') {
    echo json_encode([
        "status" => "error",
        "message" => "Insufficient permissions"
    ]);
    exit;
}
//verify that admin is not banning their own account
if ($banUserId == $authResult[0]["userId"] ) {
    echo json_encode([
        "status" => "error",
        "message" => "Insufficient permissions"
    ]);
    exit;
}


$expirationDate = $input["expiration_date"];

$blogBan = $input["blog_ban"] ?? 0;
$wikiBan = $input["wiki_ban"] ?? 0;
$calendarBan = $input["calendar_ban"] ?? 0;

$reason = $input["reason"] ?? "";

echo $apiHandler->banUser($banUserId, $expirationDate, $blogBan, $wikiBan, $calendarBan, $reason);