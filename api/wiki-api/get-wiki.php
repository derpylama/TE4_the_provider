<?php
require_once('./wiki-api-handler.php');
require_once('../auth-api/auth-api-handler.php');
$auth = new AuthApiHandler();
$apiHandler = new WikiApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);

//check required parameters         MARK:parameters
$reqparameter=['token'];
foreach($reqparameter as $param){
    if(!isset($input[$param])){
        // echo json_encode([
        //     "status"=>"error",
        //     "message"=>"Missing parameter: ".$param
        // ]);
        $message="Missing parameter: ".$param;
        $apiHandler->error($message, [], 400);
        exit;
    }
}







//set all parameters 
$token=$input['token'];
//required parameters

//optional parameters
$query=$input['query'] ?? '';


//example method call
$response=$apiHandler->getWiki($token, $query); //maybe chanmge into getwiki with parameter all  
echo $response;

?>