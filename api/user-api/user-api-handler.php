<?php

require_once('../api-handler.php');
class UserApiHandler extends BaseApiHandler{

    public function getUsers() {//example method
        $stmt = $this->conn->query("SELECT * FROM users");
        return $stmt->fetchAll();
    }
    public function addUser(int $customerId, string $mail, string $adress, int $employmentNumber, string $birthDate, string $username, string $password, string $type) {
        try {

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
            echo($type." ".$username." added successfully");
        } catch(PDOException $e) {
            echo("ERROR ". $e);
        }
    }
    public function getUser($customerId ,$userId = "", string $username = "") {
        try {
            if ($userId != ""){
                $stmt = $this->conn->prepare("SELECT customer_id, mail, adress, employment_number, birthdate, username, type, creation_date, latest_update FROM user WHERE ID =:userId ");
                $stmt->execute([":userId"=>$userId]);
            } elseif ($username != ""){
                $stmt = $this->conn->prepare("SELECT customer_id, mail, adress, employment_number, birthdate, username, type, creation_date, latest_update FROM user WHERE username =:username ");
                $stmt->execute([":username"=>$username]);
            } else {
                return json_encode("ERROR");
                exit;
            }



            $output = $stmt->fetch();
            if ($output["customer_id"] == $customerId) {
                return json_encode($output);
            } else {
                //Update to correct output
                return json_encode("ERROR "."You do not have acces to this user");
            }
            
            
        } catch(PDOException $e) {
            // Update to correct error
            return json_encode("ERROR ". $e);
        }
    }


}

?>