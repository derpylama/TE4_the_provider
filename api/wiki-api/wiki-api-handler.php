<?php

require_once('../api-handler.php');
class WikiApiHandler extends BaseApiHandler{

    protected function checkServiceAndToken($token, $service="wiki"){
        return parent::checkServiceAndToken($token, $service);
    }

    public function exampleFunction($param, $param2, $param3){
        try {
            // all stmts here and logic


        } catch (PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }  
    }

    public function createWiki($title, $content, $token){
        //              needed everywhere in all endpoint functions
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
        $user_id=$tokeninfo["userId"];

        try {
            // Check if user already has a wiki
            $checkStmt = $this->conn->prepare("SELECT 1 FROM wiki WHERE user_id = :user_id LIMIT 1");
            $checkStmt->execute([':user_id' => $user_id]);

            if ($checkStmt->fetchColumn()) {
                return json_encode([
                    "status" => "error",
                    "message" => "User already has a wiki"
                ]);
            }
    
            // 1. Insert main wiki row
            $stmt = $this->conn->prepare("
                INSERT INTO wiki (user_id, title)
                VALUES (:user_id, :title)
            ");
            $stmt->execute([
                ':user_id' => $user_id,
                ':title' => $title
            ]);
    
            // Get inserted wiki ID
            $wiki_id = $this->conn->lastInsertId();
    
            // 2. Insert the first wiki change (content)
            $stmt2 = $this->conn->prepare("
                INSERT INTO wiki_changes (wiki_id, content, user_id)
                VALUES (:wiki_id, :content, :user_id)
            ");
            $stmt2->execute([
                ':wiki_id' => $wiki_id,
                ':content' => $content,
                ':user_id' => $user_id
            ]);
    
            // 3. Return success JSON
            return json_encode([
                "status" => "success",
                "message" => "Wiki created successfully.",
                "wiki_id" => $wiki_id
            ]);
    
        } catch (PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }  
    }
    public function editWiki($newContent, $wiki_id, $token){ //cant change title for now
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
        $user_id=$tokeninfo["userId"];

        try {
            // Check if wiki exists
            $checkStmt = $this->conn->prepare("SELECT 1 FROM wiki WHERE id = :wiki_id LIMIT 1");
            $checkStmt->execute([':wiki_id' => $wiki_id]);
            
            if (!$checkStmt->fetchColumn()) {
                return json_encode([
                    "status" => "error",
                    "message" => "Wiki does not exist"
                ]);
            }
            

            // 1. Insert new wiki change
            $stmt = $this->conn->prepare("
            INSERT INTO wiki_changes (wiki_id, content, user_id)
            VALUES (:wiki_id, :content, :user_id)
            ");
            $stmt->execute([
                ':wiki_id' => $wiki_id,
                ':content' => $newContent,
                ':user_id' => $user_id
            ]);

            return json_encode([
                "status" => "success",
                "message" => "Wiki edited successfully."
            ]);


        } catch (PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }  
    }

    public function getWiki($token, $query = '') { //allows searching with a query ex hello
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
        $customer_id=$tokeninfo["customer_id"];

        try {
            // No search query -> return all
            if (empty($query)) {
                $stmt = $this->conn->prepare("
                    SELECT w.*
                    FROM wiki w
                    JOIN user u ON w.user_id = u.id
                    WHERE u.customer_id = :customer_id
                ");
                $stmt->execute([':customer_id' => $customer_id]);
            } 
            else {
                // Search query -> return filtered list
                $stmt = $this->conn->prepare("
                    SELECT w.*, wc.content
                    FROM wiki w
                    JOIN user u ON w.user_id = u.id
                    LEFT JOIN wiki_changes wc 
                        ON wc.id = (
                            SELECT id FROM wiki_changes 
                            WHERE wiki_id = w.id 
                            ORDER BY time DESC 
                            LIMIT 1
                        )
                    WHERE u.customer_id = :customer_id
                      AND (w.title LIKE :search OR wc.content LIKE :search)
                ");
    
                $stmt->execute([
                    ':customer_id' => $customer_id,
                    ':search' => '%' . $query . '%'  //partial matching 
                ]);
            }
    
            $wikis = $stmt->fetchAll();
    
            return json_encode([
                "status" => "success",
                "message" => "Wikis retrieved successfully.",
                "wikis" => $wikis
            ]);
            
        } catch (PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
    }

    public function getAllVersions($wiki_id, $token){
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

        try {

            $stmt = $this->conn->prepare("
                SELECT *
                FROM wiki_changes
                WHERE wiki_id = :wiki_id
                ORDER BY time DESC
            ");

            $stmt->execute([':wiki_id' => $wiki_id]);

            $versions = $stmt->fetchAll();

            return json_encode([
                "status" => "success",
                "versions" => $versions
            ]);


        } catch (PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }  
    }

    public function restoreWiki($wikiChanges_id, $token) {
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
        $customer_id=$tokeninfo["customer_id"];
        try {
            // 1. Get wiki_id and timestamp of this wiki change
            $stmt = $this->db->prepare("
                SELECT wiki_id, time
                FROM wiki_changes
                WHERE id = :id
            ");
            $stmt->execute([':id' => $wikiChanges_id]);
            $change = $stmt->fetch();
    
            if (!$change) {
                return json_encode([
                    "status" => "error",
                    "message" => "Wiki change not found."
                ]);
            }
    
            $wiki_id = $change['wiki_id'];
    
            // 2. Check if the wiki belongs to a user with this customer_id
            $stmt = $this->db->prepare("
                SELECT u.customer_id
                FROM wiki w
                JOIN user u ON w.user_id = u.id
                WHERE w.id = :wiki_id
            ");
            $stmt->execute([':wiki_id' => $wiki_id]);
            $owner = $stmt->fetch();
    
            if (!$owner || $owner['customer_id'] != $customer_id) {
                return json_encode([
                    "status" => "error",
                    "message" => "Unauthorized: Wiki does not belong to this customer."
                ]);
            }
    
            // 3. Delete all wiki_changes newer than this one
            $stmt = $this->db->prepare("
                DELETE FROM wiki_changes
                WHERE wiki_id = :wiki_id
                  AND time > :time
            ");
            $stmt->execute([
                ':wiki_id' => $wiki_id,
                ':time' => $change['time']
            ]);
    
            return json_encode([
                "status" => "success",
                "message" => "Restored successfully (newer changes removed)."
            ]);
    
        } catch (PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
    }

    public function deleteWiki($token, $wiki_id){
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
        $customer_id=$tokeninfo["customer_id"];
        try {
            // 1. Get the customer_id of the user who created this wiki
            $stmt = $this->db->prepare("
                SELECT u.customer_id
                FROM wiki w
                JOIN user u ON w.user_id = u.id
                WHERE w.id = :wiki_id
            ");
            $stmt->execute([':wiki_id' => $wiki_id]);
            $organisationOwner = $stmt->fetch();

            // 2. Does the wiki exist?
            if (!$organisationOwner) {
                return json_encode([
                    "status" => "error",
                    "message" => "Wiki not found."
                ]);
            }

            // 3. Check if the requesting customer matches creator's customer_id
            if ($organisationOwner['customer_id'] != $customer_id) {
                return json_encode([
                    "status" => "error",
                    "message" => "Unauthorized: You do not have permission to delete this wiki."
                ]);
            }

            // 4. Authorized → delete the wiki
            $stmt = $this->db->prepare("
                DELETE FROM wiki
                WHERE id = :wiki_id
            ");
            $stmt->execute([':wiki_id' => $wiki_id]);

            return json_encode([
                "status" => "success",
                "message" => "Wiki deleted successfully."
            ]);

        } catch (PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }  
    }

    public function examplteFunction($param, $param2, $param3){
        try {
            // all stmts here and logic


        } catch (PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }  
    }




}

?>