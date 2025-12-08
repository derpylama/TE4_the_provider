<?php
require_once('./wiki-api-handler.php');
require_once('../auth-api/auth-api-handler.php');
$auth = new AuthApiHandler();
$apiHandler = new WikiApiHandler();

// Get headers
$header = getallheaders();

// Check Authorization Header
if (!isset($header["Authorization"])) {
    $apiHandler->error("Missing Authorization Header", [], 401);
    exit;
}

// Check if it is a Bearer Token
if (substr($header["Authorization"], 0, 7) !== "Bearer ") {
    $apiHandler->error("Invalid Authorization Header", [], 401);
    exit;
}

$token = substr($header["Authorization"], 7);

// //get input data
// $input=json_decode(file_get_contents('php://input'), true);

// check if the request method is GET
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    $apiHandler->error("Invalid request method", [], 405);
    exit;
}

$input = $_GET;

//check required parameters         MARK:parameters


//set all parameters 

//required parameters

//optional parameters

$wiki_article_id=$input["wiki_article_id"] ?? "";
$searchQuery=$input["search_query"] ?? "";
$searchFilter=$input["search_filter"] ?? ["title"];
$amount=$input["amount"] ?? 10;
$offset=$input["offset"] ?? 0;
$orderDirection=$input["order_direction"] ?? "DESC";  //newest to oldest is defualt

//type checking
$wiki_article_id= $apiHandler->checkType($wiki_article_id, "int", "wiki_article_id");
$searchQuery= $apiHandler->checkType($searchQuery, "string", "search_query");
$searchFilter= $apiHandler->checkType($searchFilter, "array", "search_filter");
$amount= $apiHandler->checkType($amount, "int", "amount");
$offset= $apiHandler->checkType($offset, "int", "offset");
$orderDirection= $apiHandler->checkType($orderDirection, "string", "order_direction");

$wiki_id = $input["wiki_id"] ?? 0;
$wiki_id = $apiHandler->checkType($wiki_id, "int", "wiki_id");



//makeing sure order direction is valid to prevent sql injection
$orderDirection = strtoupper($orderDirection);
if (!in_array($orderDirection, ['ASC', 'DESC'])) {
    $apiHandler->error("order_direction must be ASC or DESC. You entered: " .$orderDirection , [], 400);
}

//example method call
$response=$apiHandler->getWikiArticle($token, $wiki_article_id, $searchQuery, $searchFilter, $amount, $offset, $orderDirection, $wiki_id);
//what it does is 
//if wiki_id is given, it returns articles only from that wiki and searches only in that wiki
//if wiki_article_id is given, it returns that specific article



echo $response;

?>