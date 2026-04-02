<?php
echo "<h1>Database Connection Check</h1>";

// Test MySQL connection without database
try {
    $test_conn = new PDO("mysql:host=localhost", "root", "");
    $test_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ MySQL is running<br>";
    
    // Check if database exists
    $stmt = $test_conn->query("SHOW DATABASES LIKE 'social_network'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Database 'social_network' exists<br>";
        
        // Test connection to the database
        require_once 'config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        if ($conn !== null) {
            echo "✅ Successfully connected to social_network database<br>";
            
            // Check tables
            $tables = ['users', 'friends', 'posts', 'likes', 'activity_log'];
            $stmt = $conn->query("SHOW TABLES");
            $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<br>Tables found:<br>";
            foreach ($tables as $table) {
                if (in_array($table, $existing_tables)) {
                    echo "✅ $table<br>";
                } else {
                    echo "❌ $table is missing<br>";
                }
            }
        }
    } else {
        echo "❌ Database 'social_network' does not exist<br>";
        echo "Please create it in phpMyAdmin first.<br>";
    }
    
} catch(PDOException $e) {
    echo "❌ MySQL connection failed: " . $e->getMessage() . "<br>";
    echo "Please make sure XAMPP is running and MySQL service is started.<br>";
}
?>