<?php
require_once('./wiki-api-handler.php');
$apiHandler = new WikiApiHandler();
//get input data
$input=json_decode(file_get_contents('php://input'), true);


//tokenthing here if needed



//check required parameters         MARK:parameters
$reqparameter=['title', 'author_id'];
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
$title=$input['title'];
$author_id=$input['author_id'];

//optional parameters
$content=$input['content'] ?? ''; //default to empty string if not provided only needed for non required parameters


//example method call
$response=$apiHandler->createWiki($title, $content, $author_id);
echo $response;

?>