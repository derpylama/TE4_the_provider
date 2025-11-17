<?php

require_once('../api-handler.php');
class CalenderApiHandler extends BaseApiHandler{

    public function getUsers() {//example method
        $stmt = $this->conn->query("SELECT * FROM users");
        return $stmt->fetchAll();
    }
}

?>