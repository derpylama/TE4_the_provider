<?php
require_once __DIR__ . '/php-jwt-6.11.1/src/JWT.php';
require_once __DIR__ . '/php-jwt-6.11.1/src/Key.php';
require_once __DIR__ . '/php-jwt-6.11.1/src/JWTExceptionWithPayloadInterface.php';
require_once __DIR__ . '/php-jwt-6.11.1/src/BeforeValidException.php';
require_once __DIR__ . '/php-jwt-6.11.1/src/ExpiredException.php';
require_once __DIR__ . '/php-jwt-6.11.1/src/SignatureInvalidException.php';
require_once __DIR__ . '/../api-handler.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function loadEnvFile ($path) {
    if (!file_exists($path)) {
        echo "file not found";
        throw new Exception(".env file not found: " . $path);
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

    function getAuthToken(string $username, string $password){

        $userInfoStmt = $this->conn->prepare("SELECT * FROM user WHERE username = :username");
        $userInfoStmt->execute([":username" => $username]);

        $user = $userInfoStmt->fetch();

        if (!$user) {
            return json_encode(["status" => "error", "message" => "Invalid username or password"]);
        }
    
        // 2. Verify the password
        if (!password_verify($password, $user['password'])) {
            return json_encode(["status" => "error", "message" => "Invalid username or password"]);
        }

        $payload = [
            "username" => $username,
            "userId" => $user["id"],
            "type" => $user["type"],
            "customer_id" => $user["customer_id"]
        ];


        $jwtToken = JWT::encode($payload, $_ENV["JWT_SECRET"], "HS256");
        return json_encode(["token" => $jwtToken, "status" => "success"]);

    }

    function verifyAuthToken($jwtToken) {
        try {
            $decoded = JWT::decode($jwtToken, new Key($_ENV["JWT_SECRET"], "HS256"));

            $stmt = $this->conn->prepare("SELECT * FROM user WHERE id = :id");
            $success = $stmt->execute([":id" => $decoded->userId]);

            $decodedArray = (array) $decoded;
            if ($success) {
                if ($stmt->fetch()) {
                    return json_encode([$decodedArray, "status" => "success"] );
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
            return json_encode(["status" => "error", "message" => "invalid token"]);
        }


    }
}