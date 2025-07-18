<?php

namespace NetflixLoginService\Config;

use PDO;
use PDOException;
use Exception;

class Database 
{
    private static $instance = null;
    private $connection;
    private $host;
    private $port;
    private $database;
    private $username;
    private $password;
    
    private function __construct()
    {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->port = $_ENV['DB_PORT'] ?? '3306';
        $this->database = $_ENV['DB_NAME'] ?? 'netflix_login_service';
        $this->username = $_ENV['DB_USERNAME'] ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
        
        $this->connect();
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect()
    {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
            // Set timezone
            $timezone = $_ENV['APP_TIMEZONE'] ?? 'UTC';
            $this->connection->exec("SET time_zone = '{$timezone}'");
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }
    
    public function getConnection()
    {
        // Check if connection is still alive
        try {
            $this->connection->query('SELECT 1');
        } catch (PDOException $e) {
            // Reconnect if connection is lost
            $this->connect();
        }
        
        return $this->connection;
    }
    
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }
    
    public function commit()
    {
        return $this->connection->commit();
    }
    
    public function rollback()
    {
        return $this->connection->rollBack();
    }
    
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}