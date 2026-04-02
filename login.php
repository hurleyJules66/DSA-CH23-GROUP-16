<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Username and password are required!";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid username or password!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Social Network</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-box">
            <i class="fas fa-users" style="font-size: 60px; color: var(--primary); margin-bottom: 20px;"></i>
            <h1>Welcome Back!</h1>
            <p>Login to your account</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username or Email</label>
                    <input type="text" name="username" placeholder="Enter your username or email" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <p style="margin-top: 20px;">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
            
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--gray-light);">
                <p style="font-size: 0.85rem; color: var(--gray);">
                    <i class="fas fa-info-circle"></i> Demo Accounts:<br>
                    Username: john_doe | Password: password<br>
                    Username: jane_smith | Password: password
                </p>
            </div>
        </div>
    </div>
</body>
</html>