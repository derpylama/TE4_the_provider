<?php

require_once('../api-handler.php');
class WikiApiHandler extends BaseApiHandler{

    protected function checkServiceAndToken($token, $service="wiki"){
        return parent::checkServiceAndToken($token, $service);
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
            $this->conn->beginTransaction();
            
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
            $this->conn->beginTransaction();
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
                ':general' => json_encode($general)
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

    public function editWikiArticle($newContent, $wiki_article_id, $token, $newGeneral, $newTitle) {
        // Token check
        $tokeninfo = $this->checkServiceAndToken($token); 
        if ($tokeninfo['status'] !== "success") {
            $this->error($tokeninfo["message"], [], 401);
        }

        // Permission check: only admins/end-users
        if ($tokeninfo['type'] === 'user') {
            $this->error("Insufficient permissions", [], 403);
        }

        $user_id = $tokeninfo["userId"];
        $customer_id = $tokeninfo["customer_id"];

        try {
            // Start transaction
            $this->conn->beginTransaction();

            // Verify wiki_article exists and get wiki_id
            $wikiIdStmt = $this->conn->prepare("SELECT wiki_id FROM wiki_article WHERE id = :wiki_article_id");
            $wikiIdStmt->execute([':wiki_article_id' => $wiki_article_id]);
            $wiki_id = $wikiIdStmt->fetchColumn();
            if (!$wiki_id) {
                $this->error("Wiki article not found.", [], 404);
            }

            // Verify customer ownership
            $ownerCheckStmt = $this->conn->prepare("
                SELECT u.customer_id
                FROM wiki w
                JOIN user u ON w.user_id = u.id
                WHERE w.id = :wiki_id
            ");
            $ownerCheckStmt->execute([':wiki_id' => $wiki_id]);
            $ownerCustomerId = $ownerCheckStmt->fetchColumn();
            if ($ownerCustomerId !== $customer_id) {
                $this->error("Wiki article not found.", [], 404);
            }

            // Get current active version
            $oldChangesStmt = $this->conn->prepare("
                SELECT * 
                FROM wiki_change 
                WHERE wiki_article_id = :wiki_article_id 
                ORDER BY creation_date DESC 
                LIMIT 1
            ");
            $oldChangesStmt->execute([':wiki_article_id' => $wiki_article_id]);
            $oldChanges = $oldChangesStmt->fetch(PDO::FETCH_ASSOC);
            if (!$oldChanges) {
                $this->error("No previous changes found for this wiki article.", [], 404);
            }

            // Determine new values
            $newContent = $newContent ?: $oldChanges['content'];
            $newGeneral = !empty($newGeneral) ? json_encode($newGeneral) : $oldChanges['general'];
            $newTitle = $newTitle ?: $oldChanges['title'];

           // Insert new active version
            $stmtInsert = $this->conn->prepare("
                INSERT INTO wiki_change
                (wiki_article_id, content, user_id, general, title)
                VALUES (:wiki_article_id, :content, :user_id, :general, :title)
            ");
            $stmtInsert->execute([
                ':wiki_article_id' => $wiki_article_id,
                ':content'        => $newContent,
                ':user_id'        => $user_id,
                ':general'        => $newGeneral,
                ':title'          => $newTitle
            ]);

            // Fetch the newly inserted record reliably using wiki_article_id and creation_date
            $stmtNewActive = $this->conn->prepare("
                SELECT id
                FROM wiki_change
                WHERE wiki_article_id = :wiki_article_id
                ORDER BY creation_date DESC
                LIMIT 1
            ");
            $stmtNewActive->execute([':wiki_article_id' => $wiki_article_id]);
            $newChangeId = $stmtNewActive->fetchColumn();

            // Move all old versions (except new) to backup_wiki_change
            $moveStmt = $this->conn->prepare("
                INSERT INTO backup_wiki_change 
                (title, content, user_id, wiki_article_id, creation_date, general, restored_from_backup_id)
                SELECT title, content, user_id, wiki_article_id, creation_date, general, restored_from_backup_id
                FROM wiki_change
                WHERE wiki_article_id = :article_id AND id != :new_id
            ");
            $moveStmt->execute([
                ':article_id' => $wiki_article_id,
                ':new_id'     => $newChangeId
            ]);

            // Delete old active versions from wiki_change (keep the new one)
            $deleteStmt = $this->conn->prepare("
                DELETE FROM wiki_change
                WHERE wiki_article_id = :article_id AND id != :new_id
            ");
            $deleteStmt->execute([
                ':article_id' => $wiki_article_id,
                ':new_id'     => $newChangeId
            ]);

            // Commit transaction inside success
            $this->success("Wiki article edited successfully.", [], 200);

        } catch (PDOException $e) {
            // rollback transaction inside error
            $this->error("Database error: " . $e->getMessage(), [], 500);
        }
    }

    //FIXMTHIS
    public function getAllWiki($token, string $searchQuery = "", array $searchFilter = [], int $amount = 20, int $offset = 0, string $orderDirection = "DESC") {
        // ---------------- Token Check ---------------------------------------
        $tokeninfo = $this->checkServiceAndToken($token); 
        if ($tokeninfo['status'] !== "success") {
            $this->error($tokeninfo["message"], [], 401);
        }
    
        // Permissions
        if ($tokeninfo['type'] === 'user') {
            $this->error("Insufficient permissions", [], 403);
        }
        //---------------------------------------------------------------------
        $customerId = $tokeninfo["customer_id"];
    
        try {
    
            // Validate sort
            $orderDirection = strtoupper($orderDirection);
            if (!in_array($orderDirection, ["ASC", "DESC"])) {
                $this->error("orderDirection must be ASC or DESC", [], 400);
            }
    
            // Base query
            $baseQuery = "
                FROM wiki w
                INNER JOIN user u ON u.id = w.user_id
                WHERE u.customer_id = :customerId
            ";
    
            $params = [":customerId" => $customerId];
    
            // ---- Search (like getWikiArticle) ----
            if ($searchQuery !== "") {
                $allowedFilters = ["title", "description"];
    
                if (!is_array($searchFilter)) {
                    $this->error("searchFilter must be an array", [], 400);
                }
    
                // If empty → search all allowed fields
                $filters = !empty($searchFilter) ? $searchFilter : $allowedFilters;
    
                // Check for invalid filters
                $invalidFilters = array_diff($filters, $allowedFilters);
                if (!empty($invalidFilters)) {
                    $this->error("Invalid search filter(s): " . implode(", ", $invalidFilters), [], 400);
                }
    
                // Build OR LIKE conditions
                $conditions = [];
                foreach ($filters as $i => $filter) {
                    $p = ":search$i";
                    $conditions[] = "w.$filter LIKE $p";
                    $params[$p] = "%$searchQuery%";
                }
    
                $baseQuery .= " AND (" . implode(" OR ", $conditions) . ")";
            }
    
            // ---- Count matching results ----
            $countStmt = $this->conn->prepare("SELECT COUNT(*) " . $baseQuery);
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();
    
            // ---- Get paginated wiki list ----
            $stmt = $this->conn->prepare("
                SELECT w.id, w.title, w.description, w.creation_date
                $baseQuery
                ORDER BY w.creation_date $orderDirection
                LIMIT :amount OFFSET :offset
            ");
    
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(":amount", $amount, PDO::PARAM_INT);
            $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    
            $stmt->execute();
            $result = $stmt->fetchAll();
    
            // ---- Send response ----
            $this->success(
                "Fetched wikis",
                [
                    "wikis"       => $result,
                    "total_count" => $totalCount,
                    "offset"      => $offset,
                    "amount"      => $amount
                ],
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
        $tokeninfo = $this->checkServiceAndToken($token); 
        if ($tokeninfo['status'] !== "success") {
            $this->error($tokeninfo["message"], [], 401);
        }

        if ($tokeninfo['type'] === 'user') {
            $this->error("Insufficient permissions", [], 403);
        }

        $customer_id = $tokeninfo["customer_id"];

        try {
            $this->conn->beginTransaction();

            // Fetch backup version + wiki + user info
            $stmt = $this->conn->prepare("
                SELECT bwc.*, wa.wiki_id, w.user_id AS wiki_owner_user_id, u.customer_id AS wiki_owner_customer_id
                FROM backup_wiki_change bwc
                INNER JOIN wiki_article wa ON bwc.wiki_article_id = wa.id
                INNER JOIN wiki w ON wa.wiki_id = w.id
                INNER JOIN user u ON w.user_id = u.id
                WHERE bwc.id = :backup_id
            ");
            $stmt->execute([':backup_id' => $backup_wiki_change_id]);
            $backup = $stmt->fetch();

            if (!$backup) {
                $this->error("Backup version not found", [], 404);
            }

            // Check that requesting user’s customer matches the wiki’s customer
            if ($backup['wiki_owner_customer_id'] != $customer_id) {
                $this->error("backup_wiki_change not found", [], 404); 
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
            $active = $stmtActive->fetch();

            if (!$active) {
                $this->error("No active version found for this wiki article", [], 404);
            }

            // Move current active to backup, set restored_from_backup_id if applicable
            $stmtInsertBackup = $this->conn->prepare("
                INSERT INTO backup_wiki_change
                (title, content, user_id, wiki_article_id, creation_date, general, restored_from_backup_id)
                VALUES (:title, :content, :user_id, :wiki_article_id, :creation_date, :general, :restored_from_backup_id)
            ");
            $stmtInsertBackup->execute([
                ':title' => $active['title'],
                ':content' => $active['content'],
                ':user_id' => $active['user_id'],
                ':wiki_article_id' => $active['wiki_article_id'],
                ':creation_date' => $active['creation_date'],
                ':general' => $active['general'],
                ':restored_from_backup_id' => $active['restored_from_backup_id'] ?? null
            ]);

            // Insert restored version as new active
            $stmtInsertActive = $this->conn->prepare("
                INSERT INTO wiki_change
                (title, content, user_id, wiki_article_id, general, restored_from_backup_id)
                VALUES (:title, :content, :user_id, :wiki_article_id, :general, :restored_from_backup_id)
            ");
            $stmtInsertActive->execute([
                ':title' => $backup['title'],
                ':content' => $backup['content'],
                ':user_id' => $backup['wiki_owner_user_id'],
                ':wiki_article_id' => $backup['wiki_article_id'],
                ':general' => $backup['general'],
                ':restored_from_backup_id' => $backup['id']
            ]);

            // Fetch newly inserted active version reliably
            $stmtNewActive = $this->conn->prepare("
                SELECT id 
                FROM wiki_change 
                WHERE wiki_article_id = :wiki_article_id 
                ORDER BY creation_date DESC
                LIMIT 1
            ");
            $stmtNewActive->execute([':wiki_article_id' => $backup['wiki_article_id']]);
            $newActiveId = $stmtNewActive->fetchColumn();


            $this->success("Wiki article restored successfully", [
                "restored_backup_id" => $backup['id'],
                "new_active_id" => $newActiveId
            ], 200);

        } catch (PDOException $e) {
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
            $this->conn->beginTransaction();
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
            $this->conn->beginTransaction();
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





}

?>