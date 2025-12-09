<?php

require_once('../api-handler.php');
class BlogApiHandler extends BaseApiHandler{

    protected function checkServiceAndToken($token, $service="blog"){
        return parent::checkServiceAndToken($token, $service);
    }
    public function createBlog(string $content, $token, string $title, $generalData) {
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
            $this->conn->beginTransaction();

            // Check if the user has a blog
            $checkStmt = $this->conn->prepare("SELECT id FROM blog WHERE user_id = :user_id");
            $checkStmt -> execute([":user_id" => $user_id]);
            $blogExists = $checkStmt->fetch();


            if ($blogExists) { 
                $message="user already has a blog";
                $this->error($message, [], 400);   
            }

            $sqlParts = ["description", "title", "user_id"];
            $placeholders = [":description", ":title", ":user_id"];
            $insertParams = [":description" => $content, ":title" => $title, ":user_id" => $user_id];

            if ($generalData != "") {
                $sqlParts[] = "general";
                $placeholders[] = ":general";
                $insertParams[":general"] = json_encode($generalData);
            }
            
            $stmt = $this->conn->prepare("INSERT INTO blog (" . implode(", ", $sqlParts) . ") 
                VALUES (" . implode(", ", $placeholders) . ")");
            $stmt->execute($insertParams);
    
            $blogId = $this->conn->lastInsertId();
    
            $responsData=["id" => $blogId];
            $message="blog created";
            $this->success($message, $responsData, 200);
        }
        catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }

    public function getBlog ($token, $blogId = "", string $searchQuery, array $searchFilter, int $amount, int $offset) {
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

            $allowedFilters = ['title', 'content', 'general'];
            if ($searchQuery !== "") {

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
                        $conditions[] = "blog.$filter LIKE $paramName";
                        $params[$paramName] = "%" . $searchQuery . "%";  // bind separately
                    }
                    $query .= " AND (" . implode(" OR ", $conditions) . ")";
                } else {
                    // no filters → search all allowed columns
                    $columns = ['title', 'content', 'general'];
                    $conditions = [];
                    foreach ($columns as $index => $col) {
                        $paramName = ":searchQuery$index";
                        $conditions[] = "blog.$col LIKE $paramName";
                        $params[$paramName] = "%" . $searchQuery . "%";
                    }
                    $query .= " AND (" . implode(" OR ", $conditions) . ")";
                }
            
            }

            $amount = (int)$amount;
            $offset = (int)$offset;

            $query .= " LIMIT $amount OFFSET $offset";

            $stmt = $this->conn->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
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

    public function getBlogPost ($token, $blogPostId = 0, $blogOwnerId = 0, string $searchQuery, array $searchFilter, int $amount, int $offset) {
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
            $this->conn->beginTransaction();

            $params = [":customerId" => $customerId];
            $query = "SELECT blog_post.*, blog.user_id, user.customer_id FROM blog_post 
                INNER JOIN blog ON blog.id = blog_post.blog_id 
                INNER JOIN user ON user.id = blog.user_id 
                WHERE user.customer_id = :customerId";

            if ($blogPostId != 0) {
                $query .= " AND blog_post.id = :blogPostId";
                $params[":blogPostId"] = $blogPostId;
            }

            if ($blogOwnerId != 0) {
                $query .= " AND blog.user_id = :blogOwnerId";
                $params[":blogOwnerId"] = $blogOwnerId;
            }

            $allowedFilters = ['title', 'content', 'general'];
            if ($searchQuery !== "") {

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
                        $conditions[] = "blog_post.$filter LIKE $paramName";
                        $params[$paramName] = "%" . $searchQuery . "%";  // bind separately
                    }
                    $query .= " AND (" . implode(" OR ", $conditions) . ")";
                } else {
                    // no filters → search all allowed columns
                    $columns = ['title', 'content', 'general'];
                    $conditions = [];
                    foreach ($columns as $index => $col) {
                        $paramName = ":searchQuery$index";
                        $conditions[] = "blog_post.$col LIKE $paramName";
                        $params[$paramName] = "%" . $searchQuery . "%";
                    }
                    $query .= " AND (" . implode(" OR ", $conditions) . ")";
                }
            
            }

            // Additional search and filtering logic can be added here

            $amount = (int)$amount;
            $offset = (int)$offset;

            $query .= " LIMIT $amount OFFSET $offset";

            $stmt = $this->conn->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();

            $responsData=$stmt->fetchAll();
            $message="Fetched blog posts";

            $this->success($message, $responsData, 200);
        }
        catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }

    public function editBlog (string $content, string $title, $token, int $editUserId=0, $generalData) {
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
            $this->conn->beginTransaction();

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
                    $fields[] = "description = :content";
                    $params[":content"] = $content;
                }

                if (!empty(trim($title))) {
                    $fields[] = "title = :title";
                    $params[":title"] = $title;
                }

                if (!empty($generalData)) {
                    $fields[] = "general = :general";
                    $params[":general"] = json_encode($generalData);
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
                $fields[] = "description = :content";
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

    public function editBlogPost ($content, $title, $token, $blogPostId, $generalData) {
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
        $userId=$tokeninfo["userId"];

        $blogExists = $this->conn->prepare("SELECT id FROM blog WHERE user_id = :user_id");
        $blogExists->execute(["user_id" => $userId]);

        $blogRow = $blogExists->fetch();

        if (!$blogRow) {
            $message="The user does not have a blog";
            $this->error($message, [], 400); 
        }

        try {
            $this->conn->beginTransaction();

            // Build update fields dynamically
            $fields = [];
            $params = [":blog_id" => $blogPostId];

            if (!empty(trim($content))) {
                $fields[] = "description = :content";
                $params[":content"] = $content;
            }

            if (!empty(trim($title))) {
                $fields[] = "title = :title";
                $params[":title"] = $title;
            }

            if (!empty($generalData)) {
                $fields[] = "general = :general";
                $params[":general"] = json_encode($generalData);
            }

            // No fields to update
            if (empty($fields)) {
                $message="Nothing to update. Provide at least title or content.";
                $this->error($message, [], 400); 
            }

            // Create SQL string
            $sql = "UPDATE blog_post SET " . implode(", ", $fields) . " WHERE id = :blog_id";

            $updateStmt = $this->conn->prepare($sql);

            if ($updateStmt->execute($params)) {
                $responsData=[];
                $message="Blog post updated successfully";
                $this->success($message, $responsData, 200);
            }

            $message="Failed to update blog post";
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
            $this->conn->beginTransaction();
            if ($userType === "admin") {

                // Check if the user has a blog that can be edited
                $blogExists = $this->conn->prepare("SELECT id FROM blog WHERE user_id = :user_id");
                $blogExists->execute([":user_id" => $userId]);

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

    public function createBlogPost (string $content, string $title, $token, $generalData) {
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
            $this->conn->beginTransaction();

            //check if the user has a blog to add the post to
            $checkStmt = $this->conn->prepare("SELECT id FROM blog WHERE user_id = :user_id");
            $checkStmt -> execute([":user_id" => $user_id]);
            $blogExists = $checkStmt->fetch();

            if (!$blogExists) { 
                $message="user does not have a blog to add posts to";
                $this->error($message, [], 404);   
            }
            
            $blogId = $blogExists['id'];


            $stmt = $this->conn->prepare("INSERT INTO blog_post (content, title, blog_id, general) 
                VALUES (:content, :title, :user_id, :general)");
            $stmt->execute([
                ":content" => $content,
                ":title" => $title,
                ":user_id" => $blogId,
                ":general" => json_encode($generalData)
            ]);
    
            $blogPostId = $this->conn->lastInsertId();
    
            $responsData=["id" => $blogPostId];
            $message="blog post created";
            $this->success($message, $responsData, 200);
        }
        catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }

    }

    public function deleteBlogPost ($blogPostId, $token) {
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
        $userId=$tokeninfo["userId"];

        $blogExists = $this->conn->prepare("SELECT id FROM blog WHERE user_id = :user_id");
        $blogExists->execute(["user_id" => $userId]);

        $blogRow = $blogExists->fetch();

        if (!$blogRow) {
            $message="The user does not have a blog";
            $this->error($message, [], 400); 
        }

        //check if the user has a blog post to delete
        $blogPostExists = $this->conn->prepare("SELECT id FROM blog_post WHERE id = :blog_post_id AND blog_id = :blog_id");
        $blogPostExists->execute([
            ":blog_post_id" => $blogPostId,
            ":blog_id" => $blogRow['id']
        ]);
        $blogPostRow = $blogPostExists->fetch();

        if (!$blogPostRow) {
            $message="The blog post does not exist or does not belong to the user's blog";
            $this->error($message, [], 400); 
        }

        //check if the user is admin and allowed to delete another users the blog post
        if ($tokeninfo['type'] === 'admin') {
            try {
                $this->conn->beginTransaction();

                // Get the customer ID of the user being edited
                $check = $this->conn->prepare("
                    SELECT customer_id 
                    FROM user 
                    WHERE id = :userId
                ");
                $check->execute([":userId" => $userId]);
            
                $userData = $check->fetch();
            
                // If user doesn't exist or belongs to another company
                if (!$userData || $userData["customer_id"] != $tokeninfo["customer_id"]) {
                    $message="Admin cannot delete a blog post from a different company";
                    $this->error($message, [], 400); 
                }   
    
                $deleteStmt = $this->conn->prepare("DELETE FROM blog_post WHERE id = :blogPostId");
                if ($deleteStmt->execute([":blogPostId" => $blogPostId])) {
                    $responsData=[];
                    $message="Blog post deleted successfully";
                    $this->success($message, $responsData, 200);
                }
    
                $message="Failed to delete blog post";
                $this->error($message, [], 400);
            }
            catch (PDOException $e) {
                $message="Database error: " . $e->getMessage();
                $this->error($message, [], 400);
            }
        }
        else{
            try {
                $this->conn->beginTransaction();

                $deleteStmt = $this->conn->prepare("DELETE FROM blog_post WHERE id = :blogPostId");
    
                if ($deleteStmt->execute([":blogPostId" => $blogPostId])) {
                    $responsData=[];
                    $message="Blog post deleted successfully";
                    $this->success($message, $responsData, 200);
                }
    
                $message="Failed to delete blog post";
                $this->error($message, [], 400); 
            }
            catch (PDOException $e) {
                $message="Database error: " . $e->getMessage();
                $this->error($message, [], 400);
            }
        }




    }
}

?>