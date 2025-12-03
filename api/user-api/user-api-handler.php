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
    //----------
    private $allowedEditUserArray = [
        "main_mail",
        "phone_number",
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
        "phone_number",
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
    //----------
    private $getAllListAdmin = [
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
        "latest_update",
        "extra_mail",
        "extra_adress",
        "extra_phone_number"
    ];
    private $getAllListEndUser = [
        "first_name",
        "last_name",
        "birthdate",
        "username",
        "type"
    ];
    //----------
    private $getUserInfoListAdmin = [
        "id", 
        "customer_id",
        "main_mail",
        "phone_number",
        "first_name",
        "last_name",
        "main_adress",
        "employment_number",
        "birthdate",
        "username",
        "type",
        "creation_date",
        "latest_update",
        "extra_mail",
        "extra_adress",
        "extra_phone_number"
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


    private $getUserEndUser = [
        "username",
        "id"
    ];
    private $getUserAdmin = [
        "id", 
        "customer_id",
        "main_mail",
        "phone_number",
        "first_name",
        "last_name",
        "main_adress",
        "employment_number",
        "birthdate",
        "username",
        "type",
        "creation_date",
        "latest_update",
        "extra_mail",
        "extra_adress",
        "extra_phone_number"
    ];
    private $getOwnUserData = [
        "main_mail",
        "first_name",
        "last_name",
        "main_adress",
        "phone_number",
        "employment_number",
        "birthdate",
        "username",
        "type",
        "creation_date",
        "latest_update",
        "extra_mail",
        "extra_adress",
        "extra_phone_number"
    ];




    //----------

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
            $extraMail[] = $mail;
            $extraAdress[] = $adress;
            $extraPhoneNumber[] = $phoneNumber;
            
            //veryfies if username already exists
            $stmt = $this->conn->prepare("SELECT 1 FROM user WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            if ($stmt->fetchColumn()) {
                $message="Username already exists";
                $this->error($message, [], 400); 
            }
            //Adds user
            $stmt = $this->conn->prepare("INSERT INTO user (customer_id, first_name, last_name, employment_number, birthdate, username, password, type, general) VALUES (:customer_id, :first_name, :last_name, :employment_number, :birthdate, :username, :password, :type, :general)");
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->execute([
                ":customer_id" => $customerId, 
                ":first_name" => $name,
                ":last_name" => $lastName,
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

            if(!empty($mail)){
                $stmt = $this->conn->prepare("UPDATE user u INNER JOIN mail m ON u.id = m.user_id SET main_mail = m.id WHERE m.mail = :mail");
                $stmt->execute(["mail" => $mail]);
            }
            if(!empty($adress)){
                $stmt = $this->conn->prepare("UPDATE user u INNER JOIN adress a ON u.id = a.user_id SET main_adress = a.id WHERE a.adress = :adress");
                $stmt->execute(["adress" => $adress]);
            }
            if(!empty($phoneNumber)){
                $stmt = $this->conn->prepare("UPDATE user u INNER JOIN phone_number pn ON u.id = pn.user_id SET u.phone_number = pn.id WHERE pn.phone_number = :phoneNumber");
                $stmt->execute(["phoneNumber" => $phoneNumber]);
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
            $message="retrieved user:".$userInfo["username"]."data";
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
    public function editUser($token, $editUserId, $mail, $firstName, $lastName, $phoneNumber, $adress, $employmentNumber, $birthDate, $username, $password, $type, $general) {
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
        $userId=$tokeninfo["userId"];
        
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
            
            if(!empty($editsString)){
                $sqlExecute = "UPDATE user SET ".$editsString." WHERE id = :id";

                $stmt = $this->conn->prepare($sqlExecute);
                $stmt->execute($valueList);
            }
            #region mail
            if(!empty($mail['add'])){
                foreach($mail['add'] as $index => $value){

                    // check if the user already has this mail
                    $stmt = $this->conn->prepare("SELECT mail FROM mail WHERE mail = :mail AND user_id = :userId");
                    $stmt->execute(["mail" => $value, "userId" => $editUserId]);
                    $selectMail = $stmt->fetch();
                    if($selectMail === false || $selectMail['mail'] != $value || !$selectMail['mail']){
                        // insert the new mail
                        $stmt = $this->conn->prepare("INSERT INTO mail (user_id, mail) VALUES (:userId, :mail)");
                        $stmt->execute(["userId" => $editUserId, "mail" => $value]);
                    }
                }
            }
            if(!empty($mail['update'])){
                foreach($mail['update'] as $index => $value){
                    // check if the mail to update exists
                    $stmt = $this->conn->prepare("SELECT mail FROM mail WHERE mail = :mail AND user_id = :userId");
                    $stmt->execute(["mail" => $index, "userId" => $editUserId]);
                    $selectMail = $stmt->fetch();

                    if ($selectMail === false) {
                        continue;
                    }

                    if($selectMail['mail'] == $index){
                        $oldMail = $selectMail['mail'];
                        // update the selected mail
                        $stmt = $this->conn->prepare("UPDATE mail SET mail = :mail WHERE mail = :oldMail AND user_id = :userId");
                        $stmt->execute(["userId" => $editUserId, "mail" => $value, "oldMail" => $oldMail]);
                    }
                }
            }
            if(!empty($mail['delete'])){
                foreach($mail['delete'] as $value){
                    // check if the mail to delete exists
                    $stmt = $this->conn->prepare("SELECT mail FROM mail WHERE mail = :mail AND user_id = :userId");
                    $stmt->execute(["mail" => $value, "userId" => $editUserId]);
                    $selectMail = $stmt->fetch();
                    if($selectMail === false || $selectMail['mail']){
                        // delete the selected mail
                        $stmt = $this->conn->prepare("DELETE FROM mail WHERE mail = :mail AND user_id = :userId");
                        $stmt->execute(["userId" => $editUserId, "mail" => $value]);
                    }
                }
            }

            if(!empty($mail['main'])){
                $newMain = $mail['main'];
                // check if the new mail isnt the same as the old main and if a min exists currently
                $stmt = $this->conn->prepare("SELECT mail FROM mail WHERE user_id = :userId");
                $stmt->execute(["userId" => $editUserId]);
                $oldMain = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if($oldMain === false || $oldMain){
                    // insert the new mail
                    echo $newMain;
                    var_dump($oldMain);
                    if(!in_array($newMain, $oldMain)){
                        $stmt = $this->conn->prepare("INSERT INTO mail (user_id, mail) VALUES (:userId, :mail)");
                        $stmt->execute(["userId" => $editUserId, "mail" => $newMain]);
                    }

                    // get the id of the new mail
                    $stmt = $this->conn->prepare("SELECT id FROM mail WHERE mail = :mail AND user_id = :userId");
                    $stmt->execute(["mail" => $newMain, "userId" => $editUserId]);
                    $mainMailId = $stmt->fetch();

                    // insert the id of the new mail
                    $stmt = $this->conn->prepare("UPDATE user SET main_mail = :mainMailId WHERE id = :userId");
                    $stmt->execute(["mainMailId" => $mainMailId['id'], "userId" => $editUserId]);
                } 
            }
            #endregion

            #region phone number
            if(!empty($phoneNumber['add'])){
                foreach($phoneNumber['add'] as $index => $value){

                    // check if the user already has this phone number
                    $stmt = $this->conn->prepare("SELECT phone_number FROM phone_number WHERE phone_number = :phoneNumber AND user_id = :userId");
                    $stmt->execute(["phoneNumber" => $value, "userId" => $editUserId]);
                    $selectPhoneNumber = $stmt->fetch();
                    if($selectPhoneNumber === false || $selectPhoneNumber['phone_number'] != $value || !$selectPhoneNumber['phone_number']){
                        // insert the new phone number
                        $stmt = $this->conn->prepare("INSERT INTO phone_number (user_id, phone_number) VALUES (:userId, :phoneNumber)");
                        $stmt->execute(["userId" => $editUserId, "phoneNumber" => $value]);
                    }
                }
            }
            if(!empty($phoneNumber['update'])){
                foreach($phoneNumber['update'] as $index => $value){
                    // check if the phone_number to update exists
                    $stmt = $this->conn->prepare("SELECT phone_number FROM phone_number WHERE phone_number = :phoneNumber AND user_id = :userId");
                    $stmt->execute(["phoneNumber" => $index, "userId" => $editUserId]);
                    $selectPhoneNumber = $stmt->fetch();

                    if ($selectPhoneNumber === false) {
                        continue;
                    }

                    if($selectPhoneNumber['phone_number'] == $index){
                        $oldPhoneNumber = $selectPhoneNumber['phone_number'];
                        // update the selected phone_number
                        $stmt = $this->conn->prepare("UPDATE phone_number SET phone_number = :phoneNumber WHERE phone_number = :oldPhoneNumber AND user_id = :userId");
                        $stmt->execute(["userId" => $editUserId, "phoneNumber" => $value, "oldPhoneNumber" => $oldPhoneNumber]);
                    }
                }
            }
            if(!empty($phoneNumber['delete'])){
                foreach($phoneNumber['delete'] as $value){
                    // check if the phone_number to delete exists
                    $stmt = $this->conn->prepare("SELECT phone_number FROM phone_number WHERE phone_number = :phoneNumber AND user_id = :userId");
                    $stmt->execute(["phoneNumber" => $value, "userId" => $editUserId]);
                    $selectPhoneNumber = $stmt->fetch();
                    if($selectPhoneNumber === false || $selectPhoneNumber['phoneNumber']){
                        // delete the selected phone_number
                        $stmt = $this->conn->prepare("DELETE FROM phone_number WHERE phone_number = :phoneNumber AND user_id = :userId");
                        $stmt->execute(["userId" => $editUserId, "phoneNumber" => $value]);
                    }
                }
            }

            if(!empty($phoneNumber['main'])){
                $newMain = $phoneNumber['main'];
                // check if the new phone_number isnt the same as the old main and if a min exists currently
                $stmt = $this->conn->prepare("SELECT phone_number FROM phone_number WHERE user_id = :userId");
                $stmt->execute(["userId" => $editUserId]);
                $oldMain = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if($oldMain === false || $oldMain){
                    // insert the new phone_number
                    if(!in_array($newMain, $oldMain)){
                        $stmt = $this->conn->prepare("INSERT INTO phone_number (user_id, phone_number) VALUES (:userId, :phoneNumber)");
                        $stmt->execute(["userId" => $editUserId, "phoneNumber" => $newMain]);
                    }

                    // get the id of the new phone_number
                    $stmt = $this->conn->prepare("SELECT id FROM phone_number WHERE phone_number = :phoneNumber AND user_id = :userId");
                    $stmt->execute(["phoneNumber" => $newMain, "userId" => $editUserId]);
                    $mainPhoneNumberId = $stmt->fetch();

                    // insert the id of the new phone_number
                    $stmt = $this->conn->prepare("UPDATE user SET phone_number = :mainPhoneNumberId WHERE id = :userId");
                    $stmt->execute(["mainPhoneNumberId" => $mainPhoneNumberId['id'], "userId" => $editUserId]);
                } 
            }
            #endregion

            #region adress
            if(!empty($adress['add'])){
                foreach($adress['add'] as $index => $value){

                    // check if the user already has this phone number
                    $stmt = $this->conn->prepare("SELECT adress FROM adress WHERE adress = :adress AND user_id = :userId");
                    $stmt->execute(["adress" => $value, "userId" => $editUserId]);
                    $selectAdress = $stmt->fetch();
                    if($selectAdress === false || $selectAdress['adress'] != $value || !$selectAdress['adress']){
                        // insert the new phone number
                        $stmt = $this->conn->prepare("INSERT INTO adress (user_id, adress) VALUES (:userId, :adress)");
                        $stmt->execute(["userId" => $editUserId, "adress" => $value]);
                    }
                }
            }
            if(!empty($adress['update'])){
                foreach($adress['update'] as $index => $value){
                    // check if the phone_number to update exists
                    $stmt = $this->conn->prepare("SELECT adress FROM adress WHERE adress = :adress AND user_id = :userId");
                    $stmt->execute(["adress" => $index, "userId" => $editUserId]);
                    $selectAdress = $stmt->fetch();

                    if ($selectAdress === false) {
                        continue;
                    }

                    if($selectAdress['adress'] == $index){
                        $oldPhoneNumber = $selectAdress['adress'];
                        // update the selected phone_number
                        $stmt = $this->conn->prepare("UPDATE adress SET adress = :adress WHERE adress = :oldAdress AND user_id = :userId");
                        $stmt->execute(["userId" => $editUserId, "adress" => $value, "oldAdress" => $oldPhoneNumber]);
                    }
                }
            }
            if(!empty($adress['delete'])){
                foreach($adress['delete'] as $value){
                    // check if the phone_number to delete exists
                    $stmt = $this->conn->prepare("SELECT adress FROM adress WHERE adress = :adress AND user_id = :userId");
                    $stmt->execute(["adress" => $value, "userId" => $editUserId]);
                    $selectAdress = $stmt->fetch();
                    if($selectAdress === false || $selectAdress['adress']){
                        // delete the selected phone_number
                        $stmt = $this->conn->prepare("DELETE FROM adress WHERE adress = :adress AND user_id = :userId");
                        $stmt->execute(["userId" => $editUserId, "adress" => $value]);
                    }
                }
            }

            if(!empty($adress['main'])){
                $newMain = $adress['main'];
                // check if the new phone_number isnt the same as the old main and if a min exists currently
                $stmt = $this->conn->prepare("SELECT adress FROM adress WHERE user_id = :userId");
                $stmt->execute(["userId" => $editUserId]);
                $oldMain = $stmt->fetchAll();
                if($oldMain === false || $oldMain){
                    // insert the new phone_number
                    if(!in_array($newMain, $oldMain)){
                        $stmt = $this->conn->prepare("INSERT INTO adress (user_id, adress) VALUES (:userId, :adress)");
                        $stmt->execute(["userId" => $editUserId, "adress" => $newMain]);
                    }

                    // get the id of the new phone_number
                    $stmt = $this->conn->prepare("SELECT id FROM adress WHERE adress = :adress AND user_id = :userId");
                    $stmt->execute(["adress" => $newMain, "userId" => $editUserId]);
                    $mainAdressId = $stmt->fetch(PDO::FETCH_COLUMN);

                    // insert the id of the new phone_number
                    $stmt = $this->conn->prepare("UPDATE user SET main_adress = :adress WHERE id = :userId");
                    $stmt->execute(["adress" => $mainAdressId['id'], "userId" => $editUserId]);
                } 
            }
            #endregion

            // if(empty($editsString)){
            //     $responsData=[];
            //     $message="No user data to edit";
            //     $this->success($message, $responsData, 200);
            // }


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
    /*
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
        $stmtSelect = $this->getAllListAdmin;

            if ($tokeninfo['type'] == 'admin') {
                $editableInfoList = $this->getAllListAdmin;
            } elseif ($userInfo["id"] == $editUserId) {
                $editableInfoList = $this->getAllListEndUser;
            } else {
                $message="Insufficient permissions";
                $this->error($message, [], 400);
            }




        $sqlLimit = "";
        if ($searchAmount != 0) {
            $sqlLimit = $sqlLimit." LIMIT ".$searchAmount;
            $sqlLimit = $sqlLimit." OFFSET ".$offset;
        }
        

        if ($request!=null or !empty($request)) {
            $selectArray = array_intersect($stmtSelect, $request);
            // $selectString = implode(", ", $selectArray);
            // $sqlExecute = "SELECT ".$selectString." FROM user WHERE customer_id = :customer_id".$sqlLimit;
            $selectStringArray = [];
            foreach ($selectArray as $col) {
                switch ($col) {
                    case 'extra_mail':
                        $selectStringArray[] = "(SELECT GROUP_CONCAT(mail SEPARATOR ',') FROM mail WHERE user_id = user.id) AS extra_mail";
                        break;
                    case 'extra_address':
                        $selectStringArray[] = "(SELECT GROUP_CONCAT(adress SEPARATOR ',') FROM adress WHERE user_id = user.id) AS extra_address";
                        break;
                    case 'extra_phone_number':
                        $selectStringArray[] = "(SELECT GROUP_CONCAT(phone_number SEPARATOR ',') FROM phone_number WHERE user_id = user.id) AS extra_phone_number";
                        break;
                    default:
                        $selectStringArray[] = $col;
                }
            }
        
            $selectString = implode(", ", $selectStringArray);
        
            $sqlExecute = "SELECT ".$selectString." FROM user WHERE customer_id = :customer_id".$sqlLimit;
        } else {
            //$sqlExecute = "SELECT u.id, u.customer_id, m.mail, pn.phone_number, u.first_name, u.last_name, a.adress, u.employment_number, u.birthdate, u.username, u.type, u.creation_date, u.latest_update FROM user u INNER JOIN mail m INNER JOIN adress a INNER JOIN phone_number pn WHERE customer_id = :customer_id".$sqlLimit;
            $sqlExecute = "SELECT 
                u.id,
                u.customer_id,
                main_mail.mail AS main_mail,
                main_address.adress AS main_address,
                main_phone.phone_number AS main_phone,
                u.first_name,
                u.last_name,
                u.employment_number,
                u.birthdate,
                u.username,
                u.type,
                u.creation_date,
                u.latest_update,
                (SELECT GROUP_CONCAT(mail SEPARATOR ',')
                FROM mail
                WHERE user_id = u.id) AS all_mails,
                (SELECT GROUP_CONCAT(adress SEPARATOR ',')
                FROM adress
                WHERE user_id = u.id) AS all_addresses,
                (SELECT GROUP_CONCAT(phone_number SEPARATOR ',')
                FROM phone_number
                WHERE user_id = u.id) AS all_phone_numbers
            FROM user u
            LEFT JOIN mail main_mail ON main_mail.id = u.main_mail
            LEFT JOIN adress main_address ON main_address.id = u.main_adress
            LEFT JOIN phone_number main_phone ON main_phone.id = u.phone_number
            WHERE u.customer_id = :customer_id;".$sqlLimit;
        }

        try {
            $stmt = $this->conn->prepare($sqlExecute);
            $stmt->execute([":customer_id" => $customerId]);
            $userInfo = $stmt->fetchAll();

            $responsData=["users" => $userInfo];
            $message="retrieved all users belonging to this organisation";
            $this->success($message, $responsData, 200);

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
            $message="retrieved all users belonging to this organisation";
            $this->success($message, $userInfo, 200);

        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }  
        
    }*/
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
                    "phone_number",
                    "first_name",
                    "last_name",
                    "main_adress",
                    "employment_number",
                    "birthdate",
                    "username",
                    "type",
                    "creation_date",
                    "latest_update",
                    "extra_mail",
                    "extra_adress",
                    "extra_phone_number"
                    ];
                $selectArray = [];
                // $selectArray = array_intersect($stmtSelect, $request);
                // $selectString = implode(", user.", $selectArray);
                // $selectString ="user.".$selectString;
                // $sqlExecute = "SELECT ".$selectString." FROM user INNER JOIN ban ON user.id = ban.id WHERE customer_id = :customer_id";
                // $stmt = $this->conn->prepare($sqlExecute);
                $selectArray = array_intersect($stmtSelect, $request);

                // Build SELECT string dynamically
                $selectStringArray = [];
                foreach ($selectArray as $col) {
                    switch ($col) {
                        case 'extra_mail':
                            $selectStringArray[] = "(SELECT GROUP_CONCAT(mail SEPARATOR ',') FROM mail WHERE user_id = user.id) AS extra_mail";
                            break;
                        case 'extra_adress':
                            $selectStringArray[] = "(SELECT GROUP_CONCAT(adress SEPARATOR ',') FROM adress WHERE user_id = user.id) AS extra_adress";
                            break;
                        case 'extra_phone_number':
                            $selectStringArray[] = "(SELECT GROUP_CONCAT(phone_number SEPARATOR ',') FROM phone_number WHERE user_id = user.id) AS extra_phone_number";
                            break;
                        case 'main_mail':
                            $selectStringArray[] = "(SELECT mail FROM mail WHERE id = user.main_mail) AS main_mail";
                            break;
                        case 'main_adress':
                            $selectStringArray[] = "(SELECT adress FROM adress WHERE id = user.main_adress) AS main_adress";
                            break;
                        case 'phone_number':
                            $selectStringArray[] = "(SELECT phone_number FROM phone_number WHERE id = user.phone_number) AS phone_number";
                            break;
                        default:
                            $selectStringArray[] = "user.".$col;
                    }
                }

                $selectString = implode(", ", $selectStringArray);

                // Build the final SQL
                $sqlExecute = "SELECT ".$selectString." 
                            FROM user 
                            INNER JOIN ban ON user.id = ban.user_id 
                            WHERE user.customer_id = :customer_id";

                $stmt = $this->conn->prepare($sqlExecute);
                } else {
                //$stmt = $this->conn->prepare("SELECT user.id, user.customer_id, user.main_mail, user.first_name, user.last_name, user.main_adress, user.employment_number, user.birthdate, user.username, user.type, user.creation_date, user.latest_update FROM user INNER JOIN ban ON user.id = ban.user_id WHERE customer_id = :customer_id");
                $stmt = $this->conn->prepare("
                    SELECT 
                        user.id,
                        user.customer_id,
                        user.main_mail,
                        user.first_name,
                        user.last_name,
                        user.main_adress,
                        user.employment_number,
                        user.birthdate,
                        user.username,
                        user.type,
                        user.creation_date,
                        user.latest_update,
                        (SELECT GROUP_CONCAT(mail SEPARATOR ',') FROM mail WHERE user_id = user.id) AS extra_mail,
                        (SELECT GROUP_CONCAT(adress SEPARATOR ',') FROM adress WHERE user_id = user.id) AS extra_address,
                        (SELECT GROUP_CONCAT(phone_number SEPARATOR ',') FROM phone_number WHERE user_id = user.id) AS extra_phone_number
                    FROM user
                    INNER JOIN ban ON user.id = ban.user_id
                    WHERE user.customer_id = :customer_id
                ");
            }

            $stmt->execute([":customer_id" => $customerId]);
            $userInfo = $stmt->fetchAll();

            $responsData=["users" => $userInfo];
            $message="retrieved all banned users belonging to this organisation";
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
                $message="Error";
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
                $responsData=[];
                $message="User with either that id and or username doesnt exist";
                $this->error($message, $responsData, 400);
            }


            $responsData=["users" => $userInfo];
            $message="retrieved user bans";
            $this->success($message, $responsData, 200);

            
            
        } catch(PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
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
            $message="retrieved search results";
            $this->success($message, $userInfo, 200);

        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }
    public function getBans($token, $userId) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
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
                if ($userInfo["customer_id"] != $customerId) {
                    $message="Error";
                    $this->error($message, [], 400); 
                }
                if (empty($userInfo)) {
                    $message="Error";
                    $this->error($message, [], 400); 
                }

            } else if($tokeninfo['type'] != 'admin') {
                $message="Insufficient permissions";
                $this->error($message, [], 400);
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
    public function is_assoc($array): bool {
        if (!is_array($array)) {
            return false;
        }
        if ($array === []) return false;
        return array_keys($array) !== range(0, count($array) - 1);
    }
    public function getAllUsers($token, $request, $searchAmount, $offset, $getUserId) { //only admin?
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] != 'admin' && $tokeninfo['type'] != 'end_user') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
        }

        //---------------------------------------------------------------------
        $customerId=$tokeninfo["customer_id"];
        $userId=$tokeninfo["userId"];


        if ($getUserId != null) {
            try {
                // $getStmt = $this->conn->prepare("SELECT customer_id, id FROM user WHERE id = :id");
                // $getStmt->execute([":id"=>$getUserId]);
                // $userInfo = $getStmt->fetch();
                // if ($tokeninfo["customer_id"] == $userInfo["customer_id"]) {
                //     if ($tokeninfo['type'] == 'admin') {
                //         $stmtSelect = $this->getUserAdmin;
                //     } elseif ($getUserId == $userInfo["id"]) {
                //         $stmtSelect = $this->getOwnUserData;
                //     } elseif ($tokeninfo['type'] == 'end_user') {
                //         $stmtSelect = $this->getUserEndUser;
                //     }
                // } else {
                //     $message="ERROR";
                //     $this->error($message, [], 400);
                // }
                // $selectString = implode(", ", $stmtSelect);
                // $sqlExecute = "SELECT ".$selectString." FROM user WHERE id = :id";
                // $stmt = $this->conn->prepare($sqlExecute);
                // $stmt->execute([":id"=>$getUserId]);
                // $userData = $stmt->fetch();

                $getStmt = $this->conn->prepare("SELECT customer_id, id, main_mail, main_adress, phone_number FROM user WHERE id = :id");
                $getStmt->execute([":id"=>$getUserId]);
                $userInfo = $getStmt->fetch();

                if ($tokeninfo["customer_id"] == $userInfo["customer_id"]) {
                    if ($tokeninfo['type'] == 'admin') {
                        $stmtSelect = $this->getUserAdmin;
                    } elseif ($userId == $getUserId) {
                        $stmtSelect = $this->getOwnUserData;
                    } elseif ($tokeninfo['type'] == 'end_user') {
                        $stmtSelect = $this->getUserEndUser;
                    }
                } else {
                    $message="ERROR";
                    $this->error($message, [], 400);
                }

                // Build SELECT string dynamically with extra fields using GROUP_CONCAT
                $selectStringArray = [];
                foreach ($stmtSelect as $col) {
                    switch ($col) {
                        case 'extra_mail':
                            $selectStringArray[] = "(SELECT GROUP_CONCAT(mail SEPARATOR ',') FROM mail WHERE user_id = user.id) AS extra_mail";
                            break;
                        case 'extra_adress':
                            $selectStringArray[] = "(SELECT GROUP_CONCAT(adress SEPARATOR ',') FROM adress WHERE user_id = user.id) AS extra_address";
                            break;
                        case 'extra_phone_number':
                            $selectStringArray[] = "(SELECT GROUP_CONCAT(phone_number SEPARATOR ',') FROM phone_number WHERE user_id = user.id) AS extra_phone_number";
                            break;
                        case 'main_mail':
                            $selectStringArray[] = "(SELECT mail FROM mail WHERE id = user.main_mail) AS main_mail";
                            break;
                        case 'main_adress':
                            $selectStringArray[] = "(SELECT adress FROM adress WHERE id = user.main_adress) AS main_address";
                            break;
                        case 'phone_number':
                            $selectStringArray[] = "(SELECT phone_number FROM phone_number WHERE id = user.phone_number) AS main_phone_number";
                            break;
                        default:
                            $selectStringArray[] = $col;
                    }
                }

                $selectString = implode(", ", $selectStringArray);
                // Prepare and execute the new query
                $sqlExecute = "SELECT ".$selectString." FROM user WHERE id = :id";
                $stmt = $this->conn->prepare($sqlExecute);
                $stmt->execute([":id"=>$getUserId]);
                $userData = $stmt->fetch();


                $responsData=["users" => $userData];
                $message="Retrieved User Data";
                $this->success($message, $responsData, 200);

            } catch (PDOException $e) {
                $message="Database error: " . $e->getMessage();
                $this->error($message, [], 400);
            }
        } else {   
            
            if ($tokeninfo['type'] == 'admin') {
                $stmtSelect = $this->getUserAdmin;
            } elseif ($tokeninfo["type"] == "end_user") {
                $stmtSelect = $this->getUserEndUser;

            }

            $sqlLimit = " LIMIT ".$searchAmount;
            $sqlLimit = $sqlLimit." OFFSET ".$offset;

            // $selectString = implode(", ", $stmtSelect);
            // $sqlExecute = "SELECT ".$selectString." FROM user WHERE customer_id = :customer_id".$sqlLimit;

            $selectArray = $stmtSelect; // Add this line before the foreach
            $selectStringArray = [];
            foreach ($selectArray as $col) {
                switch ($col) {
                    case 'extra_mail':
                        $selectStringArray[] = "(SELECT GROUP_CONCAT(mail SEPARATOR ',') FROM mail WHERE user_id = user.id) AS extra_mail";
                        break;
                    case 'extra_adress':
                        $selectStringArray[] = "(SELECT GROUP_CONCAT(adress SEPARATOR ',') FROM adress WHERE user_id = user.id) AS extra_address";
                        break;
                    case 'extra_phone_number':
                        $selectStringArray[] = "(SELECT GROUP_CONCAT(phone_number SEPARATOR ',') FROM phone_number WHERE user_id = user.id) AS extra_phone_number";
                        break;
                    case 'main_mail':
                        $selectStringArray[] = "(SELECT mail FROM mail WHERE id = user.main_mail) AS main_mail";
                        break;
                    case 'main_adress':
                        $selectStringArray[] = "(SELECT adress FROM adress WHERE id = user.main_adress) AS main_address";
                        break;
                    case 'phone_number':
                        $selectStringArray[] = "(SELECT phone_number FROM phone_number WHERE id = user.phone_number) AS main_phone_number";
                        break;
                    default:
                        $selectStringArray[] = $col;
                }
            }
        
            $selectString = implode(", ", $selectStringArray);
        
            $sqlExecute = "SELECT ".$selectString." FROM user WHERE customer_id = :customer_id".$sqlLimit;
        }


        try {
            $getStmt = $this->conn->prepare($sqlExecute);
            $getStmt->execute([":customer_id"=>$tokeninfo["customer_id"]]);
            $userData = $getStmt->fetchall();
            $responsData=["users" => $userData];
            $message="Retrieved User Data";
            $this->success($message, $responsData, 200);

            //Gives the correct list for the user to edit
            if ($tokeninfo['type'] == 'admin') {
                $editableInfoList = $this->allowedEditUserArrayAdmin;
            } elseif ($userInfo["id"] == $editUserId) {
                $editableInfoList = $this->allowedEditUserArray;
            } else {
                $message="Insufficient permissions";
                $this->error($message, [], 400);
            }
        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }


    }    
    
}

?>