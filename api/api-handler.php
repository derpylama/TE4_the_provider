<?php
class BaseApiHandler{
    private $conn;
    private $dbServer = "localhost";
    private $dbUser = "root";
    private $dbPass = "";
    private $dbName = "octopus";

    function __construct()
    {
        $this->conn = new mysqli($this->dbServer, $this->dbUser, $this->dbPass, $this->dbName);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    function __destruct()
    {
        $this->conn->close();
    }
}