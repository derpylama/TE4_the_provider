<?php
require_once('./wiki-api-handler.php');
require_once('../auth-api/auth-api-handler.php');
$auth = new AuthApiHandler();
$apiHandler = new WikiApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);

//check required parameters         MARK:parameters
$reqparameter=['wiki_id','content','token'];
foreach($reqparameter as $param){
    if(!isset($input[$param])){
        echo json_encode([
            "status"=>"error",
            "message"=>"Missing parameter: ".$param
        ]);
        exit;
    }
}



//set all parameters 

//required parameters

$wiki_id=$input['wiki_id'];
$token=$input['token'];

//optional parameters
$content=$input['content'] ?? ''; //default to empty string if not provided only needed for non required parameters


//example method call
$response=$apiHandler->editWiki($content, $wiki_id, $token);
echo $response;

?>