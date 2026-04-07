<?php

class Database {
    private $host;
    private $db;
    private $user;
    private $pass;
    private $port;
    private $env;

    public function __construct() {
        $this->env = getenv('APP_ENV') ?: 'local';

        if ($this->env === 'production') {
            // Railway
            $this->host = getenv('MYSQLHOST');
            $this->port = getenv('MYSQLPORT');
            $this->db   = getenv('MYSQLDATABASE');
            $this->user = getenv('MYSQLUSER');
            $this->pass = getenv('MYSQLPASSWORD');
        } else {
            // Local
            $this->host = "localhost";
            $this->port = "3306";
            $this->db   = "citas_db";
            $this->user = "root";
            $this->pass = "";
        }
    }

    public function connect() {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset=utf8mb4";

            $pdo = new PDO($dsn, $this->user, $this->pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            return $pdo;

        } catch (Exception $e) {
            die("Error DB: " . $e->getMessage());
        }
    }
}