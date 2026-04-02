<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function getCurrentUser($conn) {
    if ($conn === null) {
        return null;
    }
    
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    return null;
}

function checkDatabaseConnection($conn) {
    if ($conn === null) {
        die("Database connection failed. Please check your XAMPP setup and database.");
    }
}
?>