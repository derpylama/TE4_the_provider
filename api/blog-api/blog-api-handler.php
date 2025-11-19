<?php

require_once('../api-handler.php');
class BlogApiHandler extends BaseApiHandler{

    public function getUsers() {//example method
        $stmt = $this->conn->query("SELECT * FROM users");
        return $stmt->fetchAll();
    }

    public function createBlog(string $content, string $user_id) {
        
        
    }
}

?>