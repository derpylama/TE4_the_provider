<?php

require_once('../api-handler.php');
class BlogApiHandler extends BaseApiHandler{

    public function getUsers() {//example method
        $stmt = $this->conn->query("SELECT * FROM users");
        return $stmt->fetchAll();
    }

    public function createBlog(string $content, int $user_id, string $title) {

        try {
            // Check if the user has a blog
            $checkStmt = $this->conn->prepare("SELECT id FROM blog WHERE user_id = :user_id");
            $checkStmt -> execute([":user_id" => $user_id]);
            $blogExists = $checkStmt->fetch();


            if ($blogExists) {
                return json_encode([
                    "status" => "error",
                    "message" => "user already has a blog"
                ]);    
            }

            $stmt = $this->conn->prepare("INSERT INTO `blog`(`content`, `title`, `user_id`, `latest_update`) VALUES (:content, :title, :user_id, NOW())");
            $stmt->execute([
                ":content" => $content,
                ":title" => $title,
                ":user_id" => $user_id
            ]);
    
            $blogId = $this->conn->lastInsertId();
    
            return json_encode(["status" => "success", "message" => "blog created", "blog_id" => $blogId]);
        }
        catch (PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
    }
}

?>