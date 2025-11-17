<?php

require_once('../api-handler.php');
class WikiApiHandler extends BaseApiHandler{

    public function getUsers() {//example method
        $stmt = $this->conn->query("SELECT * FROM users");
        return $stmt->fetchAll();
    }
    public function createWiki($title, $content, $author_id) {
        return json_encode([
            "status" => "success", 
            "message" => "Wiki currently disabled.",
            "title" => $title,
            "content" => $content,
            "author_id" => $author_id
        ]); 
/* 
        $stmt = $this->conn->prepare("INSERT INTO wikis (title, content, author_id) VALUES (:title, :content, :author_id)");
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':author_id', $author_id);
        if ($stmt->execute()) {
            return ['status' => 'success', 'wiki_id' => $this->conn->lastInsertId()];
        } else {
            return ['status' => 'error', 'message' => 'Failed to create wiki'];
        }
         */
    }
}

?>