<?php

require_once('../api-handler.php');
class BlogApiHandler extends BaseApiHandler{


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

    public function getBlog (int $customerId, $blogId = "") {
        try {
            if ($blogId != "") {
                $stmt = $this->conn->prepare("SELECT blog.*, user.* FROM blog INNER JOIN user ON user.id = blog.user_id WHERE user.customer_id = :customerId AND blog.id = :blogId");
                $stmt->execute([":customerId" => $customerId, ":blogId" => $blogId]);

                return json_encode($stmt->fetchAll());
            }
            else {
                $stmt = $this->conn->prepare("SELECT blog.*, user.id, user.customer_id FROM blog INNER JOIN user ON user.id = blog.user_id WHERE user.customer_id = :customerId");
                $stmt->execute([":customerId" => $customerId]);
                
                return json_encode($stmt->fetchAll());
            }
        }
        catch (PDOException $e) {
            return json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }

    }

    public function editBlog (string $content, string $title, int $customerId, int $userId, string $userType = "") {

        // Check if the user has a blog that can be edited
        $blogExists = $this->conn->prepare("SELECT id FROM blog WHERE user_id = :user_id");
        $blogExists->execute(["user_id" => $userId]);

        $blogRow = $blogExists->fetch();

        if (!$blogRow) {
            return json_encode([
                "status" => "error",
                "message" => "The user does not have a blog"
            ]);
        }
        
        if ($userType === "admin") {

            // Get the customer ID of the user being edited
            $check = $this->conn->prepare("
                SELECT customer_id 
                FROM user 
                WHERE id = :userId
            ");
            $check->execute([":userId" => $userId]);
        
            $userData = $check->fetch();
        
            // If user doesn't exist or belongs to another company
            if (!$userData || $userData["customer_id"] != $customerId) {
                return json_encode([
                    "status" => "error",
                    "message" => "Admin cannot edit a user from a different company"
                ]);
            }   

            // Build update fields dynamically
            $fields = [];
            $params = [":userId" => $userId];

            if (!empty(trim($content))) {
                $fields[] = "content = :content";
                $params[":content"] = $content;
            }

            if (!empty(trim($title))) {
                $fields[] = "title = :title";
                $params[":title"] = $title;
            }

            // No fields to update
            if (empty($fields)) {
                return json_encode([
                    "status" => "error",
                    "message" => "Nothing to update. Provide at least title or content."
                ]);
            }

            // Create SQL string
            $sql = "UPDATE blog SET " . implode(", ", $fields) . " WHERE user_id = :userId";

            $updateStmt = $this->conn->prepare($sql);

            if ($updateStmt->execute($params)) {
                return json_encode([
                    "status" => "success",
                    "message" => "Blog updated successfully"
                ]);
            }

            return json_encode([
                "status" => "error",
                "message" => "Failed to update blog"
            ]);

        }

        // Build update fields dynamically
        $fields = [];
        $params = [":userId" => $userId];

        if (!empty(trim($content))) {
            $fields[] = "content = :content";
            $params[":content"] = $content;
        }

        if (!empty(trim($title))) {
            $fields[] = "title = :title";
            $params[":title"] = $title;
        }

        // No fields to update
        if (empty($fields)) {
            return json_encode([
                "status" => "error",
                "message" => "Nothing to update. Provide at least title or content."
            ]);
        }

        // Create SQL string
        $sql = "UPDATE blog SET " . implode(", ", $fields) . " WHERE user_id = :userId";

        $updateStmt = $this->conn->prepare($sql);

        if ($updateStmt->execute($params)) {
            return json_encode([
                "status" => "success",
                "message" => "Blog updated successfully"
            ]);
        }

        return json_encode([
            "status" => "error",
            "message" => "Failed to update blog"
        ]);

    }

    public function deleteBlog(int $customerId, int $userId, string $userType) {
        if ($userType === "admin") {

            // Get the customer ID of the user being edited
            $check = $this->conn->prepare("
                SELECT customer_id 
                FROM user 
                WHERE id = :userId
            ");
            $check->execute([":userId" => $userId]);
        
            $userData = $check->fetch();
        
            // If user doesn't exist or belongs to another company
            if (!$userData || $userData["customer_id"] != $customerId) {
                return json_encode([
                    "status" => "error",
                    "message" => "Admin cannot delete a users blog that is part of a different company"
                ]);
            }   

            $deleteStmt = $this->conn->prepare("DELETE FROM blog WHERE user_id = :userId");

            if ($deleteStmt->execute([":userId" => $userId])) {
                return json_encode([
                    "status" => "success",
                    "message" => "Blog deleted successfully"
                ]);
            }

            return json_encode([
                "status" => "error",
                "message" => "Failed to delete blog"
            ]);

        }

        // Check if the user has a blog that can be edited
        $blogExists = $this->conn->prepare("SELECT id FROM blog WHERE user_id = :user_id");
        $blogExists->execute(["user_id" => $userId]);

        $blogRow = $blogExists->fetch();

        if (!$blogRow) {
            return json_encode([
                "status" => "error",
                "message" => "The user does not have a blog"
            ]);
        }

        $deleteStmt = $this->conn->prepare("DELETE FROM blog WHERE user_id = :userId");

            if ($deleteStmt->execute([":userId" => $userId])) {
                return json_encode([
                    "status" => "success",
                    "message" => "Blog deleted successfully"
                ]);
            }

            return json_encode([
                "status" => "error",
                "message" => "Failed to delete blog"
            ]);
        
    }
}

?>