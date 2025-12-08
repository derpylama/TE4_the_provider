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

    public function createWiki($title, $description, $token, $general){
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
            
            //check if user has a wiki
            $checkStmt = $this->conn->prepare("SELECT 1 FROM wiki WHERE user_id = :user_id LIMIT 1");
            $checkStmt->execute([':user_id' => $user_id]);

            if ($checkStmt->fetchColumn()) {
                $message="User already has a wiki";
                $this->error($message, [], 409);
            } 

            //create wiki if not exist
            $stmt = $this->conn->prepare("INSERT INTO wiki 
                (user_id, title, description, general)
                VALUES (:user_id, :title, :description, :general)"
            );
            $stmt->execute([
                ':user_id' => $user_id,
                ':title' => $title,
                ':description' => $description,
                ':general' => json_encode($general)
            ]);
            $message="Wiki successfully created.";
                $this->success($message, [], 200);
    
        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
        }  
    }
    public function createWikiArticle($title, $content, $token, $general){
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

            // 1. Check if user has a wiki
            $checkStmt = $this->conn->prepare("
                SELECT id FROM wiki WHERE user_id = :user_id LIMIT 1
            ");
            $checkStmt->execute([':user_id' => $user_id]);
            $wiki_id = $checkStmt->fetchColumn();
    
            if (!$wiki_id) {
                $this->error("User does not have a wiki", [], 409);
            }
    
            // 2. Check if title already exists for this wiki
            $checkTitle = $this->conn->prepare("SELECT 1
                FROM wiki_change wc
                JOIN wiki_article wa ON wa.id = wc.wiki_article_id
                WHERE wa.wiki_id = :wiki_id
                  AND wc.title = :title
                LIMIT 1
            ");
            $checkTitle->execute([
                ':wiki_id' => $wiki_id,
                ':title'   => $title
            ]);
    
            if ($checkTitle->fetchColumn()) {
                $this->error("A page with the title '$title' already exists for this wiki.", [], 400);
            }
    
            // Create wiki_article
            $stmtArticle = $this->conn->prepare("INSERT INTO wiki_article 
                (wiki_id)
                VALUES (:wiki_id)
            ");
            $stmtArticle->execute([':wiki_id' => $wiki_id]);
    
            // Fetch newly created article_id
            $articleQuery = $this->conn->prepare("SELECT id 
                FROM wiki_article
                WHERE wiki_id = :wiki_id
                ORDER BY id DESC
                LIMIT 1
            ");
            $articleQuery->execute([':wiki_id' => $wiki_id]);
            $article_id = $articleQuery->fetchColumn();
    
            if (!$article_id) {
                $this->error("Failed to create wiki article", [], 500);
            }
    
            // Insert first revision into wiki_change
            $stmtChange = $this->conn->prepare("INSERT INTO wiki_change 
                (title, content, user_id, wiki_article_id, general)
                VALUES (:title, :content, :user_id, :article_id, :general)
            ");
    
            $stmtChange->execute([
                ':title'       => $title,
                ':content'     => $content,
                ':user_id'     => $user_id,
                ':article_id'  => $article_id,
                ':general'     => $general
            ]);
    
            // Success response
            $this->success("Article created successfully", [
                "wiki_id"     => $wiki_id,
                "article_id"  => $article_id,
                "title"       => $title
            ]);
    
        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
        }  
    }

    public function editWiki($newContent, $wiki_article_id, $token, $newGeneral, $newTitle){ //cant change title for now
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
        $customer_id = $tokeninfo["customer_id"]; //customer id of the editor


        try {

            //check if wiki_article_id exists and get wiki_id
            $wikiIdStmt = $this->conn->prepare("SELECT wiki_id FROM wiki_article WHERE id = :wiki_article_id");
            $wikiIdStmt->execute([':wiki_article_id' => $wiki_article_id]);
            $wiki_id = $wikiIdStmt->fetchColumn();
            if (!$wiki_id) { //maybe a prbolem
                $message="Wiki article not found.";
                $this->error($message, [], 404); 
            }


            // Check if the wiki to be edited belongs to a user with the same customer_id
            $ownerCheckStmt = $this->conn->prepare("SELECT u.customer_id
                FROM wiki w
                JOIN user u ON w.user_id = u.id
                WHERE w.id = :wiki_id"
            );
            $ownerCheckStmt->execute([':wiki_id' => $wiki_id]);
            $ownerCustomerId = $ownerCheckStmt->fetchColumn();
            if ($ownerCustomerId !== $customer_id) { //maybe change to a 404
                $message="Wiki article not found.";
                $this->error($message, [], 404); 
            }

            //get old wiki changes from wiki_change table
            $oldChangesStmt = $this->conn->prepare("SELECT * FROM wiki_change WHERE wiki_article_id = :wiki_article_id ORDER BY time DESC LIMIT 1");
            $oldChangesStmt->execute([':wiki_article_id' => $wiki_article_id]);
            $oldChanges = $oldChangesStmt->fetch(PDO::FETCH_ASSOC);
            if (!$oldChanges) {
                $message="No previous changes found for this wiki article.";
                $this->error($message, [], 404); 
            }

            //see which values needs to be inserter ex if new title is provided then  change or not
            //if changed update it to the new one  if not keep the old one
            if (empty($newContent)) {
                $newContent = $oldChanges['content'];
            }
            if (!empty($newGeneral)) {
                $newGeneral = json_encode($newGeneral);
            } else {
                $newGeneral = $oldChanges['general']; //already json encoded i think
            }
            if (empty($newTitle)) {
                $newTitle = $oldChanges['title'];
            }


            // insert new changes

            $stmt = $this->conn->prepare("INSERT INTO wiki_change
                (wiki_article_id, content, user_id, general, title)
                VALUES (:wiki_article_id, :content, :user_id, :general, :title)"
            );
            $stmt->execute([
                ':wiki_article_id' => $wiki_article_id, //get from old
                ':content' => $newContent, //new or get from old
                ':user_id' => $user_id, //new / current editor
                ':general' => $newGeneral,  //new or get from old
                ':title'   => $newTitle
            ]); 

            //check that new one was sucessfully inserted dont use lastinserted id
            $checkStmt = $this->conn->prepare("SELECT id FROM wiki_change WHERE wiki_article_id = :wiki_article_id AND content = :content AND title = :title AND general = :general AND user_id = :user_id ORDER BY time DESC LIMIT 1");
            $checkStmt->execute([
                ':wiki_article_id' => $wiki_article_id, //get from old
                ':content' => $newContent, //new or get from old
                ':user_id' => $user_id, //new / current editor
                ':general' => $newGeneral,  //new or get from old
                ':title'   => $newTitle
            ]);
            $newChangeId = $checkStmt->fetchColumn();

            if (!$newChangeId) {
                $message="Failed to insert new wiki changes.";
                $this->error($message, [], 500);
            }

            //move all old changes with the same article id to "backup_wiki_change" table then send success
            $moveStmt = $this->conn->prepare("
            INSERT INTO backup_wiki_change (title, content, user_id, wiki_article_id, creation_date, general)
            SELECT title, content, user_id, wiki_article_id, creation_date, general
            FROM wiki_change
            WHERE wiki_article_id = :article_id
              AND id != :new_id
            ");
            $moveStmt->execute([
                ':article_id' => $wiki_article_id,
                ':new_id' => $newChangeId
            ]);

            //delete all old changes from wiki_change table
            $deleteStmt = $this->conn->prepare("
                DELETE FROM wiki_change
                WHERE wiki_article_id = :article_id
                AND id != :new_id
            ");
            $deleteStmt->execute([
                ':article_id' => $wiki_article_id,
                ':new_id' => $newChangeId
            ]);

            //success
            $responsData=[];
            $message="Wiki article edited successfully.";
            $this->success($message, $responsData, 200);

        } catch (PDOException $e) {
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 500);
        }  
    }

    public function getWiki($token, $query = '', array $searchFilter) { 
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

            // No search query -> return all in organisation
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

    public function deleteWikiArticle($token, $wiki_article_id){
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
        $usertype=$tokeninfo["type"];
        $user_id=$tokeninfo["userId"];
        try {
            // 1. Get the customer_id of the user who created this wiki via wiki_article_id
            $stmt = $this->conn->prepare(
                "SELECT u.customer_id
                FROM wiki_article wa
                JOIN wiki w ON wa.wiki_id = w.id
                JOIN user u ON w.user_id = u.id
                WHERE wa.id = :wiki_article_id
                LIMIT 1
            ");
            $stmt->execute([':wiki_article_id' => $wiki_article_id]);
            $organisationOwner = $stmt->fetchColumn();

            //does wiki exist
            if (!$organisationOwner) {
                $this->error("article not found.", [], 404);
            }

            // check that customer ids match        //maybe change ot not found since its costumer id
            if ($organisationOwner != $customer_id) { 
                $message="Article not found";
                $this->error($message, [], 404); 
            }

            //check if user is admin
            if ($usertype == 'admin') {
                // Admins can delete any wiki article
                $stmt = $this->conn->prepare("DELETE FROM wiki_article WHERE id = :wiki_article_id");
                $stmt->execute([':wiki_article_id' => $wiki_article_id]);

                $responsData=[];
                $message="Wiki article deleted successfully by admin.";
                $this->success($message, $responsData, 200);
            }

            // delete the wiki article if not admin but user matches

            //check user owns the wiki article
            $stmt = $this->conn->prepare(
                "SELECT 1
                FROM wiki_article wa
                JOIN wiki w ON wa.wiki_id = w.id
                JOIN user u ON w.user_id = u.id
                WHERE wa.id = :wiki_article_id
                  AND u.id = :user_id
                LIMIT 1
                ");
            $stmt->execute([
                ':wiki_article_id' => $wiki_article_id,
                ':user_id' => $user_id
            ]);
            if (!$stmt->fetchColumn()) {
                $message="Unauthorized: You do not have permission to delete this wiki article.";
                $this->error($message, [], 403); 
            }

            //delete wiki article if user owns the wiki
            $stmt = $this->conn->prepare("DELETE FROM wiki_article WHERE id = :wiki_article_id");
            $stmt->execute([':wiki_article_id' => $wiki_article_id]);
            $responsData=[];
            $message="Wiki article deleted successfully.";
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