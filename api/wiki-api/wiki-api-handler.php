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

    public function getAllWiki($token, array $searchQuery = [], int $amount = 20, int $offset = 0, string $orderDirection = "DESC") {
        // ---------------- Token Check ---------------------------------------
        $tokeninfo = $this->checkServiceAndToken($token); 
        if ($tokeninfo['status'] !== "success") {
            $this->error($tokeninfo["message"], [], 401);
        }

        // Check permissions
        if ($tokeninfo['type'] === 'user') {
            $this->error("Insufficient permissions", [], 403);
        }
        //---------------------------------------------------------------------
        $customerId = $tokeninfo["customer_id"];

        try {
            // Validate order direction
            $orderDirection = strtoupper($orderDirection);
            if (!in_array($orderDirection, ["ASC", "DESC"])) {
                $this->error("order_direction must be ASC or DESC", [], 400);
            }

            // Base query: only wikis for this customer
            $baseQuery = "FROM wiki w INNER JOIN user u ON u.id = w.user_id WHERE u.customer_id = :customerId";
            $params = [":customerId" => $customerId];

            // Search filters
            if (!empty($searchQuery)) {
                $allowedFilters = ["title", "description"];
                $conditions = [];

                foreach ($searchQuery as $key => $value) {
                    if (!in_array($key, $allowedFilters)) {
                        $this->error("Invalid search filter: $key", [], 400);
                    }
                    $paramName = ":$key";
                    $conditions[] = "w.$key LIKE $paramName";
                    $params[$paramName] = "%$value%";
                }

                if (!empty($conditions)) {
                    $baseQuery .= " AND (" . implode(" OR ", $conditions) . ")";
                }
            }

            // Get total count first
            $countStmt = $this->conn->prepare("SELECT COUNT(*) " . $baseQuery);
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();

            // Fetch paginated results
            $query = "SELECT w.id, w.title, w.description " . $baseQuery . " ORDER BY w.creation_date $orderDirection LIMIT :amount OFFSET :offset";
            $stmt = $this->conn->prepare($query);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(":amount", $amount, PDO::PARAM_INT);
            $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll();

            $this->success(
                "Fetched wikis",
                ["wikis" => $result, "total_count" => $totalCount, "offset" => $offset, "amount" => $amount],
                200
            );
        } catch (PDOException $e) {
            $this->error("Database error: " . $e->getMessage(), [], 500);
        }
    }

    public function getWikiArticle($token, int $wiki_article_id = 0, string $searchQuery = "", array $searchFilter = [], int $amount = 10, int $offset = 0 , string $orderDirection = "DESC" , int $wiki_id = 0) { 

        $tokeninfo = $this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $this->error($tokeninfo["message"], [], 401);
        }

        if ($tokeninfo['type'] == 'user') {
            $this->error("Insufficient permissions", [], 403);
        }

        $customerId = $tokeninfo["customer_id"];

        try {
            // Base query
            $baseQuery = "
                FROM wiki_change wc
                INNER JOIN wiki_article wa ON wa.id = wc.wiki_article_id
                INNER JOIN wiki w ON w.id = wa.wiki_id
                INNER JOIN user u ON u.id = w.user_id
                WHERE u.customer_id = :customerId
            ";
            $params = [":customerId" => $customerId];

            // Optional wiki filter
            if ($wiki_id !== 0) {
                $baseQuery .= " AND wa.wiki_id = :wiki_id";
                $params[":wiki_id"] = $wiki_id;
            }

            // Optional single article verification
            if ($wiki_id !== 0 && $wiki_article_id !== 0) {
                $checkQuery = "SELECT COUNT(*) FROM wiki_article WHERE id = :wiki_article_id AND wiki_id = :wiki_id";
                $checkStmt = $this->conn->prepare($checkQuery);
                $checkStmt->execute([":wiki_article_id" => $wiki_article_id, ":wiki_id" => $wiki_id]);
                if ($checkStmt->fetchColumn() == 0) {
                    $this->error("Wiki article does not belong to this wiki", [], 400);
                }
            }

            // Single article request
            if ($wiki_article_id !== 0) {
                $baseQuery .= " AND wc.wiki_article_id = :wiki_article_id";
                $params[":wiki_article_id"] = $wiki_article_id;

                $stmt = $this->conn->prepare("SELECT wc.*, wa.wiki_id, w.user_id, u.customer_id " . $baseQuery . " ORDER BY wc.creation_date DESC LIMIT 1");
                foreach ($params as $k => $v) $stmt->bindValue($k, $v);
                $stmt->execute();
                $result = $stmt->fetch();
                if (!$result) $this->error("Wiki article not found", [], 404);

                $this->success("Fetched a single wiki article", ["articles" => $result, "total_count" => 1], 200);
                return;
            }

            // Search
            if ($searchQuery !== "") {
                $allowedFilters = ["title", "content", "general"];
                if (!is_array($searchFilter)) $this->error("searchFilter must be an array", [], 400);
                if (!empty(array_diff($searchFilter, $allowedFilters))) $this->error("Invalid search filter", [], 400);

                $conditions = [];
                $filters = !empty($searchFilter) ? $searchFilter : $allowedFilters;

                foreach ($filters as $i => $filter) {
                    $p = ":search$i";
                    $conditions[] = "wc.$filter LIKE $p";
                    $params[$p] = "%$searchQuery%";
                }
                $baseQuery .= " AND (" . implode(" OR ", $conditions) . ")";
            }

            // Total count for pagination
            $countStmt = $this->conn->prepare("SELECT COUNT(*) " . $baseQuery);
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();

            // Paginated fetch
            $stmt = $this->conn->prepare("SELECT wc.*, wa.wiki_id, w.user_id, u.customer_id " . $baseQuery . " ORDER BY wc.creation_date $orderDirection LIMIT :amount OFFSET :offset");
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(":amount", $amount, PDO::PARAM_INT);
            $stmt->bindValue(":offset", $offset, PDO::PARAM_INT); //makes sure its an int.  sends it as a int always
            $stmt->execute();

            $result = $stmt->fetchAll();

            $this->success(
                "Fetched wiki articles",
                ["articles" => $result, "total_count" => $totalCount, "offset" => $offset, "amount" => $amount],
                200
            );

        } catch (PDOException $e) {
            $this->error("Database error: " . $e->getMessage(), [], 500);
        }
    }

    public function getAllVersions($wiki_article_id, $token) {
        // Token validation
        $tokeninfo = $this->checkServiceAndToken($token); 
        if ($tokeninfo['status'] !== "success") {
            $this->error($tokeninfo["message"], [], 401);
        }

        if ($tokeninfo['type'] === 'user') {
            $this->error("Insufficient permissions", [], 403);
        }

        $customer_id = $tokeninfo["customer_id"];

        try {
            // 1. Get the wiki_id and verify ownership
            $stmt = $this->conn->prepare("
                SELECT w.id AS wiki_id
                FROM wiki_article wa
                JOIN wiki w ON wa.wiki_id = w.id
                JOIN user u ON w.user_id = u.id
                WHERE wa.id = :wiki_article_id
                AND u.customer_id = :customer_id
                LIMIT 1
            ");
            $stmt->execute([
                ':wiki_article_id' => $wiki_article_id,
                ':customer_id' => $customer_id
            ]);
            $wiki = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$wiki) {
                $this->error("Wiki article not found or access denied", [], 404);
            }

            $wiki_id = $wiki['wiki_id'];

            // 2. Get the active version from wiki_change
            $stmtActive = $this->conn->prepare("
                SELECT *
                FROM wiki_change
                WHERE wiki_article_id = :wiki_article_id
                ORDER BY creation_date DESC
                LIMIT 1
            ");
            $stmtActive->execute([':wiki_article_id' => $wiki_article_id]);
            $activeVersion = $stmtActive->fetch();

            // 3. Get all previous versions from backup_wiki_change
            $stmtBackup = $this->conn->prepare("
                SELECT *
                FROM backup_wiki_change
                WHERE wiki_article_id = :wiki_article_id
                ORDER BY creation_date DESC
            ");
            $stmtBackup->execute([':wiki_article_id' => $wiki_article_id]);
            $oldVersions = $stmtBackup->fetchAll();

            $this->success("Fetched wiki article versions", [
                "active_version" => $activeVersion,
                "old_versions" => $oldVersions
            ], 200);

        } catch (PDOException $e) {
            $this->error("Database error: " . $e->getMessage(), [], 500);
        }
    }

    public function restoreWikiVersion($backup_wiki_change_id, $token) {
        // ---------------- Token check ----------------
        $tokeninfo = $this->checkServiceAndToken($token); 
        if ($tokeninfo['status'] !== "success") {
            $this->error($tokeninfo["message"], [], 401);
        }

        if ($tokeninfo['type'] === 'user') {
            $this->error("Insufficient permissions", [], 403);
        }

        $customer_id = $tokeninfo["customer_id"];

        try {
            // Start transaction
            $this->conn->beginTransaction(); //ensures all steps succed maybe add to edit wiki too

            // Get the backup version and validate ownership
            $stmt = $this->conn->prepare("
                SELECT bwc.*, wa.wiki_id, w.user_id
                FROM backup_wiki_change bwc
                INNER JOIN wiki_article wa ON bwc.wiki_article_id = wa.id
                INNER JOIN wiki w ON wa.wiki_id = w.id
                WHERE bwc.id = :backup_id
            ");
            $stmt->execute([':backup_id' => $backup_wiki_change_id]);
            $backup = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$backup) {
                $this->error("Backup version not found", [], 404);
            }

            // Check customer ownership
            if ($backup['user_id'] !== $customer_id) {
                $this->error("Access denied: cannot restore this wiki article", [], 403);
            }

            $wiki_article_id = $backup['wiki_article_id'];

            // Get current active version
            $stmtActive = $this->conn->prepare("
                SELECT *
                FROM wiki_change
                WHERE wiki_article_id = :wiki_article_id
                ORDER BY creation_date DESC
                LIMIT 1
            ");
            $stmtActive->execute([':wiki_article_id' => $wiki_article_id]);
            $active = $stmtActive->fetch(PDO::FETCH_ASSOC);

            if (!$active) {
                $this->error("No active version found for this wiki article", [], 404);
            }

            // Move current active to backup
            $stmtInsertBackup = $this->conn->prepare("
                INSERT INTO backup_wiki_change (title, content, user_id, wiki_article_id, creation_date, general)
                VALUES (:title, :content, :user_id, :wiki_article_id, :creation_date, :general)
            ");
            $stmtInsertBackup->execute([
                ':title' => $active['title'],
                ':content' => $active['content'],
                ':user_id' => $active['user_id'],
                ':wiki_article_id' => $active['wiki_article_id'],
                ':creation_date' => $active['creation_date'],
                ':general' => $active['general']
            ]);

            // Promote backup version to active
            $stmtUpdateActive = $this->conn->prepare("
                UPDATE wiki_change
                SET title = :title, content = :content, user_id = :user_id, general = :general, creation_date = :creation_date
                WHERE id = :active_id
            ");
            $stmtUpdateActive->execute([
                ':title' => $backup['title'],
                ':content' => $backup['content'],
                ':user_id' => $backup['user_id'],
                ':general' => $backup['general'],
                ':creation_date' => $backup['creation_date'],
                ':active_id' => $active['id']
            ]);

            // Remove the restored version from backup (since it is now active)
            $stmtDeleteBackup = $this->conn->prepare("
                DELETE FROM backup_wiki_change
                WHERE id = :backup_id
            ");
            $stmtDeleteBackup->execute([':backup_id' => $backup_wiki_change_id]);

            // Commit transaction
            $this->conn->commit();

            $this->success("Wiki article restored successfully", [
                "restored_version" => $backup['id'],
                "new_active_id" => $active['id']
            ], 200);

        } catch (PDOException $e) {
            $this->conn->rollBack();
            $this->error("Database error: " . $e->getMessage(), [], 500);
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