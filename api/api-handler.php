<?php
require_once __DIR__ . "/auth-api/auth-api-handler.php";
require_once __DIR__ . '/config/db.php';
class BaseApiHandler{
    protected $conn;
    protected $auth;


    

public function __construct() {
    $this->auth = new AuthApiHandler();
    try {
            $this->conn = Database::getInstance()->conn();
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    
}

public function __destruct() {
    $this->conn = null;
}

    //MARK:isBanned
    protected function isBanned($userId, $service) {
        if ($service!="user"){
            try {
                // Build the SQL query with a dynamic column name (safe because we validate it)
                $sql = "
                    SELECT *
                    FROM ban
                    WHERE user_id = :user_id
                      AND $service = 1
                      AND expiration_date > NOW()
                    LIMIT 1
                ";
        
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    ':user_id' => $userId
                ]);
        
                $result = $stmt->fetch();
        
                if ($result) {
                    return [
                        "status" => "error",
                        "message" => "User is banned",
                        "service" => $service,
                        "reason" => $result["reason"],
                        "expires" => $result["expiration_date"]
                    ];
                }
        
                return ["status" => "success"];
        
            } catch (PDOException $e) {
                return [
                    "status" => "error",
                    "message" => "Database error: " . $e->getMessage()
                ];
            }
        } else {return ["status" => "success"];}
        // Only allow safe service names
        
    }
    //MARK:dontHaveService
    protected function dontHaveService($session_key, $service){    

        //verify
        //send back if token invalid so they loggin again
        $url = "https://theprovider.ntigskovde.se/verify";

        $data = [
            "session_key" => $session_key
        ];
        
        $jsonData = json_encode($data);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,      // return response instead of printing
            CURLOPT_POST => true,                // POST request
            CURLOPT_POSTFIELDS => $jsonData,     // JSON body
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Content-Length: " . strlen($jsonData)
            ]
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);

        if (!$result) {
            return [
                "status" => "error",
                "message" => "cURL error: " . curl_error($ch)
            ];
        }
        if ($result["valid"]!=true){
            return [
                "status" => "error",
                "message" => "seassion token invalid"
            ];
        }
        //check if customer has that services 
        $services=[];
        switch ($result["services"]){
            case 1:
                $services=["user","calendar"];
                break;
            case 2:
                $services=["user","blog"];
                break;
            case 3:
                $services=["user","calendar","blog"];
                break;
            case 4:
                $services=["user","wiki"];
                break;
            case 5:
                $services=["user","wiki","calendar"];
                break;
            case 6:
                $services=["user","wiki","blog"];
                break;
            case 7:
                $services=["user","calendar","wiki","blog"];
                break;
            default:
            return [
                "status" => "error",
                "message" => "respons from provider api invalid or something went wrong"
            ];
        }

        if (in_array($service, $services)){
        return [
            "status" => "success",
            "message" => "you have this service"
        ];  
        }


        

    }

    //MARK:serviceCheck
    protected function serviceCheck($tokeninfo, $service ,$checkagainstprovider=false){  //returns array
/*         return[
            "status" => "error",
            "message" => "fyou" . json_encode($tokeninfo),
            "message2" => "fyou2" . $tokeninfo["username"]
        ]; */
        if ($checkagainstprovider){
            $providerServiceCheck=$this->dontHaveService($tokeninfo["session_key"], $service);
        } else $providerServiceCheck=[
            "status" => "success",
            "message" => "ignored Provider check"
        ]; 
        
        
         //true if not service they have
        $banCheck=$this->isBanned($tokeninfo["userId"], $service); //true if user is banned from that service
        if($providerServiceCheck['status']!="success"){
            return $providerServiceCheck;

        } else if ($banCheck['status']!="success") {
            return $banCheck;

        } else {
            //user has permission 
            return[
                "status" => "success",
                "message" => "User has permissions and not banned from this service."
            ];

        }
        
    }

//MARK:tokenHandler
    protected function tokenHandler($token){ //returns array
        //handle the token check and give back the information in 
        
        //verify token
        $authResult=$this->auth->verifyAuthToken($token);
        if($authResult['status']!="success"){
            //maybe make return just an assoc array
            $data=$authResult;
            
            return $data; //normal assoc array
        } else {
            return $data = [
                "status" => $authResult['status'],
                "username" => $authResult["data"]["username"],
                "userId" => $authResult["data"]["userId"],
                "type" => $authResult["data"]["type"],
                "customer_id" => $authResult["data"]["customer_id"],
                "session_key" => $authResult["data"]["session_key"]

            ];
            
        }

    }
    //MARK:checkServiceAndToken
    protected function checkServiceAndToken($token, $service){  //returns normal array that can just be json encoded        if array[status]!=success
        //make sure service is a provided one
        $tokeninfolog=[$token];
        //return $tokeninfolog;

        $service = strtolower($service); 
        $allowedServices = ['wiki', 'blog', 'user', 'calendar'];
        //check if service is right before trying to execute anything else
        if (!in_array($service, $allowedServices, true)) {
            $responsData=[];
            $message="Invalid service type.";
            $this->error($message, $responsData, 400);
        }

        $tokeninfo=$this->tokenHandler($token);
        if ($tokeninfo['status']!="success"){
            $responsData=[];
            $message=$tokeninfo['message'];
            $this->error($message, $responsData, 400);
           // return $tokeninfo; //handle json encoding outside function
        }
        
        $serviceCheck=$this->serviceCheck($tokeninfo, $service);
        if ($serviceCheck["status"]!="success"){
            $responsData=[];
            $message=$serviceCheck["message"];
            $this->error($message, $responsData, 400);
           // return $serviceCheck;  //handle json encoding outside function
        }

        return $tokeninfo; //if user has complete permissions just the tokens info is returned   assoc array
    }

    protected function getImagesFromContent($content, $addTo, $customerId, $userId) {
        try{
            // echo "add to: ".$addTo;
            // echo "kund: ".$customerId;
            // echo "user: ".$userId;
            if($addTo == "wiki"){
                $stmt = $this->conn->prepare("SELECT id FROM wiki WHERE user_id = :userId"); 
            }else if($addTo == "blog"){
                $stmt = $this->conn->prepare("SELECT id FROM blog WHERE user_id = :userId"); 
            }
            $stmt->execute(["userId" => $userId]);
            $addToId = $stmt->fetchAll();

            $pattern = '/(?:https?:\/\/[^\s"\'<>()]+|data:image\/[a-zA-Z0-9+\/]+;base64,[A-Za-z0-9+\/=]+)/i';

            preg_match_all($pattern, $content, $matches);

            $imageUrls = $matches[0];

            //print_r($imageUrls);

            foreach($imageUrls as $index) { 
                if($addTo == "wiki"){
                    $stmt = $this->conn->prepare("INSERT INTO img (img_url, customer_id, wiki_id) VALUES (:imgUrl, :customerId, :addTo)");
                    $addTo .= "_id";
                } else if($addTo == "blog"){
                    $stmt = $this->conn->prepare("INSERT INTO img (img_url, customer_id, blog_id) VALUES (:imgUrl, :customerId, :addTo)");
                    $addTo .= "_id";
                }
                $stmt->execute(["imgUrl" => $index, "customerId" => $customerId, "addTo" => $addToId[0]['id']]);
            }
        }catch (PDOException $e) {
            $responsData=[];
            $message="Database error: " . $e->getMessage();
            $this->error($message, $responsData, 500);
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


    //MARK: sanitizeInput
    /* should work for
    strings
    null
    ints
    floats
    booleans
    arrays (recursively)
    objects (recursively → treated like arrays) 
    */
    public function sanitize_for_db($input) {  //without log

        // If null, boolean, int, float → safe to return as-is
        if (is_null($input) || is_bool($input) || is_int($input) || is_float($input)) {
            return $input;
        }
    
        // Arrays → sanitize each field recursively
        if (is_array($input)) {
            $clean = [];
            foreach ($input as $key => $value) {
                $clean[$key] = $this->sanitize_for_db($value);
            }
            return $clean;
        }
    
        // Objects → convert to array and sanitize recursively
        if (is_object($input)) {
            $input = (array)$input;
            return $this->sanitize_for_db($input);
        }
    
        // Everything else → turn into string
        $input = (string)$input;
    
        // Ensure proper UTF-8 (fix or remove invalid sequences)
        $input = mb_convert_encoding($input, 'UTF-8', 'UTF-8');
    
        // Remove MySQL-invalid control chars (except newline, tab)
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $input);
    
        // Remove null bytes
        $input = str_replace("\0", "", $input);
    
        // Trim dangerous whitespace
        return trim($input);
    }
    // example do this before inserting to db
    // $content = $this->sanitize_for_db($_POST["content"]);

    // $stmt = $pdo->prepare("INSERT INTO articles (content) VALUES (:content)");
    // $stmt->execute(["content" => $content]);

    public function sanitize_for_db_with_log($input, &$removed = [], $path = '') {

        // ---- Simple scalar types (safe) ----
        if (is_null($input) || is_bool($input) || is_int($input) || is_float($input)) {
            return $input;
        }
    
        // ---- Arrays: sanitize recursively ----
        if (is_array($input)) {
            $clean = [];
            foreach ($input as $key => $value) {
                $clean[$key] = $this->sanitize_for_db_with_log(
                    $value,
                    $removed,
                    $path . "[$key]"
                );
            }
            return $clean;
        }
    
        // ---- Objects: treat as associative array ----
        if (is_object($input)) {
            $arr = (array)$input;
            return $this->sanitize_for_db_with_log($arr, $removed, $path);
        }
    
        // ---- Convert everything else to string ----
        $str = (string)$input;
    
        // UTF-8 fix step
        $converted = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
    
        // Detect invalid UTF-8 removed by conversion
        if ($converted !== $str) {
            $this->log_utf8_differences($str, $converted, $removed, $path);
        }
    
        $str = $converted;
    
        // Remove control characters except \n and \t
        $str = preg_replace_callback(
            '/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/u',
            function ($m) use (&$removed, $path) {
                $this->log_removed_char($m[0], $removed, $path, "mysql-invalid control char");
                return '';
            },
            $str
        );
    
        // Remove null bytes (extra safety)
        $str = str_replace("\0", "", $str, $nullCount);
        if ($nullCount > 0) {
            $this->log_removed_char("\0", $removed, $path, "null byte");
        }
    
        // Trim with removal logging
        $trimmed = trim($str);
        if ($trimmed !== $str) {
            $this->log_removed_char(substr($str, 0, strlen($str) - strlen($trimmed)), $removed, $path, "leading/trailing whitespace trimmed");
        }
    
        return $trimmed;
    }
    
    // ---------------------------------------------------
    
    private function log_removed_char($char, &$removed, $path, $reason) {
        $removed[] = [
            "char" => $char,
            "hex" => strtoupper(bin2hex($char)),
            "path" => $path,
            "reason" => $reason
        ];
    }
    
    private function log_utf8_differences($before, $after, &$removed, $path) {
        $lenBefore = strlen($before);
        for ($i = 0; $i < $lenBefore; $i++) {
            if (!isset($after[$i]) || $after[$i] !== $before[$i]) {
                $char = $before[$i];
                $this->log_removed_char($char, $removed, $path, "invalid UTF-8 sequence");
            }
        }
    }
/*
example return
[
    "clean" => "hello world",
    "removed" => [
        ["char" => "\x00", "hex" => "00", "pos" => 2, "reason" => "null byte"],
        ["char" => "\x1F", "hex" => "1F", "pos" => 5, "reason" => "mysql-invalid control char"]
    ]
]
 
*/
/* usage ex
$removedLog = [];
$clean = $apiHandler->sanitize_for_db_with_log($userInput, $removedLog);

var_dump($clean);
var_dump($removedLog);

*/




//MARK: checkType
public function checkType($value, $allowed, string $fieldName = "value") {

    // If a single string is given, convert it to an array
    if (is_string($allowed)) {
        $allowed = [$allowed];
    }

    // --- Skip type validation ONLY if value is null or empty string ---
    if ($value === null || $value === "") {
        return $this->sanitize_for_db($value);
    }

    // If "any" is in allowed, skip type validation
    if (in_array("any", $allowed, true)) {
        return $this->sanitize_for_db($value);
    }

    // Detect actual type
    $type = gettype($value);

    // Normalize PHP types
    $map = [
        "boolean" => "bool",
        "integer" => "int",
        "double"  => "float",
        "string"  => "string",
        "array"   => "array",
        "NULL"    => "null",
        "object"  => "object",
    ];

    $normalized = $map[$type] ?? $type;

    // --- Validation ---
    if (!in_array($normalized, $allowed, true)) {

        $this->error(
            "Invalid type for '$fieldName'. Got '$normalized', expected: " . implode(" | ", $allowed),
            [],
            400
        );
        exit;
    }

    // --- Sanitization ---
    return $this->sanitize_for_db($value);
}





public function validateDateInput($date, $dateType = "") {
    if ($date != "") {
        if ($dateType != "") {
            if ($dateType == "date") {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    return;
                }
            } else if ($dateType == "dateSeconds") {
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date)) {
                    return;
                }
            }
        } else {
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return;
            }
        }
        $this->error(
            "Invalid date input.",
            [],
            400
        );
    }


}










}
//maybe add when getting html as a optional safety
// require_once "htmlpurifier/HTMLPurifier.auto.php";
// $purifier = new HTMLPurifier();
// $clean_html = $purifier->purify($input_html);

