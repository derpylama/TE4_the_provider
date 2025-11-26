<?php
require __DIR__ . "\auth-api-handler.php";

$requiredParams = ["username", "password"];

$input = file_get_contents("php://input");
$inputArray = json_decode($input, true);

foreach ($requiredParams as $param) {
    if (!isset($inputArray[$param])){
        echo json_encode([
            "status" => "error",
            "message" => "missing required variable " . $param
        ]);
        exit;
    }
}

$apiHandler = new AuthApiHandler();

echo $apiHandler->getAuthToken($inputArray["username"], $inputArray["password"], );