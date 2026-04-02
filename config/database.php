<?php
// Database configuration
class Database {
    private $host = "localhost";
    private $db_name = "social_network";
    private $username = "root";
    private $password = "";
    public $conn;
    private $error;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                  $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
            return $this->conn;
        } catch(PDOException $exception) {
            $this->error = $exception->getMessage();
            echo "Connection error: " . $this->error;
            echo "<br><br>Please make sure:<br>";
            echo "1. MySQL is running in XAMPP<br>";
            echo "2. Database 'social_network' exists<br>";
            echo "3. Run the SQL script from phpMyAdmin first<br>";
            return null;
        }
    }
    
    public function getError() {
        return $this->error;
    }
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>