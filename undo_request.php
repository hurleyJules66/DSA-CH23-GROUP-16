<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

$database = new Database();
$conn = $database->getConnection();
$current_user = getCurrentUser($conn);

// Get last friend request (Stack - LIFO)
$stmt = $conn->prepare("
    SELECT * FROM activity_log 
    WHERE user_id = ? AND action_type = 'friend_request' 
    ORDER BY created_at DESC LIMIT 1
");
$stmt->execute([$current_user['id']]);
$last_action = $stmt->fetch(PDO::FETCH_ASSOC);

if ($last_action) {
    $data = json_decode($last_action['action_data'], true);
    $friend_id = $data['friend_id'];
    
    // Delete the friend request
    $stmt = $conn->prepare("DELETE FROM friends WHERE user_id = ? AND friend_id = ? AND status = 'pending'");
    $stmt->execute([$current_user['id'], $friend_id]);
    
    // Delete the log entry
    $stmt = $conn->prepare("DELETE FROM activity_log WHERE id = ?");
    $stmt->execute([$last_action['id']]);
}

header("Location: recommend_friends.php");
exit();
?>