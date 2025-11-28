<?php

// $message="";
// $this->error($message, [], 400); 

require_once('../api-handler.php');
require_once('../auth-api/auth-api-handler.php');
class UserApiHandler extends BaseApiHandler{

    protected function checkServiceAndToken($token, $service="user"){
        return parent::checkServiceAndToken($token, $service);
    }
    private $stmtAllowedFilterTermsList = [
        "id", 
        "main_adress",
        "employment_number",
        "birthdate",
        "username",
        "type"
    ];
    private $allowedEditUserArray = [
        "main_mail",
        "first_name",
        "last_name",
        "main_adress",
        "birthdate",
        "username",
        "password",
        "general",
        "extraMail",
        "extraPhoneNumber",
        "extraAdress"
    ];
    private $allowedEditUserArrayAdmin = [
        "main_mail",
        "first_name",
        "last_name",
        "main_adress",
        "employment_number",
        "birthdate",
        "username",
        "password",
        "type",
        "general",
        "extraMail",
        "extraPhoneNumber",
        "extraAdress"
    ];
    private $getAllList = [
        "id", 
        "customer_id",
        "main_mail",
        "first_name",
        "last_name",
        "main_adress",
        "employment_number",
        "birthdate",
        "username",
        "type",
        "creation_date",
        "latest_update"
    ];
    private $getUserInfoListAdmin = [
        "id", 
        "customer_id",
        "main_mail",
        "first_name",
        "last_name",
        "main_adress",
        "employment_number",
        "birthdate",
        "username",
        "type",
        "creation_date",
        "latest_update"
    ];
    private $getUserInfoList = [
        "main_mail",
        "first_name",
        "last_name",
        "main_adress",
        "employment_number",
        "birthdate",
        "username",
        "type",
        "creation_date",
        "latest_update"
    ];


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
        $stmt = $this->conn->query("SELECT * FROM user");
        return $stmt->fetchAll();
    }
    public function addUser($token, string $mail, string $name, string $lastName, string $phoneNumber, string $adress, int $employmentNumber, string $birthDate, string $username, string $password, string $type, string $general, array $extraMail, array $extraPhoneNumber, array $extraAdress) {
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
                } else { //remove this if when product is complete 
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
            //Adds user
            $stmt = $this->conn->prepare("INSERT INTO user (customer_id, main_mail, first_name, last_name, main_adress, employment_number, birthdate, username, password, type, general) VALUES (:customer_id, :main_mail, :first_name, :last_name, :main_adress, :employment_number, :birthdate, :username, :password, :type, :general)");
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->execute([
                ":customer_id" => $customerId, 
                ":main_mail" => $mail,
                ":first_name" => $name,
                ":last_name" => $lastName,
                ":main_adress" => $adress,
                ":employment_number" => $employmentNumber,
                ":birthdate" => $birthDate,
                ":username" => $username,
                ":password" => $hashedPassword,
                ":type" => $type,
                ":general" => $general
            ]);
            //Retrives the id of the user just added
            $stmt = $this->conn->prepare("SELECT id FROM user WHERE username = :username");
            $stmt->execute(["username" => $username]);
            $result = $stmt->fetch();
            $id = $result["id"];

            $stmt = $this->conn->prepare("INSERT INTO mail (user_id, mail) VALUES (:id, :mail)");
            foreach($extraMail as $value){
                $stmt->execute(["id" => $id, "mail" => $value]);
            }
            $stmt = $this->conn->prepare("INSERT INTO adress (user_id, adress) VALUES (:id, :adress)");
            foreach($extraAdress as $value){
                $stmt->execute(["id" => $id, "adress" => $value]);
            }
            $stmt = $this->conn->prepare("INSERT INTO phone_number (user_id, phone_number) VALUES (:id, :phone_number)");
            foreach($extraPhoneNumber as $value){
                $stmt->execute(["id" => $id, "phone_number" => $value]);
            }
            //Success return
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
            $getStmt = $this->conn->prepare("SELECT customer_id, id FROM user WHERE id = :id");
            $getStmt->execute([":id"=>$id]);
            $userInfo = $getStmt->fetch();
            //verifies if user is registered to correct customer
            if ($userInfo["customer_id"] != $customerId) {
                $message="No access";
                $this->error($message, [], 400); 
            }
            //Gives the correct list for the user to edit
            if ($tokeninfo['type'] == 'admin') {
                $getInfoList = $this->getUserInfoListAdmin;
            } elseif ($userInfo["userId"] == $id) {
                $getInfoList = $this->getUserInfoList;
            } else {
                $message="Insufficient permissions";
                $this->error($message, [], 400);
            }


            $selectString = implode(", ", $getInfoList);
            $sqlExecute = "SELECT ".$selectString." FROM `user` WHERE ";

            if ($id != 0) {
                $stmt = $this->conn->prepare($sqlExecute."id = :id");
                $stmt->execute([":id"=>$id]);
            } else {
                $stmt = $this->conn->prepare($sqlExecute."username = :username");
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
    public function editUser($token, $editUserId, $mail, $firstName, $lastName, $adress, $employmentNumber, $birthDate, $username, $password, $type, $general, $extraMail, $extraPhoneNumber, $extraAdress) {
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
        
        try {
            if ($password != null) {
                $newPassword = password_hash($password, PASSWORD_DEFAULT);
            } else {
                $newPassword = null;
            }
            $getStmt = $this->conn->prepare("SELECT customer_id, id FROM user WHERE id = :id");
            $getStmt->execute([":id"=>$editUserId]);
            $userInfo = $getStmt->fetch();
            //verifies if user is registered to correct customer
            if ($userInfo["customer_id"] != $customerId) {
                $message="No access";
                $this->error($message, [], 400); 
            }
            //Gives the correct list for the user to edit
            if ($tokeninfo['type'] == 'admin') {
                $editableInfoList = $this->allowedEditUserArrayAdmin;
            } elseif ($userInfo["id"] == $editUserId) {
                $editableInfoList = $this->allowedEditUserArray;
            } else {
                $message="Insufficient permissions";
                $this->error($message, [], 400);
            }

            $editField = [
                "main_mail" => $mail,
                "first_name" => $firstName,
                "last_name" => $lastName,
                "main_adress" => $adress,
                "employment_number" => $employmentNumber,
                "birthdate" => $birthDate,
                "username" => $username,
                "password" => $newPassword,
                "type" => $type,
                "general" => $general
            ];  


            $editStringList = [];
            $valueList = [];

            foreach($editableInfoList as $editString){

                if (array_key_exists($editString, $editField) && $editField[$editString] != null) {

                    $editStringList[] = "$editString = :$editString";
                    $valueList[":$editString"] = $editField[$editString];
                }
            }

            $valueList[":id"] = $editUserId;

            $editsString = implode(", ", $editStringList);
            
            $sqlExecute = "UPDATE user SET ".$editsString." WHERE id = :id";


            $stmt = $this->conn->prepare($sqlExecute);
            $stmt->execute($valueList);

            // $stmt = $this->conn->prepare("DELETE FROM mail WHERE user_id = :user_id");
            // foreach($extraMail as $value){
            //     $stmt->execute(["id" => $id, "mail" => $value]);
            // }
            // $stmt = $this->conn->prepare("UPDATE adress (user_id, adress) VALUES (:id, :adress)");
            // foreach($extraAdress as $value){
            //     $stmt->execute(["id" => $id, "adress" => $value]);
            // }
            // $stmt = $this->conn->prepare("UPDATE phone_number (user_id, phone_number) VALUES (:id, :phone_number)");
            // foreach($extraPhoneNumber as $value){
            //     $stmt->execute(["id" => $id, "phone_number" => $value]);
            // }


            // $extraMail=[
            //     "id1"=>"mejl@nothing",
            //     "id2"=>"mejl3434@nothing",
            //     "new"=>["ewasd","dsadsa"]
            // ];



            $responsData=[];
            $message="User edited";
            $this->success($message, $responsData, 200);




            /*
            $editField = [
                "main_mail" => $mail,
                "first_name" => $firstName,
                "last_name" => $lastName,
                "main_adress" => $adress,
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
            */
 

        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }  
    }
    public function getAllUsers($token, $request, $searchAmount, $offset) { //only admin?
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
        $stmtSelect = $this->getAllList;


        $sqlLimit = "";
        if ($searchAmount != 0) {
            $sqlLimit = $sqlLimit." LIMIT ".$searchAmount;
            $sqlLimit = $sqlLimit." OFFSET ".$offset;
        }
        

        if ($request!=null or !empty($request)) {
            $selectArray = array_intersect($stmtSelect, $request);
            $selectString = implode(", ", $selectArray);
            $sqlExecute = "SELECT ".$selectString." FROM user WHERE customer_id = :customer_id".$sqlLimit;
        } else {
            $sqlExecute = "SELECT id, customer_id, main_mail, first_name, last_name, main_adress, employment_number, birthdate, username, type, creation_date, latest_update FROM user WHERE customer_id = :customer_id".$sqlLimit;
        }

        try {
            $stmt = $this->conn->prepare($sqlExecute);
            $stmt->execute([":customer_id" => $customerId]);
            $userInfo = $stmt->fetchAll();
            return json_encode([
                "status" => "success",
                "message" => "retrieved all users belonging to this organisation",
                "data" => $userInfo        
            ]);
        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
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
                    "main_mail",
                    "first_name",
                    "last_name",
                    "main_adress",
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
                $stmt = $this->conn->prepare("SELECT user.id, user.customer_id, user.main_mail, user.first_name, user.last_name, user.main_adress, user.employment_number, user.birthdate, user.username, user.type, user.creation_date, user.latest_update FROM user INNER JOIN ban ON user.id = ban.user_id WHERE customer_id = :customer_id");
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
            
            if (empty($userInfo)) {
                $message="Ban with this id doesnt exist.";
                $this->error($message, [], 400); 
            }



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
    public function getUserBans($token ,$id) { //currently only admin
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            return json_encode($tokeninfo);
        }

        //check user permissions
        if ($tokeninfo['type'] != 'admin') {
            return json_encode([
                "status" => "error",
                "message" => "Insufficient permissions"
            ]);
        }
        //---------------------------------------------------------------------
        $customerId=$tokeninfo["customer_id"];

        try {
            $stmt = $this->conn->prepare("SELECT customer_id, type, id FROM user WHERE id =:id ");
            $stmt->execute([":id"=>$id]);
            $userInfo = $stmt->fetch();
            $userCustomerId = $userInfo["customer_id"];
            //verifies if user is registered to correct customer
            if ($userCustomerId != $customerId) {
                $message="No access";
                $this->error($message, [], 400); 
            }
            
            $stmt = $this->conn->prepare("SELECT * FROM `ban` WHERE user_id =:id");
            $stmt->execute([":id"=>$id]);

            
            $userInfo = $stmt->fetch();
            
            //Verifies that the requested user exists
            if (!$userInfo) {
                return json_encode([
                "status" => "error",
                "message" => "User with either that id and or username doesnt exist"
                ]);
            }


            return json_encode([
                "status" => "success",
                "message" => "retrived user bans",
                "data" => $userInfo
            ]);

            
            
        } catch(PDOException $e) {
            // Update to correct error
            return json_encode("ERROR ". $e);
        }
    }
    public function searchUsers($token, $filter, $searchQuery) { //currently only admin
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
        $stmtAllowedFilterTerms = $this->stmtAllowedFilterTermsList;

        try {
            //Finds of the filter request is valid, otherwise default to username search.
            if (in_array($filter, $stmtAllowedFilterTerms)) {
                $searchColumn = $filter;
            } else {
                $searchColumn = "username";
            } 
            if (empty($searchQuery)) {
                $message="Query is empty";
                $this->error($message, [], 400); 
            }

            $searchTerm = "%".$searchQuery."%";
            $sqlExecute = "SELECT id, username FROM user WHERE $searchColumn LIKE :searchTerm AND customer_id = :customer_id";
            $stmt = $this->conn->prepare($sqlExecute);
            $stmt->execute([":searchTerm"=>$searchTerm, ":customer_id"=>$customerId]);
            $userInfo = $stmt->fetchAll();
            
            //Verifies that the search returns a result
            if (!$userInfo) {
                $message="Search returned no results";
                $this->error($message, [], 400); 
            }

            $responsData=[];
            $message="retrived search results";
            $this->success($message, $userInfo, 200);

        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }
    public function getBans($token, $userId) { //currently only admin
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


        $sqlExecute = "SELECT ban.*, user.username FROM ban INNER JOIN user ON ban.user_id = user.id WHERE";
        if ($userId != null) {
            $sqlExecute = $sqlExecute." user.id = :input";
            $input = $userId;

        } else {
            $sqlExecute = $sqlExecute." user.customer_id = :input ORDER BY user.id";
            $input = $customerId;
        }

        try {
            //verifies if user is registered to correct customer
            if ($userId != null) {
                $getStmt = $this->conn->prepare("SELECT customer_id, id FROM user WHERE id = :id");
                $getStmt->execute([":id"=>$userId]);
                $userInfo = $getStmt->fetch();
                if ($tokeninfo['type'] != 'admin' && $userInfo["id"] != $userId) {
                    $message="Insufficient permissions";
                    $this->error($message, [], 400);
                }
                if (empty($userInfo)) {
                    $message="User with this id doesnt exist";
                    $this->error($message, [], 400); 
                }
                if ($userInfo["customer_id"] != $customerId) {
                    $message="No access";
                    $this->error($message, [], 400); 
                }
            }

            $getStmt = $this->conn->prepare($sqlExecute);
            $getStmt->execute([":input"=>$input]);
            $userInfo = $getStmt->fetchall();

            $responsData=[];
            $message=" STUFF data";
            $this->success($message, $userInfo, 200); 
            
        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }
}

?>