<?php

require_once('../api-handler.php');
class BlogApiHandler extends BaseApiHandler{

    protected function checkServiceAndToken($token, $service="blog"){
        return parent::checkServiceAndToken($token, $service);
    }
    public function createBlog(string $content, $token, string $title, string $generalData) {
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
        $user_id=$tokeninfo["userId"];

        try {
            // Check if the user has a blog
            $checkStmt = $this->conn->prepare("SELECT id FROM blog WHERE user_id = :user_id");
            $checkStmt -> execute([":user_id" => $user_id]);
            $blogExists = $checkStmt->fetch();


            if ($blogExists) { 
                $message="user already has a blog";
                $this->error($message, [], 400);   
            }

            $sqlParts = ["content", "title", "user_id"];
            $placeholders = [":content", ":title", ":user_id"];
            $insertParams = [":content" => $content, ":title" => $title, ":user_id" => $user_id];

            if ($generalData != "") {
                $sqlParts[] = "general";
                $placeholders[] = ":general";
                $insertParams[":general"] = $generalData;
            }
            
            $stmt = $this->conn->prepare("INSERT INTO blog (" . implode(", ", $sqlParts) . ") 
                VALUES (" . implode(", ", $placeholders) . ")");
            $stmt->execute($insertParams);
    
            $blogId = $this->conn->lastInsertId();
    
            $responsData=["blog_id" => $blogId];
            $message="blog created";
            $this->success($message, $responsData, 200);
        }
        catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }

    public function getBlog ($token, $blogId = "", string $searchQuery, string $searchFilter, int $amount, int $offset) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {

            // return jsonencode([
            //     "status" => "error",
            //     "message" => "Insufficient permissions"
            // ]);
        }
        //---------------------------------------------------------------------
        $customerId=$tokeninfo["customer_id"];
        try {
            $params = [":customerId" => $customerId];
            $query = "SELECT blog.*, user.id as user_id, user.customer_id FROM blog INNER JOIN user ON user.id = blog.user_id WHERE user.customer_id = :customerId";

            if ($blogId != "") {
                $query .= " AND blog.id = :blogId";
                $params[":blogId"] = $blogId;
            }

            if ($searchQuery != "") {
                if ($searchFilter != "") {
                    $allowedFilters = ['title', 'content', 'general'];
                    if (!in_array($searchFilter, $allowedFilters)) {
                        $message="Invalid search filter";
                        $this->error($message, [], 400); 
                    }
                    $query .= " AND blog." . $searchFilter . " LIKE :searchQuery";
                } else {
                    $query .= " AND (blog.title LIKE :searchQuery OR blog.content LIKE :searchQuery OR blog.general LIKE :searchQuery)";
                }
                $params[":searchQuery"] = "%" . $searchQuery . "%";
            }

            $query .= " LIMIT :amount OFFSET :offset";

            $params[":amount"] = $amount;
            $params[":offset"] = $offset;

            $stmt = $this->conn->prepare($query);

            // Bind parameters explicitly for LIMIT and OFFSET
            foreach ($params as $key => $value) {
                if ($key === ":amount" || $key === ":offset") {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->execute();

            $responsData=$stmt->fetchAll();
            $message="Fetched blogs";

            $this->success($message, $responsData, 200);


        }
        catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }

    }

    public function editBlog (string $content, string $title, $token, int $editUserId=0, string $generalData) {
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
        $userType=$tokeninfo["type"];
        //check if user want to edit himself or another user
        if ($editUserId!=0){
            $userId=$editUserId; 
        }else {
            $userId=$tokeninfo["userId"];
        }
        try {
            // Check if the user has a blog that can be edited
            $blogExists = $this->conn->prepare("SELECT id FROM blog WHERE user_id = :user_id");
            $blogExists->execute(["user_id" => $userId]);

            $blogRow = $blogExists->fetch();

            if (!$blogRow) {
                $message="The user does not have a blog";
                $this->error($message, [], 400); 
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
                    $message="Admin cannot edit a user from a different company";
                    $this->error($message, [], 400); 
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

                if (!empty($generalData)) {
                    $fields[] = "general = :general";
                    $params[":general"] = $generalData;
                }

                // No fields to update
                if (empty($fields)) {
                    $message="Nothing to update. Provide at least title or content.";
                    $this->error($message, [], 400); 
                }

                // Create SQL string
                $sql = "UPDATE blog SET " . implode(", ", $fields) . " WHERE user_id = :userId";

                $updateStmt = $this->conn->prepare($sql);

                if ($updateStmt->execute($params)) {
                    $responsData=[];
                    $message="Blog updated successfully";
                    $this->success($message, $responsData, 200);
                }

                $message="Failed to update blog";
                $this->error($message, [], 400); 

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
                $message="Nothing to update. Provide at least title or content.";
                $this->error($message, [], 400); 
            }

            // Create SQL string
            $sql = "UPDATE blog SET " . implode(", ", $fields) . " WHERE user_id = :userId";

            $updateStmt = $this->conn->prepare($sql);

            if ($updateStmt->execute($params)) {
                $responsData=[];
                $message="Blog updated successfully";
                $this->success($message, $responsData, 200);
            }

            $message="Failed to update blog";
            $this->error($message, [], 400); 
        }
        catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }

    }

    public function deleteBlog($token, int $editUserId=0) {
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
        $userType=$tokeninfo["type"];
        if ($editUserId!=0){
            $userId=$editUserId; 
        }else {
            $userId=$tokeninfo["userId"];
        }
        try {
            if ($userType === "admin") {

                // Check if the user has a blog that can be edited
                $blogExists = $this->conn->prepare("SELECT id FROM blog WHERE user_id = :user_id");
                $blogExists->execute(["user_id" => $userId]);

                $blogRow = $blogExists->fetch();

                if (!$blogRow) {
                    $message="The user does not have a blog";
                    $this->error($message, [], 400);
                }

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
                    $message="Admin cannot delete a users blog that is part of a different company";
                    $this->error($message, [], 400); 
                }   

                $deleteStmt = $this->conn->prepare("DELETE FROM blog WHERE user_id = :userId");

                if ($deleteStmt->execute([":userId" => $userId])) {
                    $responsData=[];
                    $message="Blog deleted successfully";
                    $this->success($message, $responsData, 200);
                }

                $message="Failed to delete blog";
                $this->error($message, [], 400); 

            }

            // Check if the user has a blog that can be edited
            $blogExists = $this->conn->prepare("SELECT id FROM blog WHERE user_id = :user_id");
            $blogExists->execute(["user_id" => $userId]);

            $blogRow = $blogExists->fetch();

            if (!$blogRow) {
                $message="The user does not have a blog";
                $this->error($message, [], 400); 
            }

            $deleteStmt = $this->conn->prepare("DELETE FROM blog WHERE user_id = :userId");

            if ($deleteStmt->execute([":userId" => $userId])) {
                $responsData=[];
                $message="Blog deleted successfully";
                $this->success($message, $responsData, 200);
            }

            $message="Failed to delete blog";
            $this->error($message, [], 400); 
        }
        catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
        
    }
}

?>