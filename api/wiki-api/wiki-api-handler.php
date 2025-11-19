<?php

require_once('../api-handler.php');
class WikiApiHandler extends BaseApiHandler{

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

    public function createWiki($title, $content, $user_id){
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
    public function editWiki($newContent, $wiki_id, $user_id){ //cant change title for now
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

    public function getAllWiki($customer_id){
        try {
            // all stmts here and logic
            $stmt = $this->conn->prepare("
                SELECT w.*
                FROM wiki w
                JOIN users u ON w.user_id = u.id
                WHERE u.customer_id = :customer_id
            ");
            $stmt->execute([
                ':customer_id' => $customer_id
            ]);

            $wikis = $stmt->fetchAll();

            return json_encode([
                "status" => "success",
                "message" => "Wikis retrieved successfully.",
                "data" => $wikis
            ]);

        } catch (PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }  
    }

    public function getAllVersions($wiki_id){
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




}

?>