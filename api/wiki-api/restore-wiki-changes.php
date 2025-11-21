<?php
require_once('./wiki-api-handler.php');
require_once('../auth-api/auth-api-handler.php');
$auth = new AuthApiHandler();
$apiHandler = new WikiApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);

//check required parameters         MARK:parameters
$reqparameter=['token','wikiChanges_id'];
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
$wikiChanges_id=$input['wikiChanges_id'];
$token=$input['token'];

//optional parameters



//example method call
$response=$apiHandler->restoreWiki($customer_id, $token);
echo $response;

?>