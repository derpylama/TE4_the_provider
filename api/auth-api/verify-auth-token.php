<?php
require "./auth-api-handler.php";

$apiHandler = new AuthApiHandler();

$input = file_get_contents("php://input");
$inputArray = json_decode($input, true);

if (isset($inputArray["token"]) && !empty($inputArray["token"])) {
    echo $apiHandler->verifyAuthToken($inputArray["token"]);
}
else {
    echo json_encode(["status" => "error", "message" => "parameter `token` is not set"]);
}