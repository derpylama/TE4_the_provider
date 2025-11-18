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
    public function getUser(int $customerId ,int $userId, string $username) {
        try {
            if $userId != null {
                $stmt = $this->conn->prepare("SELECT customer_id, mail, adress, employment_number, birthdate, username, type, creation_date, latest_update FROM users WHERE ID ='$userId' ")
            } elseif $username != "" {
                $stmt = $this->conn->prepare("SELECT customer_id, mail, adress, employment_number, birthdate, username, type, creation_date, latest_update FROM users WHERE username ='$username' ")
            }

            $stmt->execute()

            $output = $stmt->fetch()
            return json_encode($output)
            echo($type." ".$username." added successfully");
        } catch(PDOException $e) {
            echo("ERROR ". $e);
        }
    }


}

?>