<?php
require_once "./php-jwt-6.11.1/src/JWT.php";
require_once "./php-jwt-6.11.1/src/Key.php";
require_once "./php-jwt-6.11.1/src/JWTExceptionWithPayloadInterface.php";
require_once "./php-jwt-6.11.1/src/BeforeValidException.php";
require_once "./php-jwt-6.11.1/src/ExpiredException.php";
require_once "./php-jwt-6.11.1/src/SignatureInvalidException.php";
require "../api-handler.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function loadEnvFile ($path) {
    if (!file_exists($path)) {
        throw new Exception(".env file not found: " . $path);
        echo "file not found";
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Split key=value
        list($name, $value) = explode('=', $line, 2);

        $name = trim($name);
        $value = trim($value);

        // Remove surrounding quotes
        $value = trim($value, "'\"");

        // Set in environment
        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

class AuthApiHandler extends BaseApiHandler {
    function __construct() {
        loadEnvFile("../../.env");
        parent::__construct();
    }

    function getAuthToken(string $username, int $userId, string $type): string {
        $payload = [
            "username" => $username,
            "userId" => $userId,
            "type" => $type
        ];

        $jwtToken = JWT::encode($payload, $_ENV["JWT_SECRET"], "HS256");

        $stmt = $this->conn->prepare("SELECT * FROM user WHERE id = :id");
        $success = $stmt->execute([":id" => $userId]);

        if ($success) {
            if ($stmt->fetch()) {
                return json_encode($jwtToken);
            }
            else {
                return json_encode(["status" => "error", "message" => "user not found"]);
            }
        }
        else {
            return json_encode(["status" => "error", "message" => "Sql query failed"]);
        }
    }
    

    function verifyAuthToken($jwtToken) {
        try {
            $decoded = JWT::decode($jwtToken, new Key($_ENV["JWT_SECRET"], "HS256"));

            $stmt = $this->conn->prepare("SELECT * FROM user WHERE id = :id");
            $success = $stmt->execute([":id" => $decoded->userId]);

            if ($success) {
                if ($stmt->fetch()) {
                    return json_encode($decoded);
                }
                else {
                    return json_encode(["status" => "error", "message" => "user not found"]);
                }
            }
            else {
                return json_encode(["status" => "error", "message" => "Sql query failed"]);
            }
            
        }
        catch (Exception) {
            echo json_encode(["status" => "error", "message" => "decode of token failed"]);
        }


    }
}