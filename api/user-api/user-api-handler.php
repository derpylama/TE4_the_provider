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



    private $getUserEndUser = [
        "username",
        "id"
    ];
    private $getUserAdmin = [
        "id", 
        "customer_id",
        "first_name",
        "last_name",
        "employment_number",
        "birthdate",
        "username",
        "type",
        "creation_date",
        "latest_update",
        "general", 
        "main_mail", 
        "extra_mail",
        "main_address", 
        "extra_address",
        "main_phone_number",
        "extra_phone_number"
    ];



    private $getUserAdminSearch = [
        "id", 
        "customer_id",
        "first_name",
        "last_name",
        "employment_number",
        "birthdate",
        "username",
        "type",
        "creation_date",
        "latest_update",
        "general", 
    ];







    private $getOwnUserData = [
        "first_name",
        "last_name",
        "employment_number",
        "birthdate",
        "username",
        "type",
        "creation_date",
        "latest_update",
        "general",
        "main_mail",
        "extra_mail",
        "main_address", 
        "extra_address",
        "main_phone_number",
        "extra_phone_number"
    ];




    //----------

    public function getUsers($token) {//example method
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 401);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            $message="Insufficient permissions";
            $this->error($message, [], 403);
        }

        //---------------------------------------------------------------------

        $stmt = $this->conn->query("SELECT * FROM user");
        return $stmt->fetchAll();
    }
    public function addUser($token,  $mail, string $name, string $lastName, $phoneNumber, $adress, string $employmentNumber, string $birthDate, string $username, string $password, string $type, $general, array $extraMail, array $extraPhoneNumber, array $extraAdress) {
        
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 401);
        }
        //check user permissions
        if ($tokeninfo['type'] != 'admin') {
            $message="Admin permissions are requiered to add a user";
            $this->error($message, [], 403); 
        }
        //---------------------------------------------------------------------
        $customerId=$tokeninfo["customer_id"];

        try {
            $this->conn->beginTransaction();
            //veryfies if username already exists
            $stmt = $this->conn->prepare("SELECT 1 FROM user WHERE username = :username AND customer_id = :customerId LIMIT 1");
            $stmt->execute([':username' => $username, "customerId" => $customerId]);
            if ($stmt->fetchColumn()) {
                $message="Username is not available";
                $this->error($message, [], 409); 
            }
            //Adds user
            $general = json_encode($general);
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
            //Retrieves the id of the user just added
            $stmt = $this->conn->prepare("SELECT id FROM user WHERE username = :username");
            $stmt->execute(["username" => $username]);
            $result = $stmt->fetch();
            $id = $result["id"];


            #region add mails
            if(!empty($mail['main']) && isset($mail['main'])){
                $mainMail = $mail['main'];
                $stmt = $this->conn->prepare("SELECT mail FROM mail WHERE mail = :mail");
                $stmt->execute(["mail" => $mainMail]);
                $result = $stmt->fetchAll();
                if(!$result){
                    $stmt = $this->conn->prepare("INSERT INTO mail (mail) VALUES (:mail)");
                    $stmt->execute(["mail" => $mainMail]);
                }

                $stmt = $this->conn->prepare("SELECT id FROM mail WHERE mail = :mail");
                $stmt->execute(["mail" => $mainMail]);
                $result = $stmt->fetch();
                $mailId = $result['id'];

                $stmt = $this->conn->prepare("INSERT INTO mail_connection (user_id, mail_id, is_main) VALUES (:userId, :mailId, 1)");
                $stmt->execute(["userId" => $id, "mailId" => $mailId]);
            }
            if(!empty($mail['extra']) && isset($mail['extra'])){
                foreach($mail['extra'] as $value){
                    $stmt = $this->conn->prepare("SELECT mail FROM mail WHERE mail = :mail");
                    $stmt->execute(["mail" => $value]);
                    $result = $stmt->fetchAll();
                    if(!$result){
                        $stmt = $this->conn->prepare("INSERT INTO mail (mail) VALUES (:mail)");
                        $stmt->execute(["mail" => $value]);
                    }

                    $stmt = $this->conn->prepare("SELECT id FROM mail WHERE mail = :mail");
                    $stmt->execute(["mail" => $value]);
                    $result = $stmt->fetch();
                    $mailId = $result['id'];

                    $stmt = $this->conn->prepare("SELECT id FROM mail_connection WHERE mail_id = :mailId AND user_id = :userId");
                    $stmt->execute(["mailId" => $mailId, "userId" => $id]);
                    $result = $stmt->fetch();
                    
                    if(!$result){
                        $stmt = $this->conn->prepare("INSERT INTO mail_connection (user_id, mail_id) VALUES (:userId, :mailId)");
                        $stmt->execute(["userId" => $id, "mailId" => $mailId]);
                    }
                }
            }
            #endregion

            #region add adresses
            if(!empty($adress['main']) && isset($adress['main'])){
                $mainadress = $adress['main'];
                $stmt = $this->conn->prepare("SELECT adress FROM adress WHERE adress = :adress");
                $stmt->execute(["adress" => $mainadress]);
                $result = $stmt->fetchAll();
                if(!$result){
                    $stmt = $this->conn->prepare("INSERT INTO adress (adress) VALUES (:adress)");
                    $stmt->execute(["adress" => $mainadress]);
                }

                $stmt = $this->conn->prepare("SELECT id FROM adress WHERE adress = :adress");
                $stmt->execute(["adress" => $mainadress]);
                $result = $stmt->fetch();
                $adressId = $result['id'];

                $stmt = $this->conn->prepare("INSERT INTO adress_connection (user_id, adress_id, is_main) VALUES (:userId, :adressId, 1)");
                $stmt->execute(["userId" => $id, "adressId" => $adressId]);
            }
            if(!empty($adress['extra']) && isset($adress['extra'])){
                foreach($adress['extra'] as $value){
                    $stmt = $this->conn->prepare("SELECT adress FROM adress WHERE adress = :adress");
                    $stmt->execute(["adress" => $value]);
                    $result = $stmt->fetchAll();
                    if(!$result){
                        $stmt = $this->conn->prepare("INSERT INTO adress (adress) VALUES (:adress)");
                        $stmt->execute(["adress" => $value]);
                    }

                    $stmt = $this->conn->prepare("SELECT id FROM adress WHERE adress = :adress");
                    $stmt->execute(["adress" => $value]);
                    $result = $stmt->fetch();
                    $adressId = $result['id'];

                    $stmt = $this->conn->prepare("SELECT id FROM adress_connection WHERE adress_id = :adressId AND user_id = :userId");
                    $stmt->execute(["adressId" => $adressId, "userId" => $id]);
                    $result = $stmt->fetch();
                    
                    if(!$result){
                        $stmt = $this->conn->prepare("INSERT INTO adress_connection (user_id, adress_id) VALUES (:userId, :adressId)");
                        $stmt->execute(["userId" => $id, "adressId" => $adressId]);
                    }
                }
            }
            #endregion

            #region add phone numbers
            if(!empty($phoneNumber['main']) && isset($phoneNumber['main'])){
                $mainPhoneNumber = $phoneNumber['main'];
                $stmt = $this->conn->prepare("SELECT phone_number FROM phone_number WHERE phone_number = :phoneNumber");
                $stmt->execute(["phoneNumber" => $mainPhoneNumber]);
                $result = $stmt->fetchAll();
                if(!$result){
                    $stmt = $this->conn->prepare("INSERT INTO phone_number (phone_number) VALUES (:phoneNumber)");
                    $stmt->execute(["phoneNumber" => $mainPhoneNumber]);
                }

                $stmt = $this->conn->prepare("SELECT id FROM phone_number WHERE phone_number = :phoneNumber");
                $stmt->execute(["phoneNumber" => $mainPhoneNumber]);
                $result = $stmt->fetch();
                $phoneNumberId = $result['id'];

                $stmt = $this->conn->prepare("INSERT INTO phone_connection (user_id, phone_id, is_main) VALUES (:userId, :phoneNumberId, 1)");
                $stmt->execute(["userId" => $id, "phoneNumberId" => $phoneNumberId]);
            }
            if(!empty($phoneNumber['extra']) && isset($phoneNumber['extra'])){
                foreach($phoneNumber['extra'] as $value){
                    $stmt = $this->conn->prepare("SELECT phone_number FROM phone_number WHERE phone_number = :phoneNumber");
                    $stmt->execute(["phoneNumber" => $value]);
                    $result = $stmt->fetchAll();
                    if(!$result){
                        $stmt = $this->conn->prepare("INSERT INTO phone_number (phone_number) VALUES (:phoneNumber)");
                        $stmt->execute(["phoneNumber" => $value]);
                    }

                    $stmt = $this->conn->prepare("SELECT id FROM phone_number WHERE phone_number = :phoneNumber");
                    $stmt->execute(["phoneNumber" => $value]);
                    $result = $stmt->fetch();
                    $phoneNumberId = $result['id'];

                    $stmt = $this->conn->prepare("SELECT id FROM phone_connection WHERE phone_id = :phoneNumberId AND user_id = :userId");
                    $stmt->execute(["phoneNumberId" => $phoneNumberId, "userId" => $id]);
                    $result = $stmt->fetch();
                    
                    if(!$result){
                        $stmt = $this->conn->prepare("INSERT INTO phone_connection (user_id, phone_id) VALUES (:userId, :phoneNumberId)");
                        $stmt->execute(["userId" => $id, "phoneNumberId" => $phoneNumberId]);
                    }
                }
            }
            #endregion
            //Success return
            $responsData=["username" => $username, "type" => $type, "id" => $id];
            $message="Successfully added user account";
            $this->success($message, $responsData, 200);
          
        } catch(PDOException $e) {
          $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
        }  
    }
    /*
    public function getUser($token ,$id, $username) { //currently only admin
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 401);
        }

        //---------------------------------------------------------------------
        $customerId=$tokeninfo["customer_id"];
        try {
            $getStmt = $this->conn->prepare("SELECT customer_id, id FROM user WHERE id = :id");
            $getStmt->execute([":id"=>$id]);
            $userInfo = $getStmt->fetch();
            //verifies if user is registered to correct customer
            if ($userInfo["customer_id"] != $customerId) {
                $message="User not found";
                $this->error($message, [], 404); 
            }
            //Gives the correct list for the user to edit
            if ($tokeninfo['type'] == 'admin') {
                $getInfoList = $this->getUserInfoListAdmin;
            } elseif ($userInfo["userId"] == $id) {
                $getInfoList = $this->getUserInfoList;
            } else {
                $message="Insufficient permissions to access specified users information.";
                $this->error($message, [], 403);
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
                $message="User not found";
                $this->error($message, [], 404);
            }

            $responsData=[];
            $message="retrieved user:".$userInfo["username"]."data";
            $this->success($message, $userInfo, 200);

            
            
        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
        }
    }
    */
    public function banUser($token, $banUserId, $expirationDate, $blogBan, $wikiBan, $calendarBan, $reason) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 401);
        }

        //check user permissions
        if ($tokeninfo['type'] != 'admin') {
            $message="Admin permissions are requiered to ban a user";
            $this->error($message, [], 403);
        }

        //---------------------------------------------------------------------
        $customerId=$tokeninfo["customer_id"];
        $banningUser=$tokeninfo["userId"];

        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare("SELECT customer_id, type, id FROM user WHERE id =:id ");
            $stmt->execute([":id"=>$banUserId]);
            $userInfo = $stmt->fetch();
            if($userInfo){
                $userCustomerId = $userInfo["customer_id"];
            }
            //verifies if user is registered to correct customer
            if (!$userInfo || $userCustomerId != $customerId) {
                $message="User not found";
                $this->error($message, [], 404);
            }
            //verify that the ban target user is not an admin
            if ($userInfo["type"] == 'admin') {
                $message="Can not ban an admin account";
                $this->error($message, [], 403); 
            }
            //verify that admin is not banning their own account
            if ($banUserId == $banningUser) {
                $message="Can not ban your own account";
                $this->error($message, [], 400); 
            }
            if($expirationDate < date('Y-m-d H:i:s')){
                $message="Expiration date can not be in the past";
                $this->error($message, [], 400); 
            }


            $stmt = $this->conn->prepare("INSERT INTO ban (user_id, expiration_date, blog, wiki, calendar, reason) VALUES (:user_id, :expiration_date, :blog, :wiki, :calendar, :reason)");
            if(            $stmt->execute([
                ":user_id" => $banUserId, 
                ":expiration_date" => $expirationDate,
                ":blog" => $blogBan,
                ":wiki" => $wikiBan,
                ":calendar" => $calendarBan,
                ":reason" => $reason
                ])){
                    $stmt = $this->conn->prepare("SELECT id FROM ban WHERE user_id = :userId ORDER BY creation_date");
                    $stmt->execute(["userId" => $banUserId]);
                    $banId = $stmt->fetch();
                    
        
                    $responsData=["id" => $banId['id']];
                    $message="Successfully banned user account with id ".$banUserId.".";
                    $this->success($message, $responsData, 200);
        
                }
                else{
                    $responsData=[];
                    $message="failed to ban user";
                    $this->error($message, $responsData, 400);
    
                }
        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
        }  
    }
    public function editUser($token, $editUserId, $mail, $firstName, $lastName, $phoneNumber, $adress, $employmentNumber, $birthDate, $username, $password, $type, $general) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 401);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            $message="Insufficient permissions";
            $this->error($message, [], 403);
        }

        //---------------------------------------------------------------------
        $customerId=$tokeninfo["customer_id"];
        $userId=$tokeninfo["userId"];
        
        try {
            $this->conn->beginTransaction();
            if(!empty($username)){
                $stmt = $this->conn->prepare("SELECT 1 FROM user WHERE username = :username AND customer_id = :customerId LIMIT 1");
                $stmt->execute([':username' => $username, "customerId" => $customerId]);
                if ($stmt->fetchColumn()) {
                    $message="Username is not available";
                    $this->error($message, [], 409); 
                }
            }

            if ($password != "") {
                $newPassword = password_hash($password, PASSWORD_DEFAULT);
            } else {
                $newPassword = null;
            }
            if ($editUserId == "") {
                $editUserId = $userId;
            }



            $getStmt = $this->conn->prepare("SELECT customer_id, id FROM user WHERE id = :id");
            $getStmt->execute([":id"=>$editUserId]);
            $userInfo = $getStmt->fetch();

            //verifies if user is registered to correct customer
            if ($userInfo["customer_id"] != $customerId) {
                $message="User not found";
                $this->error($message, [], 404);
            }
            //Gives the correct list for the user to edit
            if ($tokeninfo['type'] == 'admin') {
                $editableInfoList = $this->allowedEditUserArrayAdmin;
            } elseif ($userInfo["id"] == $editUserId) {
                $editableInfoList = $this->allowedEditUserArray;
            } else {
                $message="Insufficient permissions";
                $this->error($message, [], 403);
            }

            $editField = [
                "first_name" => $firstName,
                "last_name" => $lastName,
                "employment_number" => $employmentNumber,
                "birthdate" => $birthDate,
                "username" => $username,
                "password" => $newPassword,
                "type" => $type,
                "general" => json_encode($general)
            ];  


            $editStringList = [];
            $valueList = [];

            foreach($editableInfoList as $editString){

                if (array_key_exists($editString, $editField) && $editField[$editString] != "") {

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
                foreach($mail['add'] as $value){
                    $stmt = $this->conn->prepare("SELECT mail FROM mail WHERE mail = :mail");
                    $stmt->execute(["mail" => $value]);
                    $result = $stmt->fetchAll();
                    if(!$result){
                        $stmt = $this->conn->prepare("INSERT INTO mail (mail) VALUES (:mail)");
                        $stmt->execute(["mail" => $value]);
                    }

                    $stmt = $this->conn->prepare("SELECT id FROM mail WHERE mail = :mail");
                    $stmt->execute(["mail" => $value]);
                    $result = $stmt->fetch();
                    $mailId = $result['id'];

                    $stmt = $this->conn->prepare("SELECT id FROM mail_connection WHERE mail_id = :mailId AND user_id = :userId");
                    $stmt->execute(["mailId" => $mailId, "userId" => $editUserId]);
                    $result = $stmt->fetch();
                    
                    if(!$result){
                        $stmt = $this->conn->prepare("INSERT INTO mail_connection (user_id, mail_id) VALUES (:userId, :mailId)");
                        $stmt->execute(["userId" => $editUserId, "mailId" => $mailId]);
                    }
                }
            }
            if(!empty($mail['update'])){
                foreach($mail['update'] as $index => $value){
                    // check if the mail to update exists
                    $stmt = $this->conn->prepare("SELECT id FROM mail WHERE mail = :mail");
                    $stmt->execute(["mail" => $value]);
                    $selectMailNew = $stmt->fetchColumn();

                    $stmt = $this->conn->prepare("SELECT id FROM mail WHERE mail = :mail");
                    $stmt->execute(["mail" => $index]);
                    $selectMailOld = $stmt->fetchColumn();
                    
                    if(!$selectMailNew){
                        $stmt = $this->conn->prepare("INSERT INTO mail (mail) VALUES (:mail)");
                        $stmt->execute(["mail" => $value]);

                        $stmt = $this->conn->prepare("SELECT id FROM mail WHERE mail = :mail");
                        $stmt->execute(["mail" => $value]);
                        $selectMailNew = $stmt->fetchColumn();
                    }

                    if(!$selectMailOld){
                        $responsData=[];
                        $message="Update mail: Mail to replace does not exist";
                        $this->error($message, $responsData, 400);
                    }

                    $stmt = $this->conn->prepare("SELECT id FROM mail_connection WHERE mail_id = :mailId");
                    $stmt->execute(["mailId" => $selectMailOld]);
                    $result = $stmt->fetchColumn();
                    if(!$result){
                        $responsData=[];
                        $message="Update mail: User does not have the mail to replace";
                        $this->error($message, $responsData, 400);
                    }
                    else{
                        $stmt = $this->conn->prepare("SELECT id FROM mail_connection WHERE mail_id = :mailId AND user_id = :userId");
                        $stmt->execute(["mailId" => $selectMailNew, "userId" => $editUserId]);
                        $resultMCid = $stmt->fetchColumn();
                        if($resultMCid){
                            $responsData=[];
                            $message="Update mail: User already has the new mail";
                            $this->error($message, $responsData, 400);
                        }
                        else{
                            $stmt = $this->conn->prepare("UPDATE mail_connection SET mail_id = :mailId WHERE user_id = :userId AND mail_id = :mailOldId");
                            $stmt->execute(["mailId" => $selectMailNew, "userId" => $editUserId, "mailOldId" => $selectMailOld]);
                        }
                    }
                }
            }
            if(!empty($mail['delete'])){
                foreach($mail['delete'] as $value){
                    // check if the mail to delete exists
                    $stmt = $this->conn->prepare("SELECT id FROM mail WHERE mail = :mail");
                    $stmt->execute(["mail" => $value]);
                    $selectMail = $stmt->fetch();
                    if(!$selectMail){
                        $responsData=[];
                        $message="Delete mail: Mail does not exist: " . $value;
                        $this->error($message, $responsData, 400);
                    }
                    $stmt = $this->conn->prepare("SELECT id FROM mail_connection WHERE user_id = :userId AND mail_id = :mailId");
                    $stmt->execute(["userId" => $editUserId, "mailId" => $selectMail['id']]);
                    $result = $stmt->fetchColumn();
                    if($result){
                        // delete the selected mail
                        $stmt = $this->conn->prepare("DELETE FROM mail_connection WHERE mail_id = :mailId AND user_id = :userId");
                        $stmt->execute(["userId" => $editUserId, "mailId" => $selectMail['id']]);
                    }
                    else {
                        $responsData=[];
                        $message="Delete mail: User does not have this mail";
                        $this->error($message, $responsData, 400);
                    }
                }
            }

            if(!empty($mail['main'])){
                $mainMail = $mail['main'];
                // checks if the selected main mail already exists in the db
                $stmt = $this->conn->prepare("SELECT mail FROM mail WHERE mail = :mail");
                $stmt->execute(["mail" => $mainMail]);
                $result = $stmt->fetchAll();
                if(!$result){
                    $stmt = $this->conn->prepare("INSERT INTO mail (mail) VALUES (:mail)");
                    $stmt->execute(["mail" => $mainMail]);
                }

                // gets the id of the new main mail
                $stmt = $this->conn->prepare("SELECT id FROM mail WHERE mail = :mail");
                $stmt->execute(["mail" => $mainMail]);
                $result = $stmt->fetch();
                $mailId = $result['id'];

                $stmt = $this->conn->prepare("SELECT is_main, mail_id FROM mail_connection WHERE user_id = :userId");
                $stmt->execute(["userId" => $editUserId]);
                $checkMain = $stmt->fetchAll();
                foreach($checkMain as $value){
                    if($value['is_main'] == 1){
                        $stmt = $this->conn->prepare("UPDATE mail_connection SET is_main = 0 WHERE mail_id = :mailId AND user_id = :userId");
                        $stmt->execute(["mailId" => $value['mail_id'], "userId" => $editUserId]);
                    }
                }


                $stmt = $this->conn->prepare("SELECT * FROM mail_connection WHERE mail_id = :mailId AND user_id = :userId");
                $stmt->execute(["mailId" => $mailId, "userId" => $editUserId]);
                $result = $stmt->fetch();
                if($result){
                    // updates the mail if the user already has the mail
                    $stmt = $this->conn->prepare("UPDATE mail_connection SET is_main = 1 WHERE mail_id = :mailId AND user_id = :userId");
                    $stmt->execute(["mailId" => $mailId, "userId" => $editUserId]);
                }
                else{
                    // adds the new main mail to the user
                    $stmt = $this->conn->prepare("INSERT INTO mail_connection (user_id, mail_id, is_main) VALUES (:userId, :mailId, 1)");
                    $stmt->execute(["userId" => $editUserId, "mailId" => $mailId]);   
                }
            }
            #endregion

            #region phone number
            if(!empty($phoneNumber['add'])){
                foreach($phoneNumber['add'] as $index => $value){
                    $stmt = $this->conn->prepare("SELECT phone_number FROM phone_number WHERE phone_number = :phoneNumber");
                    $stmt->execute(["phoneNumber" => $value]);
                    $result = $stmt->fetchAll();
                    if(!$result){
                        $stmt = $this->conn->prepare("INSERT INTO phone_number (phone_number) VALUES (:phoneNumber)");
                        $stmt->execute(["phoneNumber" => $value]);
                    }

                    $stmt = $this->conn->prepare("SELECT id FROM phone_number WHERE phone_number = :phoneNumber");
                    $stmt->execute(["phoneNumber" => $value]);
                    $result = $stmt->fetch();
                    $phoneNumberId = $result['id'];

                    $stmt = $this->conn->prepare("SELECT id FROM phone_connection WHERE phone_id = :phoneNumberId AND user_id = :userId");
                    $stmt->execute(["phoneNumberId" => $phoneNumberId, "userId" => $editUserId]);
                    $result = $stmt->fetch();
                    
                    if(!$result){
                        $stmt = $this->conn->prepare("INSERT INTO phone_connection (user_id, phone_id) VALUES (:userId, :phoneNumberId)");
                        $stmt->execute(["userId" => $editUserId, "phoneNumberId" => $phoneNumberId]);
                    }
                }
            }
            if(!empty($phoneNumber['update'])){
                foreach($phoneNumber['update'] as $index => $value){
                    // check if the phone number to update exists
                    $stmt = $this->conn->prepare("SELECT id FROM phone_number WHERE phone_number = :phone_number");
                    $stmt->execute(["phone_number" => $value]);
                    $selectPhoneNew = $stmt->fetchColumn();

                    $stmt = $this->conn->prepare("SELECT id FROM phone_number WHERE phone_number = :phone_number");
                    $stmt->execute(["phone_number" => $index]);
                    $selectPhoneOld = $stmt->fetchColumn();
                    
                    if(!$selectPhoneNew){
                        $stmt = $this->conn->prepare("INSERT INTO phone_number (phone_number) VALUES (:phone_number)");
                        $stmt->execute(["phone_number" => $value]);

                        $stmt = $this->conn->prepare("SELECT id FROM phone_number WHERE phone_number = :phone_number");
                        $stmt->execute(["phone_number" => $value]);
                        $selectPhoneNew = $stmt->fetchColumn();
                    }

                    if(!$selectPhoneOld){
                        $responsData=[];
                        $message="Update phone number: Phone number to replace does not exist";
                        $this->error($message, $responsData, 400);
                    }

                    $stmt = $this->conn->prepare("SELECT id FROM phone_connection WHERE phone_id = :phone_id");
                    $stmt->execute(["phone_id" => $selectPhoneOld]);
                    $result = $stmt->fetchColumn();
                    if(!$result){
                        $responsData=[];
                        $message="Update phone number: User does not have the phone number to replace";
                        $this->error($message, $responsData, 400);
                    }
                    else{
                        $stmt = $this->conn->prepare("SELECT id FROM phone_connection WHERE phone_id = :phone_id AND user_id = :userId");
                        $stmt->execute(["phone_id" => $selectPhoneNew, "userId" => $editUserId]);
                        $resultMCid = $stmt->fetchColumn();
                        if($resultMCid){
                            $responsData=[];
                            $message="Update phone number: User already has the new phone number";
                            $this->error($message, $responsData, 400);
                        }
                        else{
                            $stmt = $this->conn->prepare("UPDATE phone_connection SET phone_id = :phone_id WHERE user_id = :userId AND phone_id = :phoneOldId");
                            $stmt->execute(["phone_id" => $selectPhoneNew, "userId" => $editUserId, "phoneOldId" => $selectPhoneOld]);
                        }
                    }
                }
            }
            if(!empty($phoneNumber['delete'])){
                foreach($phoneNumber['delete'] as $value){
                    // check if the mail to delete exists
                    $stmt = $this->conn->prepare("SELECT id FROM phone_number WHERE phone_number = :phone_number");
                    $stmt->execute(["phone_number" => $value]);
                    $selectPhone = $stmt->fetch();
                    if(!$selectPhone){
                        $responsData=[];
                        $message="Delete phone number: Phone number does not exist";
                        $this->error($message, $responsData, 400);
                    }
                    $stmt = $this->conn->prepare("SELECT id FROM phone_connection WHERE user_id = :userId AND phone_id = :phone_id");
                    $stmt->execute(["userId" => $editUserId, "phone_id" => $selectPhone['id']]);
                    $result = $stmt->fetchColumn();
                    if($result){
                        // delete the selected mail
                        $stmt = $this->conn->prepare("DELETE FROM phone_connection WHERE phone_id = :phone_id AND user_id = :userId");
                        $stmt->execute(["userId" => $editUserId, "phone_id" => $selectPhone['id']]);
                    }
                    else {
                        $responsData=[];
                        $message="Delete phone number: User does not have this phone number";
                        $this->error($message, $responsData, 400);
                    }
                }
            }

            if(!empty($phoneNumber['main'])){
                $mainPhoneNumber = $phoneNumber['main'];
                // checks if the selected main mail already exists in the db
                $stmt = $this->conn->prepare("SELECT phone_number FROM phone_number WHERE phone_number = :phone_number");
                $stmt->execute(["phone_number" => $mainPhoneNumber]);
                $result = $stmt->fetchAll();
                if(!$result){
                    $stmt = $this->conn->prepare("INSERT INTO phone_number (phone_number) VALUES (:phone_number)");
                    $stmt->execute(["phone_number" => $mainPhoneNumber]);
                }

                // gets the id of the new main mail
                $stmt = $this->conn->prepare("SELECT id FROM phone_number WHERE phone_number = :phone_number");
                $stmt->execute(["phone_number" => $mainPhoneNumber]);
                $result = $stmt->fetch();
                $phoneId = $result['id'];

                $stmt = $this->conn->prepare("SELECT is_main, phone_id FROM phone_connection WHERE user_id = :userId");
                $stmt->execute(["userId" => $editUserId]);
                $checkMain = $stmt->fetchAll();
                foreach($checkMain as $value){
                    if($value['is_main'] == 1){
                        $stmt = $this->conn->prepare("UPDATE phone_connection SET is_main = 0 WHERE phone_id = :phone_id AND user_id = :userId");
                        $stmt->execute(["phone_id" => $value['phone_id'], "userId" => $editUserId]);
                    }
                }

                $stmt = $this->conn->prepare("SELECT * FROM phone_connection WHERE phone_id = :phone_id AND user_id = :userId");
                $stmt->execute(["phone_id" => $phoneId, "userId" => $editUserId]);
                $result = $stmt->fetch();
                if($result){
                    $stmt = $this->conn->prepare("UPDATE phone_connection SET is_main = 1 WHERE phone_id = :phone_id AND user_id = :userId");
                    $stmt->execute(["phone_id" => $phoneId, "userId" => $editUserId]);
                }
                else{
                    // adds the new main mail to the user
                    $stmt = $this->conn->prepare("INSERT INTO phone_connection (user_id, phone_id, is_main) VALUES (:userId, :phone_id, 1)");
                    $stmt->execute(["userId" => $editUserId, "phone_id" => $phoneId]);   
                }
            }
            #endregion

            #region adress
            if(!empty($adress['add'])){
                foreach($adress['add'] as $index => $value){
                    $stmt = $this->conn->prepare("SELECT adress FROM adress WHERE adress = :adress");
                    $stmt->execute(["adress" => $value]);
                    $result = $stmt->fetchAll();
                    if(!$result){
                        $stmt = $this->conn->prepare("INSERT INTO adress (adress) VALUES (:adress)");
                        $stmt->execute(["adress" => $value]);
                    }

                    $stmt = $this->conn->prepare("SELECT id FROM adress WHERE adress = :adress");
                    $stmt->execute(["adress" => $value]);
                    $result = $stmt->fetch();
                    $adressId = $result['id'];

                    $stmt = $this->conn->prepare("SELECT id FROM adress_connection WHERE adress_id = :adressId AND user_id = :userId");
                    $stmt->execute(["adressId" => $adressId, "userId" => $editUserId]);
                    $result = $stmt->fetch();
                    
                    if(!$result){
                        $stmt = $this->conn->prepare("INSERT INTO adress_connection (user_id, adress_id) VALUES (:userId, :adressId)");
                        $stmt->execute(["userId" => $editUserId, "adressId" => $adressId]);
                    }
                }
            }
            if(!empty($adress['update'])){
                foreach($adress['update'] as $index => $value){
                    // check if the phone number to update exists
                    $stmt = $this->conn->prepare("SELECT id FROM adress WHERE adress = :adress");
                    $stmt->execute(["adress" => $value]);
                    $selectAdressNew = $stmt->fetchColumn();

                    $stmt = $this->conn->prepare("SELECT id FROM adress WHERE adress = :adress");
                    $stmt->execute(["adress" => $index]);
                    $selectAdressOld = $stmt->fetchColumn();
                    
                    if(!$selectAdressNew){
                        $stmt = $this->conn->prepare("INSERT INTO adress (adress) VALUES (:adress)");
                        $stmt->execute(["adress" => $value]);

                        $stmt = $this->conn->prepare("SELECT id FROM adress WHERE adress = :adress");
                        $stmt->execute(["adress" => $value]);
                        $selectAdressNew = $stmt->fetchColumn();
                    }

                    if(!$selectAdressOld){
                        $responsData=[];
                        $message="Update address: Address to replace does not exist";
                        $this->error($message, $responsData, 400);
                    }

                    $stmt = $this->conn->prepare("SELECT id FROM adress_connection WHERE adress_id = :adress_id");
                    $stmt->execute(["adress_id" => $selectAdressOld]);
                    $result = $stmt->fetchColumn();
                    if(!$result){
                        $responsData=[];
                        $message="Update address: User does not have the address to replace";
                        $this->error($message, $responsData, 400);
                    }
                    else{
                        $stmt = $this->conn->prepare("SELECT id FROM adress_connection WHERE adress_id = :adress_id AND user_id = :userId");
                        $stmt->execute(["adress_id" => $selectAdressNew, "userId" => $editUserId]);
                        $resultMCid = $stmt->fetchColumn();
                        if($resultMCid){
                            $responsData=[];
                            $message="Update address: User already has the new address";
                            $this->error($message, $responsData, 400);
                        }
                        else{
                            $stmt = $this->conn->prepare("UPDATE adress_connection SET adress_id = :adress_id WHERE user_id = :userId AND adress_id = :adressOldId");
                            $stmt->execute(["adress_id" => $selectAdressNew, "userId" => $editUserId, "adressOldId" => $selectAdressOld]);
                        }
                    }
                }
            }
            if(!empty($adress['delete'])){
                foreach($adress['delete'] as $value){
                    // check if the mail to delete exists
                    $stmt = $this->conn->prepare("SELECT id FROM adress WHERE adress = :adress");
                    $stmt->execute(["adress" => $value]);
                    $selectPhone = $stmt->fetch();
                    if(!$selectPhone){
                        $responsData=[];
                        $message="Delete address: Address does not exist";
                        $this->error($message, $responsData, 400);
                    }
                    $stmt = $this->conn->prepare("SELECT id FROM adress_connection WHERE user_id = :userId AND adress_id = :adress_id");
                    $stmt->execute(["userId" => $editUserId, "adress_id" => $selectPhone['id']]);
                    $result = $stmt->fetchColumn();
                    if($result){
                        // delete the selected mail
                        $stmt = $this->conn->prepare("DELETE FROM adress_connection WHERE adress_id = :adress_id AND user_id = :userId");
                        $stmt->execute(["userId" => $editUserId, "adress_id" => $selectPhone['id']]);
                    }
                    else {
                        $responsData=[];
                        $message="Delete address: User does not have this address";
                        $this->error($message, $responsData, 400);
                    }
                }
            }

            if(!empty($adress['main'])){
                $mainAdress = $adress['main'];
                // checks if the selected main mail already exists in the db
                $stmt = $this->conn->prepare("SELECT adress FROM adress WHERE adress = :adress");
                $stmt->execute(["adress" => $mainAdress]);
                $result = $stmt->fetchAll();
                if(!$result){
                    $stmt = $this->conn->prepare("INSERT INTO adress (adress) VALUES (:adress)");
                    $stmt->execute(["adress" => $mainAdress]);
                }

                // gets the id of the new main mail
                $stmt = $this->conn->prepare("SELECT id FROM adress WHERE adress = :adress");
                $stmt->execute(["adress" => $mainAdress]);
                $result = $stmt->fetch();
                $adressId = $result['id'];

                $stmt = $this->conn->prepare("SELECT is_main, adress_id FROM adress_connection WHERE user_id = :userId");
                $stmt->execute(["userId" => $editUserId]);
                $checkMain = $stmt->fetchAll();
                foreach($checkMain as $value){
                    if($value['is_main'] == 1){
                        $stmt = $this->conn->prepare("UPDATE adress_connection SET is_main = 0 WHERE adress_id = :adress_id AND user_id = :userId");
                        $stmt->execute(["adress_id" => $value['adress_id'], "userId" => $editUserId]);
                    }
                }

                $stmt = $this->conn->prepare("SELECT * FROM adress_connection WHERE adress_id = :adress_id AND user_id = :userId");
                $stmt->execute(["adress_id" => $adressId, "userId" => $editUserId]);
                $result = $stmt->fetch();
                if($result){
                    $stmt = $this->conn->prepare("UPDATE adress_connection SET is_main = 1 WHERE adress_id = :adress_id AND user_id = :userId");
                    $stmt->execute(["adress_id" => $adressId, "userId" => $editUserId]);
                }
                else{
                    // adds the new main mail to the user
                    $stmt = $this->conn->prepare("INSERT INTO adress_connection (user_id, adress_id, is_main) VALUES (:userId, :adress_id, 1)");
                    $stmt->execute(["userId" => $editUserId, "adress_id" => $adressId]);   
                }
            }
            #endregion

            // if(empty($editsString)){
            //     $responsData=[];
            //     $message="No user data to edit";
            //     $this->success($message, $responsData, 200);
            // }


            $responsData=[];
            $message="Successfully edited user account info.";
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
            $this->error($message, [], 500);
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
    /*
    public function getAllBannedUsers($token, $request) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 401);
 
        }

        //check user permissions
        if ($tokeninfo['type'] != 'admin') {
            $message="Insufficient permissions";
            $this->error($message, [], 403);
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
            $this->error($message, [], 500);
        }  
    }
    */
    public function removeUser($removeUserId, $token) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 401);
        }

        //check user permissions
        if ($tokeninfo['type'] != 'admin') {
            $message="Admin permissions are requiered to remove a user.";
            $this->error($message, [], 403);
        }

        //---------------------------------------------------------------------
        $customerId=$tokeninfo["customer_id"];
        //check so user isent trying to remove himself
        if ($tokeninfo['userId'] == $removeUserId) {
            $message="Cant remove your own admin account";
            $this->error($message, [], 400); 
        }

        try {
            $this->conn->beginTransaction();
            $getStmt = $this->conn->prepare("SELECT customer_id FROM user WHERE id = :id");
            $getStmt->execute([":id"=>$removeUserId]);
            $userInfo = $getStmt->fetch();
            //verifies if user is registered to correct customer
            if ($userInfo["customer_id"] != $customerId) {
                $message="User not found";
                $this->error($message, [], 404);
            }
            if (empty($userInfo)) {
                $message="User not found";
                $this->error($message, [], 404);
            }
            $stmt = $this->conn->prepare("DELETE FROM user WHERE id = :id");
            $stmt->execute([":id"=>$removeUserId]);

            $responsData=[];
            $message="Successfully removed user account";
            $this->success($message, $responsData, 200);
        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
        }  
    }    

    public function removeBan($removeBanId, $token) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 401);
        }

        //check user permissions
        if ($tokeninfo['type'] != 'admin') {
            $message="Admin permissions are requiered to remove a ban.";
            $this->error($message, [], 403);
        }

        //---------------------------------------------------------------------
        $customerId=$tokeninfo["customer_id"];

        try {


            $this->conn->beginTransaction();
            


            $stmt = $this->conn->prepare("SELECT user.customer_id FROM user INNER JOIN ban ON user.id = ban.user_id WHERE ban.id = :id");
            $stmt->execute([":id"=>$removeBanId]);
            $userInfo = $stmt->fetch();
            
            if (empty($userInfo)) {
                $message="Ban with this id doesnt exist.";
                $this->error($message, [], 404); 
            }



            $userCustomerId = $userInfo["customer_id"];
            //verify if ban exists
            if ($userCustomerId == false) {
                $message="Ban doesnt exist";
                $this->error($message, [], 400); 
            }
            //verify access this ban
            
            if ($userCustomerId != $customerId) {
                $message="User not found";
                $this->error($message, [], 404);
            }
            $stmt = $this->conn->prepare("DELETE FROM ban WHERE id = :id");
            $stmt->execute([":id"=>$removeBanId]);

            $responsData=[];
            $message="Successfully removed ban from user account.";
            $this->success($message, $responsData, 200);
        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
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

        $userCheckStmt = $this->conn->prepare("SELECT id FROM user WHERE customer_id = :customer_id AND type = 'admin'");
        $userCheckStmt->execute([":customer_id" => $result['user_id']]);

        $userInfo = $userCheckStmt->fetch();

        if (!$userInfo) {
            $createAdminStmt = $this->conn->prepare("INSERT INTO user (customer_id, username, password, type, creation_date, latest_update) VALUES (:customer_id, :username, :password, 'admin', NOW(), NOW())");
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $createAdminStmt->execute([
                ":customer_id" => $result['user_id'],
                ":username" => $username,
                ":password" => $hashedPassword
            ]);

            $createdUsersToken = $auth->getAuthToken($username, $password, $result['session_key']);

            $message = "No admin account found for this company id - created admin account with provided credentials.";
            $this->success($message, ["username" => $username, "token" => $createdUsersToken], 200);
        }

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
    /*
    public function getUserBans($token ,$id) { //currently only admin
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 401);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            $message="Insufficient permissions";
            $this->error($message, [], 403);
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
                $message="User not found";
                $this->error($message, [], 404);
            }
            
            $stmt = $this->conn->prepare("SELECT * FROM `ban` WHERE user_id =:id");
            $stmt->execute([":id"=>$id]);

            
            $userInfo = $stmt->fetch();
            
            //Verifies that the requested user exists
            if (!$userInfo) {
                $message="User not found";
                $this->error($message, [], 404);
            }


            $responsData=["users" => $userInfo];
            $message="retrieved user bans";
            $this->success($message, $responsData, 200);

            
            
        } catch(PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
        }
    }
    */
    public function searchUsers($token, $filter, $searchQuery) { //currently only admin
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 401);
        }

        //check user permissions
        if ($tokeninfo['type'] != 'admin') {
            $message="Insufficient permissions";
            $this->error($message, [], 403);
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
                $message="Search query is empty, enter a query.";
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
                $this->error($message, [], 404); 
            }


            $message="Successfully retrieved search results of users.";
            $this->success($message, $userInfo, 200);

        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
        }
    }
    public function getBans($token, $userId) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 401);
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

                $stmt = $this->conn->prepare("SELECT customer_id, id FROM user WHERE id = :id");
                $stmt->execute([":id"=>$userId]);
                $userInfo = $stmt->fetch();
                if(!$userInfo){
                    $message="User does not exist";
                    $this->error($message, [], 400);
                }
                if ($tokeninfo['type'] != 'admin' && $userInfo["id"] != $userId) {
                    $message="Insufficient permissions to access specified user information";
                    $this->error($message, [], 403);
                }
                if ($userInfo["customer_id"] != $customerId) {
                    $message="User not found";
                    $this->error($message, [], 404);
                }
                if (empty($userInfo)) {
                    $message="User not found";
                    $this->error($message, [], 404); 
                }

            } else if($tokeninfo['type'] != 'admin') {
                $message="Admin permissions are requiered to get all registered bans";
                $this->error($message, [], 403);
            }

            $getStmt = $this->conn->prepare($sqlExecute);
            $getStmt->execute([":input"=>$input]);
            $userInfo = $getStmt->fetchall();

            $responsData=["bans" => $userInfo];
            $message="Successfully retrieved bans of user accounts.";
            $this->success($message, $responsData, 200); 
            
        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
        }
    }
    public function is_assoc($array): bool {
        if (!is_array($array)) {
            return false;
        }
        if ($array === []) return false;
        return array_keys($array) !== range(0, count($array) - 1);
    }
    public function getAllUsers($token, $searchFilter, $searchAmount, $offset, $getUserId, $orderBy, $searchQuery) { //only admin?
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 401);
        }

        //check user permissions
        if ($tokeninfo['type'] != 'admin' && $tokeninfo['type'] != 'end_user') {
            $message="Insufficient permissions to access user information";
            $this->error($message, [], 403);
        }

        //---------------------------------------------------------------------
        $customerId=$tokeninfo["customer_id"];
        $userId=$tokeninfo["userId"];


        if ($getUserId != "") {
            try {
                /*
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

                    // $getStmt = $this->conn->prepare("SELECT customer_id, id FROM user WHERE id = :id");
                    // $getStmt->execute([":id"=>$getUserId]);
                    // $userInfo = $getStmt->fetch();
                    // if (empty($userInfo)) {
                    //     $message="User not found";
                    //     $this->error($message, [], 404);
                    // }
                    // if ($tokeninfo["customer_id"] == $userInfo["customer_id"]) {
                    //     if ($tokeninfo['type'] == 'admin') {
                    //         $stmtSelect = $this->getUserAdmin;
                    //     } elseif ($userId == $getUserId) {
                    //         $stmtSelect = $this->getOwnUserData;
                    //     } elseif ($tokeninfo['type'] == 'end_user') {
                    //         $stmtSelect = $this->getUserEndUser;
                    //     }
                    // } else {
                    //     $message="User not found";
                    //     $this->error($message, [], 404);
                    // }

                    // // Build SELECT string dynamically with extra fields using GROUP_CONCAT
                    // $selectStringArray = [];
                    // foreach ($stmtSelect as $col) {
                    //     switch ($col) {
                    //         case 'extra_mail':
                    //             $selectStringArray[] = "(SELECT GROUP_CONCAT(mail SEPARATOR ',') FROM mail WHERE user_id = user.id) AS extra_mail";
                    //             break;
                    //         case 'extra_adress':
                    //             $selectStringArray[] = "(SELECT GROUP_CONCAT(adress SEPARATOR ',') FROM adress WHERE user_id = user.id) AS extra_address";
                    //             break;
                    //         case 'extra_phone_number':
                    //             $selectStringArray[] = "(SELECT GROUP_CONCAT(phone_number SEPARATOR ',') FROM phone_number WHERE user_id = user.id) AS extra_phone_number";
                    //             break;
                    //         case 'main_mail':
                    //             $selectStringArray[] = "(SELECT mail FROM mail WHERE id = user.main_mail) AS main_mail";
                    //             break;
                    //         case 'main_adress':
                    //             $selectStringArray[] = "(SELECT adress FROM adress WHERE id = user.main_adress) AS main_address";
                    //             break;
                    //         case 'phone_number':
                    //             $selectStringArray[] = "(SELECT phone_number FROM phone_number WHERE id = user.phone_number) AS main_phone_number";
                    //             break;
                    //         default:
                    //             $selectStringArray[] = $col;
                    //     }
                    // }

                    // $selectString = implode(", ", $selectStringArray);
                    // // Prepare and execute the new query
                    // $sqlExecute = "SELECT ".$selectString." FROM user WHERE id = :id";
                    // $stmt = $this->conn->prepare($sqlExecute);
                    // $stmt->execute([":id"=>$getUserId]);
                    // $userData = $stmt->fetch();


                    // $responsData=["users" => $userData];
                    // $message="Retrieved User Data";
                    // $this->success($message, $responsData, 200);
                */
                $getStmt = $this->conn->prepare("SELECT customer_id, id FROM user WHERE id = :id");
                $getStmt->execute([":id" => $getUserId]);
                $userInfo = $getStmt->fetch();

                if (empty($userInfo)) {
                    $this->error("User not found", [], 404);
                }

                if ($tokeninfo["customer_id"] !== $userInfo["customer_id"]) {
                    $this->error("User not found", [], 404);
                }

                // Determine which fields to select:
                if ($tokeninfo['type'] === 'admin') {
                    $stmtSelect = $this->getUserAdmin;
                } elseif ($userId == $getUserId) {
                    $stmtSelect = $this->getOwnUserData;
                } elseif ($tokeninfo['type'] === 'end_user') {
                    $stmtSelect = $this->getUserEndUser;
                }

                // Build dynamic select fields
                $selectStringArray = [];
                foreach ($stmtSelect as $col) {
                    switch ($col) {

                        // MAIN MAIL
                        case 'main_mail':
                            $selectStringArray[] =
                                "(SELECT m.mail 
                                FROM mail m
                                JOIN mail_connection mc ON mc.mail_id = m.id
                                WHERE mc.user_id = user.id AND mc.is_main = 1
                                ) AS main_mail";
                            break;

                        // EXTRA MAILS
                        case 'extra_mail':
                            $selectStringArray[] =
                                "(SELECT GROUP_CONCAT(m.mail SEPARATOR ',')
                                FROM mail m
                                JOIN mail_connection mc ON mc.mail_id = m.id
                                WHERE mc.user_id = user.id AND mc.is_main = 0
                                ) AS extra_mail";
                            break;

                        // MAIN ADDRESS
                        case 'main_address':
                            $selectStringArray[] =
                                "(SELECT a.adress
                                FROM adress a
                                JOIN adress_connection ac ON ac.adress_id = a.id
                                WHERE ac.user_id = user.id AND ac.is_main = 1
                                ) AS main_address";
                            break;

                        // EXTRA ADDRESSES
                        case 'extra_address':
                            $selectStringArray[] =
                                "(SELECT GROUP_CONCAT(a.adress SEPARATOR ',')
                                FROM adress a
                                JOIN adress_connection ac ON ac.adress_id = a.id
                                WHERE ac.user_id = user.id AND ac.is_main = 0
                                ) AS extra_address";
                            break;

                        // MAIN PHONE
                        case 'main_phone_number':
                            $selectStringArray[] =
                                "(SELECT p.phone_number
                                FROM phone_number p
                                JOIN phone_connection pc ON pc.phone_id = p.id
                                WHERE pc.user_id = user.id AND pc.is_main = 1
                                ) AS main_phone_number";
                            break;

                        // EXTRA PHONES
                        case 'extra_phone_number':
                            $selectStringArray[] =
                                "(SELECT GROUP_CONCAT(p.phone_number SEPARATOR ',')
                                FROM phone_number p
                                JOIN phone_connection pc ON pc.phone_id = p.id
                                WHERE pc.user_id = user.id AND pc.is_main = 0
                                ) AS extra_phone_number";
                            break;

                        default:
                            $selectStringArray[] = "user." . $col;
                    }
                }

                $selectString = implode(", ", $selectStringArray);

                // Execute query
                $sqlExecute = "SELECT $selectString FROM user WHERE id = :id";
                $stmt = $this->conn->prepare($sqlExecute);
                $stmt->execute([":id" => $getUserId]);
                $userData = $stmt->fetch();

                $this->success("Retrieved User Data", ["users" => $userData], 200);

            } catch (PDOException $e) {
                $message="Database error: " . $e->getMessage();
                $this->error($message, [], 500);
            }
        } else {   
            
            if ($tokeninfo['type'] == 'admin') {
                $stmtSelect = $this->getUserAdmin;
                $stmtSearch = $this->getUserAdminSearch;
            } elseif ($tokeninfo["type"] == "end_user") {
                $stmtSelect = $this->getUserEndUser;
                $stmtSearch = $this->getUserEndUser;

            }
            $sqlLimit = "";
            if ($searchAmount != ""){
                $sqlLimit = " LIMIT ".$searchAmount;
                $sqlLimit = $sqlLimit." OFFSET ".$offset;
            }

            $query = "";
            $query .= " customer_id = :customer_id";
            $params[":customer_id"] = $customerId;

            $allowedFilters = $stmtSearch;
            if ($searchQuery != "") {

                if (!empty(array_diff($searchFilter, $allowedFilters))) {
                    $this->error("Invalid search filter", [], 400); 
                }

                if (!is_array($searchFilter)) {
                    $this->error("searchFilter must be an array", [], 400);
                }

                if (!empty($searchFilter)) {
                    $conditions = [];
                    foreach ($searchFilter as $index => $filter) {
                        $paramName = ":searchQuery$index";       // unique parameter
                        $conditions[] = "user.$filter LIKE $paramName";
                        $params[$paramName] = "%" . $searchQuery . "%";  // bind separately
                    }

                    $query .= " AND (" . implode(" OR ", $conditions) . ")";
                } else {
                    // no filters → search all allowed columns
                    $query .= " AND user.username LIKE %$searchQuery%";
                }
            }

            $orderByString = "";
            if ($orderBy != "") {
                if (in_array($orderBy, $stmtSelect)) {
                    $orderByString = " ORDER BY $orderBy";
                }
                
            }
            

            
            $query .= $orderByString.$sqlLimit;



            // Build dynamic select fields
            $selectStringArray = [];
            foreach ($stmtSelect as $col) {
                switch ($col) {

                    // MAIN MAIL
                    case 'main_mail':
                        $selectStringArray[] =
                            "(SELECT m.mail 
                            FROM mail m
                            JOIN mail_connection mc ON mc.mail_id = m.id
                            WHERE mc.user_id = user.id AND mc.is_main = 1
                            ) AS main_mail";
                        break;

                    // EXTRA MAILS
                    case 'extra_mail':
                        $selectStringArray[] =
                            "(SELECT GROUP_CONCAT(m.mail SEPARATOR ',')
                            FROM mail m
                            JOIN mail_connection mc ON mc.mail_id = m.id
                            WHERE mc.user_id = user.id AND mc.is_main = 0
                            ) AS extra_mail";
                        break;

                    // MAIN ADDRESS
                    case 'main_address':
                        $selectStringArray[] =
                            "(SELECT a.adress
                            FROM adress a
                            JOIN adress_connection ac ON ac.adress_id = a.id
                            WHERE ac.user_id = user.id AND ac.is_main = 1
                            ) AS main_address";
                        break;

                    // EXTRA ADDRESSES
                    case 'extra_address':
                        $selectStringArray[] =
                            "(SELECT GROUP_CONCAT(a.adress SEPARATOR ',')
                            FROM adress a
                            JOIN adress_connection ac ON ac.adress_id = a.id
                            WHERE ac.user_id = user.id AND ac.is_main = 0
                            ) AS extra_address";
                        break;

                    // MAIN PHONE
                    case 'main_phone_number':
                        $selectStringArray[] =
                            "(SELECT p.phone_number
                            FROM phone_number p
                            JOIN phone_connection pc ON pc.phone_id = p.id
                            WHERE pc.user_id = user.id AND pc.is_main = 1
                            ) AS main_phone_number";
                        break;

                    // EXTRA PHONES
                    case 'extra_phone_number':
                        $selectStringArray[] =
                            "(SELECT GROUP_CONCAT(p.phone_number SEPARATOR ',')
                            FROM phone_number p
                            JOIN phone_connection pc ON pc.phone_id = p.id
                            WHERE pc.user_id = user.id AND pc.is_main = 0
                            ) AS extra_phone_number";
                        break;

                    default:
                        $selectStringArray[] = "user." . $col;
                }
            }


            $selectString = implode(", ", $selectStringArray);

            // Execute query
            $sqlExecute = "SELECT $selectString FROM user WHERE $query";

 
            $stmt = $this->conn->prepare($sqlExecute);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            $userData = $stmt->fetchall();
            $this->success("Retrieved User Data", ["users" => $userData], 200);


        }


        try {
            $getStmt = $this->conn->prepare($sqlExecute);
            $getStmt->execute([":customer_id"=>$tokeninfo["customer_id"]]);
            $userData = $getStmt->fetchall();
            $responsData=["users" => $userData];
            $message="Successfully retrieved user accounts info.";
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
            $this->error($message, [], 500);
        }


    }    
    
}
?>