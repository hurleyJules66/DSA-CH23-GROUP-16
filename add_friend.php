<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

$database = new Database();
$conn = $database->getConnection();
$current_user = getCurrentUser($conn);

if (isset($_GET['id'])) {
    $friend_id = $_GET['id'];
    
    // Check if already friends or request exists
    $stmt = $conn->prepare("SELECT id FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
    $stmt->execute([$current_user['id'], $friend_id, $friend_id, $current_user['id']]);
    
    if ($stmt->rowCount() == 0) {
        $stmt = $conn->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$current_user['id'], $friend_id]);
        
        // Log activity
        $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, action_data) VALUES (?, 'friend_request', ?)");
        $stmt->execute([$current_user['id'], json_encode(['friend_id' => $friend_id])]);
    }
}

header("Location: search.php");
exit();
?>