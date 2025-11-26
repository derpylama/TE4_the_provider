<?php

// $message="";
// $this->error($message, [], 400); 

require_once('../api-handler.php');
require_once('../auth-api/auth-api-handler.php');
class UserApiHandler extends BaseApiHandler{

    protected function checkServiceAndToken($token, $service="user"){
        return parent::checkServiceAndToken($token, $service);
    }

    public function getUsers($token) {//example method
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
        }

        //---------------------------------------------------------------------
        $stmt = $this->conn->query("SELECT * FROM users");
        return $stmt->fetchAll();
    }
    public function addUser($token, string $mail, string $adress, int $employmentNumber, string $birthDate, string $username, string $password, string $type, string $general) {
        if ($token!="TESTtokenfo12rtest312ingporpos3123es-2131doremov23ethis-befor1eac321tually-gvining3itouttotheconsummer")
        {       
        //Token---------------------------------------------------------------
                $tokeninfo=$this->checkServiceAndToken($token); 
                if($tokeninfo['status']!="success"){
                    $message=$tokeninfo["message"];
                    $this->error($message, [], 400);
                }
        
                //check user permissions
                if ($tokeninfo['type'] != 'admin') {
                    $message="Insufficient permissions";
                    $this->error($message, [], 400); 
                }
        
                //---------------------------------------------------------------------
                $customerId=$tokeninfo["customer_id"];
        }else { //remove this if when product is complete 
            $customerId= 999;
            }
        try {
            //veryfies if username already exists
            $stmt = $this->conn->prepare("SELECT 1 FROM user WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            if ($stmt->fetchColumn()) {
                $message="Username already exists";
                $this->error($message, [], 400); 
            }
            
            $stmt = $this->conn->prepare("INSERT INTO user (customer_id, mail, adress, employment_number, birthdate, username, password, type, general) VALUES (:customer_id, :mail, :adress, :employment_number, :birthdate, :username, :password, :type, :general)");
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt->execute([
                ":customer_id" => $customerId, 
                ":mail" => $mail,
                ":adress" => $adress,
                "employment_number" => $employmentNumber,
                ":birthdate" => $birthDate,
                ":username" => $username,
                ":password" => $hashedPassword,
                ":type" => $type,
                ":general" => $general
            ]);

            $stmt = $this->conn->prepare("SELECT id FROM user WHERE username = :username");
            $stmt->execute(["username" => $username]);
            $result = $stmt->fetch();
            $id = $result["id"];

            $responsData=["username" => $username, "type" => $type, "id" => $id];
            $message="User added";
            $this->success($message, $responsData, 200);
          
        } catch(PDOException $e) {
          $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }  
    }
    public function getUser($token ,$id, $username) { //currently only admin
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] != 'admin') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
        }
        //---------------------------------------------------------------------
        $customerId=$tokeninfo["customer_id"];

        try {
            if ($id != 0) {
                $stmt = $this->conn->prepare("SELECT id, customer_id, mail, adress, employment_number, birthdate, username, type, creation_date, latest_update, general FROM `user` WHERE id =:id ");
                $stmt->execute([":id"=>$id]);
            } else {
                $stmt = $this->conn->prepare("SELECT id, customer_id, mail, adress, employment_number, birthdate, username, type, creation_date, latest_update, general FROM `user` WHERE username =:username ");
                $stmt->execute([":username"=>$username]);               
            }
            $userInfo = $stmt->fetch();
            
            //Verifies that the requested user exists
            if (!$userInfo) {
                $message="User with either that id and or username doesnt exist";
                $this->error($message, [], 400); 
            }
            //verifies if user is registered to correct customer
            if ($userInfo["customer_id"] != $customerId) {
                $message="No access";
                $this->error($message, [], 400); 
            }

            $responsData=[];
            $message="retrived user:".$userInfo["username"]."data";
            $this->success($message, $userInfo, 200);

            
            
        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }
    public function banUser($token, $banUserId, $expirationDate, $blogBan, $wikiBan, $calendarBan, $reason) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] != 'admin') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
        }

        //---------------------------------------------------------------------
        $customerId=$tokeninfo["customer_id"];
        $banningUser=$tokeninfo["userId"];

        try {
            $stmt = $this->conn->prepare("SELECT customer_id, type, id FROM user WHERE id =:id ");
            $stmt->execute([":id"=>$banUserId]);
            $userInfo = $stmt->fetch();
            $userCustomerId = $userInfo["customer_id"];
            //verifies if user is registered to correct customer
            if ($userCustomerId != $customerId) {
                $message="No access";
                $this->error($message, [], 400); 
            }
            //verify that the ban target user is not an admin
            if ($userInfo["type"] == 'admin') {
                $message="Target is an admin";
                $this->error($message, [], 400); 
            }
            //verify that admin is not banning their own account
            if ($banUserId == $banningUser) {
                $message="Cant ban your own account";
                $this->error($message, [], 400); 

            }
            


            $stmt = $this->conn->prepare("INSERT INTO ban (user_id, expiration_date, blog, wiki, calendar, reason) VALUES (:user_id, :expiration_date, :blog, :wiki, :calendar, :reason)");
            $stmt->execute([
                ":user_id" => $banUserId, 
                ":expiration_date" => $expirationDate,
                ":blog" => $blogBan,
                ":wiki" => $wikiBan,
                ":calendar" => $calendarBan,
                ":reason" => $reason
                ]);

            $responsData=[];
            $message="user".$banUserId." has been banned successfully.";
            $this->success($message, $responsData, 200);

        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }  
    }
    public function editUser($token, $editUserId, $mail, $adress, $employmentNumber, $birthDate, $username, $password, $type, $general) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
        }

        //---------------------------------------------------------------------
        $customerId=$tokeninfo["customer_id"];
        $id=$editUserId ?? $tokeninfo["userId"];
        
        
        try {
            if ($password != null) {
                $newPassword = password_hash($password, PASSWORD_DEFAULT);
            } else {
                $newPassword = null;
            }
            $getStmt = $this->conn->prepare("SELECT customer_id FROM user WHERE id = :id");
            $getStmt->execute([":id"=>$editUserId]);
            $userInfo = $getStmt->fetch();
            //verifies if user is registered to correct customer

            if ($userInfo["customer_id"] != $customerId) {
                $message="No access";
                $this->error($message, [], 400); 
            }
            
            
            $editField = [
                "mail" => $mail,
                "adress" => $adress,
                "employment_number" => $employmentNumber,
                "birthdate" => $birthDate,
                "username" => $username,
                "password" => $newPassword,
                "type" => $type,
                "general" => $general
            ];

            $editStringList = [];
            $valueList = [];

            foreach($editField as $editString => $variable){
                if ($variable != null) {
                    $editStringList[] = "$editString = :$editString";
                    $valueList[":$editString"] = $variable;
                }
            }
            $valueList[":id"] = $id;
            $editsString = implode(", ", $editStringList);
            $sqlExecute = "UPDATE user SET ".$editsString." WHERE id = :id";
            
            $stmt = $this->conn->prepare($sqlExecute);
            $stmt->execute($valueList);
            $responsData=[];
            $message="User edited";
            $this->success($message, $responsData, 200);
 

        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }  
    }
    public function getAllUsers($token, $request) { //only admin?
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] != 'admin') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
        }

        //---------------------------------------------------------------------
        $customerId=$tokeninfo["customer_id"];
        try {
            if ($request!=null or !empty($request)) {
                $stmtSelect = [
                    "id", 
                    "customer_id",
                    "mail",
                    "adress",
                    "employment_number",
                    "birthdate",
                    "username",
                    "type",
                    "creation_date",
                    "latest_update"
                    ];


                $selectArray = [];

                $selectArray = array_intersect($stmtSelect, $request);

                $selectString = implode(", ", $selectArray);
                $sqlExecute = "SELECT ".$selectString." FROM user WHERE customer_id = :customer_id";

                $stmt = $this->conn->prepare($sqlExecute);
            } else {
                $stmt = $this->conn->prepare("SELECT id, customer_id, mail, adress, employment_number, birthdate, username, type, creation_date, latest_update FROM user WHERE customer_id = :customer_id");
            }
            $stmt->execute([":customer_id" => $customerId]);
            $userInfo = $stmt->fetchAll();
            return json_encode([
                "status" => "success",
                "message" => "retrived all users belonging to this orginisation",
                "data" => $userInfo        
            ]);
        } catch (PDOException $e) {
            $message=;"Database error: " . $e->getMessage()
            $this->error($message, [], 400);
        }
        /*
        try {
            $stmt = $this->conn->prepare("SELECT id, customer_id, mail, adress, employment_number, birthdate, username, type, creation_date, latest_update FROM user WHERE customer_id = :customer_id");
            $stmt->execute([":customer_id"=>$customerId]);
            $userInfo = $stmt->fetchAll();

            $responsData=[];
            $message="retrived all users belonging to this orginisation";
            $this->success($message, $userInfo, 200);

        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }  
        */
    }
    public function getAllBannedUsers($token, $request) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
 
        }

        //check user permissions
        if ($tokeninfo['type'] != 'admin') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
        }

        //---------------------------------------------------------------------
        $customerId=$tokeninfo["customer_id"];

        try {
            if ($request!=null or !empty($request)) {
                $stmtSelect = [
                    "id", 
                    "customer_id",
                    "mail",
                    "adress",
                    "employment_number",
                    "birthdate",
                    "username",
                    "type",
                    "creation_date",
                    "latest_update"
                    ];
                $selectArray = [];
                $selectArray = array_intersect($stmtSelect, $request);
                $selectString = implode(", user.", $selectArray);
                $selectString ="user.".$selectString;
                $sqlExecute = "SELECT ".$selectString." FROM user INNER JOIN ban ON user.id = ban.id WHERE customer_id = :customer_id";
                $stmt = $this->conn->prepare($sqlExecute);
            } else {
                $stmt = $this->conn->prepare("SELECT user.id, user.customer_id, user.mail, user.adress, user.employment_number, user.birthdate, user.username, user.type, user.creation_date, user.latest_update FROM user INNER JOIN ban ON user.id = ban.user_id WHERE customer_id = :customer_id");
            }

            $stmt->execute([":customer_id" => $customerId]);
            $userInfo = $stmt->fetchAll();

            $responsData=[$userInfo];
            $message="retrieved all users belonging to this organisation";
            $this->success($message, $responsData, 200);

        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }  
    }
    public function removeUser($removeUserId, $token) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] != 'admin') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
        }

        //---------------------------------------------------------------------
        $customerId=$tokeninfo["customer_id"];
        //check so user isent trying to remove himself
        if ($tokeninfo['userId'] == $removeUserId) {
            $message="Cant remove your own admin account";
            $this->error($message, [], 400); 
        }

        try {
            $getStmt = $this->conn->prepare("SELECT customer_id FROM user WHERE id = :id");
            $getStmt->execute([":id"=>$removeUserId]);
            $userInfo = $getStmt->fetch();
            //verifies if user is registered to correct customer
            if ($userInfo["customer_id"] != $customerId) {
                $message="No access";
                $this->error($message, [], 400); 
            }
            $stmt = $this->conn->prepare("DELETE FROM user WHERE id = :id");
            $stmt->execute([":id"=>$removeUserId]);

            $responsData=[];
            $message="removed user";
            $this->success($message, $responsData, 200);
        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }  
    }    
    public function removeBan($removeBanId, $token) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] != 'admin') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
        }

        //---------------------------------------------------------------------
        $customerId=$tokeninfo["customer_id"];

        try {



            


            $stmt = $this->conn->prepare("SELECT user.customer_id FROM user INNER JOIN ban ON user.id = ban.user_id WHERE ban.id = :id");
            $stmt->execute([":id"=>$removeBanId]);
            $userInfo = $stmt->fetch();
            $userCustomerId = $userInfo["customer_id"];
            //verify if ban exists
            if ($userCustomerId == false) {
                $message="Ban doesnt exist";
                $this->error($message, [], 400); 
            }
            //verify access this ban
            
            if ($userCustomerId != $customerId) {
                $message="No access to this ban";
                $this->error($message, [], 400); 
            }
            $stmt = $this->conn->prepare("DELETE FROM ban WHERE id = :id");
            $stmt->execute([":id"=>$removeBanId]);

            $responsData=[];
            $message="removed ban";
            $this->success($message, $responsData, 200);
        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }  
    }
    public function login($customerUsername, $customerPassword, $username, $password) {
        $auth = new AuthApiHandler();
        
        $url = "http://theprovider.ntigskovde.se/login";

        $data = [
            "username" => $customerUsername,
            "password" => $customerPassword
        ];

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json"
        ]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);

        if ($response === false) {
            echo "cURL Error: " . curl_error($curl);
            exit;
        }

        curl_close($curl);

        $result = json_decode($response, true);

        //print_r($result);
        echo $auth->getAuthToken($username, $password, $result['session_key']);
        // auth token handles the return echo
    }
    public function providerLogout($token, $sessionKey) {
        // //Token---------------------------------------------------------------
        // $tokeninfo=$this->checkServiceAndToken($token); 
        // if($tokeninfo['status']!="success"){
        //     return jsonencode($tokeninfo);
        // }

        // //check user permissions
        // if ($tokeninfo['type'] != 'admin') {
        //     return jsonencode([
        //         "status" => "error",
        //         "message" => "Insufficient permissions"
        //     ]);
        // }

        // //---------------------------------------------------------------------
        // $customerId=$tokeninfo["customer_id"];

        $url = "http://theprovider.ntigskovde.se/logout";

        $data = [
            "session_key" => $sessionKey
        ];

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json"
        ]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);

        if ($response === false) {
            echo "cURL Error: " . curl_error($curl);
            exit;
        }

        curl_close($curl);

        $result = json_decode($response, true);

        //print_r($result);
        //$_SESSION['session_key'] = $result['session_key'];
        //$this->dontHaveService($result['session_key']);
    }
}

?>