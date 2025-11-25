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


    protected function tokenHandler($token){ //returns array
        //handle the token check and give back the information in 
        
        //verify token
        $authResult=json_decode($this->auth->verifyAuthToken($token), true);
        if($authResult['status']!="success"){
            //maybe make return just an assoc array
            $data=$authResult;
            
            return $data; //normal assoc array
        } else {
            return $data = [
                "status" => $authResult['status'],
                "username" => $authResult[0]["username"],
                "userId" => $authResult[0]["userId"],
                "type" => $authResult[0]["type"],
                "customer_id" => $authResult[0]["customer_id"],
                "session_key" => $authResult[0]["session_key"]

            ];
            
        }

    }

    protected function checkServiceAndToken($token, $service){  //returns normal array that can just be json encoded        if array[status]!=success
        //make sure service is a provided one
        $tokeninfolog=[$token];
        //return $tokeninfolog;

        $service = strtolower($service); 
        $allowedServices = ['wiki', 'blog', 'user', 'calendar'];
        //check if service is right before trying to execute anything else
        if (!in_array($service, $allowedServices, true)) {
            return [
                "status" => "error",
                "message" => "Invalid service type."
            ];
        }

        $tokeninfo=$this->tokenHandler($token);
        if ($tokeninfo['status']!="success"){
            return $tokeninfo; //handle json encoding outside function
        }
        
        $serviceCheck=$this->serviceCheck($tokeninfo, $service);
        if ($serviceCheck["status"]!="success"){
            return $serviceCheck;  //handle json encoding outside function
        }

        return $tokeninfo; //if user has complete permissions just the tokens info is returned
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
            return json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }  
    }
}

