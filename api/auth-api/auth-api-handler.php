<?php
require_once __DIR__ . '/php-jwt-6.11.1/src/JWT.php';
require_once __DIR__ . '/php-jwt-6.11.1/src/Key.php';
require_once __DIR__ . '/php-jwt-6.11.1/src/JWTExceptionWithPayloadInterface.php';
require_once __DIR__ . '/php-jwt-6.11.1/src/BeforeValidException.php';
require_once __DIR__ . '/php-jwt-6.11.1/src/ExpiredException.php';
require_once __DIR__ . '/php-jwt-6.11.1/src/SignatureInvalidException.php';
require_once __DIR__ . '/../api-handler.php';
require_once __DIR__ . '/../config/db.php';

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

class AuthApiHandler {
    protected $conn;

    function __construct() {
        loadEnvFile("../../.env");
        //load database
        try {
            $this->conn = Database::getInstance()->conn();
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }

    }


    // Return a token that includes username, userid, user type and customer id
    function getAuthToken(string $username, string $password, $sessionKey){  

        //Check if the username exists in the database
        $userInfoStmt = $this->conn->prepare("SELECT * FROM user WHERE username = :username");
        $userInfoStmt->execute([":username" => $username]);

        $user = $userInfoStmt->fetch();

        if (!$user) {
            $responsData=[];
            $message="Invalid username or password";
            $this->error($message, $responsData, 400);
            
        }
    
        // 2. Verify the password
        if (!password_verify($password, $user['password'])) {
            $responsData=[];
            $message="Invalid username or password";
            $this->error($message, $responsData, 400);
            
        }

        $payload = [
            "username" => $username,
            "userId" => $user["id"],
            "type" => $user["type"],
            "customer_id" => $user["customer_id"],
            "session_key" => $sessionKey
        ];


        $jwtToken = JWT::encode($payload, $_ENV["JWT_SECRET"], "HS256");
        $responsData=["token" => $jwtToken];
        $message="Token retrieved successfully";
        $this->success($message, $responsData, 200);
    }

    // input jwt token and returns user information
    function verifyAuthToken($jwtToken) {                       //returns array or echos errors
        //return json_encode(["status" => "error", "message" => $jwtToken]);

        try {
            $decoded = JWT::decode($jwtToken, new Key($_ENV["JWT_SECRET"], "HS256"));

            $stmt = $this->conn->prepare("SELECT * FROM user WHERE id = :id");
            $success = $stmt->execute([":id" => $decoded->userId]);

            $decodedArray = (array) $decoded;
            if ($success) {
                if ($stmt->fetch()) {
                    return ["data"=>$decodedArray, "status" => "success"]; 
                }
                else {
                    $responsData=[];
                    $message="user not found";
                    $this->error($message, $responsData, 400);
                    
                }
            }
            else {
                $responsData=[];
                $message="Sql query failed";
                $this->error($message, $responsData, 400);
                
            }
            
        }
        catch (Exception $e) {
            $responsData=[];
            $message="invalid token " . $e;
            $this->error($message, $responsData, 400);
        }


    }



    // ---- CORE SENDER ---- MARK:Response
    protected function sendResponse($status, $httpCode, $message = "", $data = []) { //IMPORTANT it echos and exit imediatly    AND data should always be assoc array

        //data always assoc array even empty
        if ($data === [] || $data === null) {
            $data = (object)[];
        }
    
        http_response_code($httpCode);

        $payload = [
            "status"  => $status,
            "message" => $message,
            "data"    => $data
        ];

        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); //Leaves / unescaped    Leaves Unicode characters as-is 
        exit;
    }

    // ---- SUCCESS ----
    public function success($message = "Success", $data = [], $httpCode = 200) {
        $this->sendResponse("success", $httpCode, $message, $data);
    }

    // ---- ERROR ----
    public function error($message = "Error", $data = [], $httpCode = 400) {
        $this->sendResponse("error", $httpCode, $message, $data);
    }
}