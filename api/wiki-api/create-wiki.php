<?php
require_once('./wiki-api-handler.php');
require_once('../auth-api/auth-api-handler.php');
$auth = new AuthApiHandler();
$apiHandler = new WikiApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);

//check required parameters         MARK:parameters
$reqparameter=['titel','token'];
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
if ($authResult['type'] == 'user') {
    echo json_encode([
        "status" => "error",
        "message" => "Insufficient permissions"
    ]);
    exit;
}






//set all parameters 

//required parameters
$title=$input['title'];
$user_id=$authResult['userId']; //will be from user id in token

//optional parameters
$content=$input['content'] ?? ''; //default to empty string if not provided only needed for non required parameters


//example method call
$response=$apiHandler->createWiki($title, $content, $user_id);
echo $response;

?>