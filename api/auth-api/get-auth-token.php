<?php
require "./auth-api-handler.php";

$input = file_get_contents("php://input");
$inputArray = json_decode($input, true);

$requiredParams = ["username", "userId", "type"];
$missingParams = [];

foreach ($requiredParams as $param) {
    if (!isset($inputArray[$param])){
        echo json_encode([
            "status" => "error",
            "message" => "missing required variable" . $param
        ]);
        exit;
    }
}

$apiHandler = new AuthApiHandler();

$username = $inputArray["username"];
$userId = $inputArray["userId"];
$type = $inputArray["type"];

echo $apiHandler -> getAuthToken($username, $userId, $type);