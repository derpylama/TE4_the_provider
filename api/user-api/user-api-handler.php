<?php

require_once('../api-handler.php');
class UserApiHandler extends BaseApiHandler{

    protected function checkServiceAndToken($token, $service="user"){
        return parent::checkServiceAndToken($token, $service);
    }

    public function getUsers($token) {//example method
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            return json_encode($tokeninfo);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            return json_encode([
                "status" => "error",
                "message" => "Insufficient permissions"
            ]);
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
        }else { //remove this if when product is complete 
            $customerId= 999;
            }
        try {
            //veryfies if username already exists
            $stmt = $this->conn->prepare("SELECT 1 FROM user WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            if ($stmt->fetchColumn()) {
                return json_encode([
                    "status" => "error",
                    "message" => "Username already exists"
                ]);
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
            return json_encode([
                "status" => "success",
                "message" => "User added"
            ]);
        } catch(PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }  
    }
    public function getUser($token ,$id, $username) { //currently only admin
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
                return json_encode([
                "status" => "error",
                "message" => "User with either that id and or username doesnt exist"
                ]);
            }
            //verifies if user is registered to correct customer
            if ($userInfo["customer_id"] != $customerId) {
                return json_encode([
                "status" => "error",
                "message" => "No access"
                ]);
            }
            return json_encode([
                "status" => "success",
                "message" => "retrived user:".$userInfo["username"]."data",
                "data" => $userInfo        
            ]);

            
            
        } catch(PDOException $e) {
            // Update to correct error
            return json_encode("ERROR ". $e);
        }
    }
    public function banUser($token, $banUserId, $expirationDate, $blogBan, $wikiBan, $calendarBan, $reason) {
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
        $banningUser=$tokeninfo["userId"];

        try {
            $stmt = $this->conn->prepare("SELECT customer_id, type, id FROM user WHERE id =:id ");
            $stmt->execute([":id"=>$banUserId]);
            $userInfo = $stmt->fetch();
            $userCustomerId = $userInfo["customer_id"];
            //verifies if user is registered to correct customer
            if ($userCustomerId != $customerId) {
                return json_encode([
                "status" => "error",
                "message" => "No access"
                ]);
            }
            //verify that the ban target user is not an admin
            if ($userInfo["type"] == 'admin') {
                return json_encode([
                    "status" => "error",
                    "message" => "Target is an admin"
                ]);
            }
            //verify that admin is not banning their own account
            if ($banUserId == $banningUser) {
                return json_encode([
                    "status" => "error",
                    "message" => "Cant ban your own account"
                ]);

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
            return json_encode([
                "status" => "success",
                "message" => "user".$banUserId." has been banned successfully.",
                
            ]);

        } catch (PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }  
    }
    public function editUser($token, $editUserId, $mail, $adress, $employmentNumber, $birthDate, $username, $password, $type, $general) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            return json_encode($tokeninfo);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            return json_encode([
                "status" => "error",
                "message" => "Insufficient permissions"
            ]);
        }

        //---------------------------------------------------------------------
        $customerId=$tokeninfo["customer_id"];
        $id=$editUserId ?? $tokeninfo["userId"];
        
        if ($editUserId == null) {
            return json_encode([
                "status" => "error",
                "message" => "No user id to edit specified"
            ]);
        }
        
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
                return json_encode([
                "status" => "error",
                "message" => "No access"
                ]);
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
            return json_encode([
                "status" => "success",
                "message" => "User edited"
            ]);
 

        } catch (PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }  
    }
    public function getAllUsers($token) { //only admin?
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
            $stmt = $this->conn->prepare("SELECT id, customer_id, mail, adress, employment_number, birthdate, username, type, creation_date, latest_update FROM user WHERE customer_id = :customer_id");
            $stmt->execute([":customer_id"=>$customerId]);
            $userInfo = $stmt->fetchAll();
            return json_encode([
                "status" => "success",
                "message" => "retrived all users belonging to this orginisation",
                "data" => $userInfo        
            ]);

        } catch (PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "GRUB Database error: " . $e->getMessage()
            ]);
        }  
    }
    public function getAllBannedUsers($token) {
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
            $stmt = $this->conn->prepare("SELECT user.id, user.customer_id, user.mail, user.adress, user.employment_number, user.birthdate, user.username, user.type, user.creation_date, user.latest_update FROM user INNER JOIN ban ON user.id = ban.user_id WHERE customer_id = :customer_id");
            $stmt->execute([":customer_id"=>$customerId]);
            $userInfo = $stmt->fetchAll();
            return json_encode([
                "status" => "success",
                "message" => "retrived all users belonging to this orginisation",
                "data" => $userInfo        
            ]);

        } catch (PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "GRUB Database error: " . $e->getMessage()
            ]);
        }  
    }
    public function removeUser($removeUserId, $token) {
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
        //check so user isent trying to remove himself
        if ($tokeninfo['userId'] == $removeUserId) {
            return json_encode([
                "status" => "error",
                "message" => "Cant remove your own admin account"
            ]);
        }

        try {
            $getStmt = $this->conn->prepare("SELECT customer_id FROM user WHERE id = :id");
            $getStmt->execute([":id"=>$removeUserId]);
            $userInfo = $getStmt->fetch();
            //verifies if user is registered to correct customer
            if ($userInfo["customer_id"] != $customerId) {
                return json_encode([
                "status" => "error",
                "message" => "No access"
                ]);
            }
            $stmt = $this->conn->prepare("DELETE FROM user WHERE id = :id");
            $stmt->execute([":id"=>$removeUserId]);
            return json_encode([
                "status" => "success",
                "message" => "removed user",
     
            ]);
        } catch (PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "GRUB Database error: " . $e->getMessage()
            ]);
        }  
    }    
    public function removeBan($removeBanId, $token) {
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



            


            $stmt = $this->conn->prepare("SELECT user.customer_id FROM user INNER JOIN ban ON user.id = ban.user_id WHERE ban.id = :id");
            $stmt->execute([":id"=>$removeBanId]);
            $userInfo = $stmt->fetch();
            $userCustomerId = $userInfo;
            //verify if ban exists
            if ($userCustomerId == false) {
                return json_encode([
                "status" => "error",
                "message" => "Ban doesnt exist"
                ]);
            }
            //verify access this ban
            if ($userCustomerId != $customerId) {
                return json_encode([
                "status" => "error",
                "message" => "No access to this ban"
                ]);
            }
            $stmt = $this->conn->prepare("DELETE FROM ban WHERE id = :id");
            $stmt->execute([":id"=>$removeBanId]);
            return json_encode([
                "status" => "success",
                "message" => "removed ban",
     
            ]);
        } catch (PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "GRUB Database error: " . $e->getMessage()
            ]);
        }  
    }    
}

?>