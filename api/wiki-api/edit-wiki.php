<?php
require_once('./wiki-api-handler.php');
require_once('../auth-api/auth-api-handler.php');
$auth = new AuthApiHandler();
$apiHandler = new WikiApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);

//check required parameters         MARK:parameters
$reqparameter=['wiki_id','token'];
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

//required parameters

$wiki_id=$input['wiki_id'];
$token=$input['token'];

//optional parameters
$content=$input['content'] ?? ''; //default to empty string if not provided only needed for non required parameters
$general=$input['general'] ?? '';
$title=$input['title'] ?? '';

//example method call
$response=$apiHandler->editWiki($content, $wiki_id, $token, $general, $title);
echo $response;

?>