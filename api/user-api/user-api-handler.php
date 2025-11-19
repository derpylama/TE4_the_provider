<?php

require_once('../api-handler.php');
class UserApiHandler extends BaseApiHandler{

    public function getUsers() {//example method
        $stmt = $this->conn->query("SELECT * FROM users");
        return $stmt->fetchAll();
    }
    public function addUser(int $customerId, string $mail, string $adress, int $employmentNumber, string $birthDate, string $username, string $password, string $type) {
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
            
            $stmt = $this->conn->prepare("INSERT INTO user (customer_id, mail, adress, employment_number, birthdate, username, password, type) VALUES (:customer_id, :mail, :adress, :employment_number, :birthdate, :username, :password, :type)");
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt->execute([
                ":customer_id" => $customerId, 
                ":mail" => $mail,
                ":adress" => $adress,
                "employment_number" => $employmentNumber,
                ":birthdate" => $birthDate,
                ":username" => $username,
                ":password" => $hashedPassword,
                ":type" => $type
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
    public function getUser($customerId ,$id, $username) {
        try {
            if ($id != 0) {
                $stmt = $this->conn->prepare("SELECT customer_id, mail, adress, employment_number, birthdate, username, type, creation_date, latest_update FROM user WHERE id =:id ");
                $stmt->execute([":id"=>$id]);
            } else {
                $stmt = $this->conn->prepare("SELECT customer_id, mail, adress, employment_number, birthdate, username, type, creation_date, latest_update FROM user WHERE username =:username ");
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
                "user data" => $userInfo        
            ]);

            
            
        } catch(PDOException $e) {
            // Update to correct error
            return json_encode("ERROR ". $e);
        }
    }
    public function banUser($customerId, $banUserId, $expirationDate, $blogBan, $wikiBan, $calendarBan, $reason, $banningUser) {
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


}

?>