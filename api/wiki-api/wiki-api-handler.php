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
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
        }  
    }

    public function createWiki($title, $content, $token, $general){
        //              needed everywhere in all endpoint functions
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
        $user_id=$tokeninfo["userId"];
        $customer_id = $tokeninfo["customer_id"];

        try {
            
            // Check if user already has a wiki
            $checkStmt = $this->conn->prepare("SELECT 1 FROM wiki WHERE user_id = :user_id LIMIT 1");
            $checkStmt->execute([':user_id' => $user_id]);

            if ($checkStmt->fetchColumn()) {
                $message="User already has a wiki";
                $this->error($message, [], 409);
            }

            $mainWikiParams = [
                ':user_id' => $user_id,
                ':title' => $title
            ];

            if ($general != "") {
                $mainWikiSql = "INSERT INTO wiki (user_id, title, general)
                VALUES (:user_id, :title, :general)";

                $mainWikiParams[":general"] = json_encode($general);
            }
            else {
                $mainWikiSql = "INSERT INTO wiki (user_id, title)
                VALUES (:user_id, :title)";
            }
    
            // 1. Insert main wiki row
            $stmt = $this->conn->prepare($mainWikiSql);
            $stmt->execute($mainWikiParams);
    

            $stmt = $this->conn->prepare("SELECT id FROM wiki WHERE user_id = :userId"); 
            $stmt->execute(["userId" => $user_id]);
            $wiki_id = $stmt->fetchAll();

            // Get inserted wiki ID
            //$wiki_id = $this->conn->lastInsertId();
    
            // 2. Insert the first wiki change (content)
            $stmt2 = $this->conn->prepare("INSERT INTO wiki_changes (wiki_id, content, user_id) VALUES (:wiki_id, :content, :user_id)");
            $stmt2->execute([
                ':wiki_id' => $wiki_id[0]['id'],
                ':content' => $content,
                ':user_id' => $user_id
            ]);
    
            // 3. Return success JSON
            $responsData=["id" => $wiki_id[0]['id']];
            $message="Wiki created successfully.";
            $this->success($message, $responsData, 200);
    
        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
        }  
    }
    public function editWiki($newContent, $wiki_id, $token, $newGeneral, $newTitle){ //cant change title for now
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
        $user_id=$tokeninfo["userId"];


        try {
            // Check if wiki exists
            $checkStmt = $this->conn->prepare("SELECT 1 FROM wiki WHERE id = :wiki_id LIMIT 1");
            $checkStmt->execute([':wiki_id' => $wiki_id]);
            
            if (!$checkStmt->fetchColumn()) {
                $message="Wiki does not exist";
                $this->error($message, [], 404); 
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

           
            // 2. Update general and title if provided
            $updateParams = [];
            $updateParts = [];
            
            if ($newGeneral != "") {
                $updateParts[] = "general = :general";
                $updateParams[':general'] = json_encode($newGeneral);
            }
            
            if ($newTitle != "") {
                $updateParts[] = "title = :title";
                $updateParams[':title'] = $newTitle;
            }
            
            if (!empty($updateParts)) {
                $updateStmt = "UPDATE wiki SET " . implode(', ', $updateParts) . " WHERE id = :wiki_id";
                $updateParams[':wiki_id'] = $wiki_id;

                $updateQuery = $this->conn->prepare($updateStmt);
                $updateQuery->execute($updateParams);
            }

            $responsData=[];
            $message="Wiki edited successfully.";
            $this->success($message, $responsData, 200);


        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
        }  
    }

    public function getWiki($token, $query = '', array $searchFilter) { //allows searching with a query ex hello
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

                $allowedFilters = ['title', 'content', 'general'];
                $params = [':customer_id' => $customer_id];

                $queryStmt = "SELECT w.*, wc.content
                    FROM wiki w
                    JOIN user u ON w.user_id = u.id
                    LEFT JOIN wiki_changes wc 
                        ON wc.id = (
                            SELECT id FROM wiki_changes 
                            WHERE wiki_id = w.id 
                            ORDER BY time DESC 
                             LIMIT 1
                         )
                    WHERE u.customer_id = :customer_id";

                if (!empty(array_diff($searchFilter, $allowedFilters))) {
                    $this->error("Invalid search filter", [], 400); 
                }

                if (!empty($query)) {
                    $conditions = [];

                    foreach ($searchFilter as $index => $filter) {
                        $paramName = ":search$index";
                        if ($filter === 'title') {
                            $conditions[] = "w.title LIKE $paramName";
                        } elseif ($filter === 'content') {
                            $conditions[] = "wc.content LIKE $paramName";
                        } elseif ($filter === 'general') {
                            $conditions[] = "w.general LIKE $paramName";
                        }

                        $params[$paramName] = "%" . $query . "%"; // bind separately
                    }

                    $queryStmt .= " AND (" . implode(" OR ", $conditions) . ")";
                }

                $stmt = $this->conn->prepare($queryStmt);
                
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
            }
    
            $wikis = $stmt->fetchAll();
    
            $responsData=["wikis" => $wikis];
            $message="Wikis retrieved successfully.";
            $this->success($message, $responsData, 200);
            
        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
        }
    }

    public function getAllVersions($wiki_id, $token){
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

        try {

            $stmt = $this->conn->prepare("
                SELECT *
                FROM wiki_changes
                WHERE wiki_id = :wiki_id
                ORDER BY time DESC
            ");

            $stmt->execute([':wiki_id' => $wiki_id]);

            $versions = $stmt->fetchAll();

            $responsData=["versions" => $versions];
            $message="successfully retrieved all versions";
            $this->success($message, $responsData, 200);


        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
        }  
    }

    public function restoreWiki($wikiChanges_id, $token) {
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
        $customer_id=$tokeninfo["customer_id"];
        try {
            // 1. Get wiki_id and timestamp of this wiki change
            $stmt = $this->conn->prepare("
                SELECT wiki_id, time
                FROM wiki_changes
                WHERE id = :id
            ");
            $stmt->execute([':id' => $wikiChanges_id]);
            $change = $stmt->fetch();
    
            if (!$change) {
                $responsData=[];
                $message="Wiki change not found.";
                $this->error($message, $responsData, 404);
            }
    
            $wiki_id = $change['wiki_id'];
    
            // 2. Check if the wiki belongs to a user with this customer_id
            $stmt = $this->conn->prepare("
                SELECT u.customer_id
                FROM wiki w
                JOIN user u ON w.user_id = u.id
                WHERE w.id = :wiki_id
            ");
            $stmt->execute([':wiki_id' => $wiki_id]);
            $owner = $stmt->fetch();
    
            if (!$owner || $owner['customer_id'] != $customer_id) {
                $message="Unauthorized: Wiki does not belong to this customer.";
                $this->error($message, [], 403); 
            }
    
            // 3. Delete all wiki_changes newer than this one
            $stmt = $this->conn->prepare("
                DELETE FROM wiki_changes
                WHERE wiki_id = :wiki_id
                  AND time > :time
            ");
            $stmt->execute([
                ':wiki_id' => $wiki_id,
                ':time' => $change['time']
            ]);
    
            $responsData=[];
            $message="Restored successfully (newer changes removed).";
            $this->success($message, $responsData, 200);
    
        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
        }
    }

    public function deleteWiki($token, $wiki_id){
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
        $customer_id=$tokeninfo["customer_id"];
        try {
            // 1. Get the customer_id of the user who created this wiki
            $stmt = $this->conn->prepare("
                SELECT u.customer_id
                FROM wiki w
                JOIN user u ON w.user_id = u.id
                WHERE w.id = :wiki_id
            ");
            $stmt->execute([':wiki_id' => $wiki_id]);
            $organisationOwner = $stmt->fetch();

            // 2. Does the wiki exist?
            if (!$organisationOwner) {
                $message="Wiki not found";
                $this->error($message, [], 404); 
            }

            // 3. Check if the requesting customer matches creator's customer_id
            if ($organisationOwner['customer_id'] != $customer_id) {
                $message="Unauthorized: You do not have permission to delete this wiki.";
                $this->error($message, [], 403); 
            }

            // 4. Authorized → delete the wiki
            $stmt = $this->conn->prepare("
                DELETE FROM wiki
                WHERE id = :wiki_id
            ");
            $stmt->execute([':wiki_id' => $wiki_id]);

            $responsData=[];
            $message="Wiki deleted successfully.";
            $this->success($message, $responsData, 200);

        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
        }  
    }

    public function examplteFunction($param, $param2, $param3){
        try {
            // all stmts here and logic


        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
        }  
    }




}

?>