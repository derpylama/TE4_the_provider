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
            $checkStmt = $this->conn->prepare("SELECT id FROM wiki WHERE user_id = :user_id");
            $checkStmt->execute([':user_id' => $user_id]);
            $existingWiki = $checkStmt->fetch();
    
            if ($existingWiki) {
                // User already has a wiki
                return json_encode([
                    "status" => "error",
                    "message" => "User already has a wiki."
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
    public function editWiki($title, $content, $user_id){
        try {
            // 


        } catch (PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }  
    }
    





}

?>