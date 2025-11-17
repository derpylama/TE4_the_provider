<?php

require_once('../api-handler.php');
class WikiApiHandler extends BaseApiHandler{

    public function createWiki() {//example method
        $stmt = $this->conn->query("SELECT * FROM users");
        return $stmt->fetchAll();
    }
}

?>