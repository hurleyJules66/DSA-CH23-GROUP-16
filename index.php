<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$conn = $database->getConnection();

// If logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Network - Welcome</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="welcome-box">
            <h1>Welcome to Social Network</h1>
            <p>Connect with friends, share posts, and discover new people!</p>
            <div class="buttons">
                <a href="register.php" class="btn btn-primary">Register</a>
                <a href="login.php" class="btn btn-secondary">Login</a>
            </div>
        </div>
        
        <div class="features">
            <h2>Features</h2>
            <ul>
                <li>✅ Friend requests and connections</li>
                <li>✅ Mutual friends recommendations</li>
                <li>✅ Create and like posts</li>
                <li>✅ Search for friends</li>
                <li>✅ Undo friend requests (Stack implementation)</li>
                <li>✅ Activity logging</li>
            </ul>
        </div>
    </div>
</body>
</html>