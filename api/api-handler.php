<?php
class BaseApiHandler{
    private $conn;

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
            $this->$conn = new PDO("mysql:host=$dbServer;dbname=$dbName;charset=$dbCharset", $dbUser, $dbPass, $options);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }

    }

    function __destruct()
    {
        $this->conn = null;
    }
}

