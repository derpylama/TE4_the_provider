<?php
class BaseApiHandler{
    protected $conn;

    private $dbServer = 'localhost';
    private $dbName = 'octopus';
    private $dbUser = 'root';
    private $dbPass = '';
    private $dbCharset = 'utf8';

    private $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    function __construct()
    {
        try {
            $this->conn = new PDO("mysql:host=$this->dbServer;dbname=$this->dbName;charset=$this->dbCharset", $this->dbUser, $this->dbPass, $this->options);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }

    }

    function __destruct()
    {
        $this->conn = null;
    }
}

