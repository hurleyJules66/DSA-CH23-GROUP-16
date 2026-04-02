<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

// Check if connection was successful
if ($conn === null) {
    die("Database connection failed. Please check your database setup.");
}

requireLogin();

$current_user = getCurrentUser($conn);

// Get friend recommendations using hash counts (Mutual friends)
// Simplified query to avoid complex joins
$stmt = $conn->prepare("
    SELECT u.*, 
           (
               SELECT COUNT(*)
               FROM friends f1
               WHERE (
                   (f1.user_id = u.id AND f1.friend_id IN (
                       SELECT friend_id FROM friends WHERE user_id = ? AND status = 'accepted'
                       UNION
                       SELECT user_id FROM friends WHERE friend_id = ? AND status = 'accepted'
                   ))
                   OR
                   (f1.friend_id = u.id AND f1.user_id IN (
                       SELECT friend_id FROM friends WHERE user_id = ? AND status = 'accepted'
                       UNION
                       SELECT user_id FROM friends WHERE friend_id = ? AND status = 'accepted'
                   ))
               )
               AND f1.status = 'accepted'
           ) as mutual_count
    FROM users u
    WHERE u.id != ?
      AND NOT EXISTS (
          SELECT 1 FROM friends 
          WHERE ((user_id = ? AND friend_id = u.id) OR (friend_id = ? AND user_id = u.id))
      )
    HAVING mutual_count >= 0
    ORDER BY mutual_count DESC, u.full_name
    LIMIT 20
");
$stmt->execute([
    $current_user['id'], $current_user['id'],
    $current_user['id'], $current_user['id'],
    $current_user['id'],
    $current_user['id'], $current_user['id']
]);
$recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Send friend request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_request'])) {
    $friend_id = $_POST['friend_id'];
    
    // Check if request already exists
    $stmt = $conn->prepare("SELECT id FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
    $stmt->execute([$current_user['id'], $friend_id, $friend_id, $current_user['id']]);
    
    if ($stmt->rowCount() == 0) {
        $stmt = $conn->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$current_user['id'], $friend_id]);
        
        // Log activity for undo (Stack implementation)
        $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, action_data) VALUES (?, 'friend_request', ?)");
        $stmt->execute([$current_user['id'], json_encode(['friend_id' => $friend_id])]);
        
        header("Location: recommend_friends.php?success=1");
        exit();
    } else {
        header("Location: recommend_friends.php?error=already_sent");
        exit();
    }
}

// Undo last friend request (Stack - LIFO)
if (isset($_GET['undo'])) {
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
        
        header("Location: recommend_friends.php?undone=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friend Recommendations - Social Network</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="profile-card">
                <h3><?php echo htmlspecialchars($current_user['full_name']); ?></h3>
                <p>@<?php echo htmlspecialchars($current_user['username']); ?></p>
                <a href="dashboard.php" class="btn btn-small">Dashboard</a>
                <a href="profile.php" class="btn btn-small">My Profile</a>
                <a href="search.php" class="btn btn-small">Search</a>
                <a href="logout.php" class="btn btn-small btn-danger">Logout</a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="recommendations-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Friend Recommendations</h2>
                <?php
                // Check if there are any friend requests to undo
                $stmt = $conn->prepare("SELECT COUNT(*) FROM activity_log WHERE user_id = ? AND action_type = 'friend_request'");
                $stmt->execute([$current_user['id']]);
                $has_requests = $stmt->fetchColumn() > 0;
                ?>
                <?php if ($has_requests): ?>
                    <a href="?undo" class="btn btn-warning">↩️ Undo Last Request (Stack)</a>
                <?php endif; ?>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">✅ Friend request sent successfully!</div>
            <?php endif; ?>
            
            <?php if (isset($_GET['undone'])): ?>
                <div class="alert alert-info">↩️ Last friend request undone!</div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error']) && $_GET['error'] == 'already_sent'): ?>
                <div class="alert alert-error">⚠️ Friend request already sent!</div>
            <?php endif; ?>
            
            <div class="friends-grid">
                <?php if (count($recommendations) > 0): ?>
                    <?php foreach ($recommendations as $user): ?>
                        <div class="recommendation-card">
                            <div class="user-info">
                                <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                <small>@<?php echo htmlspecialchars($user['username']); ?></small>
                                <?php if ($user['mutual_count'] > 0): ?>
                                    <div class="mutual-count">
                                        🤝 <?php echo $user['mutual_count']; ?> mutual friend<?php echo $user['mutual_count'] > 1 ? 's' : ''; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <form method="POST" action="">
                                <input type="hidden" name="friend_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="send_request" class="btn btn-small btn-primary">➕ Add Friend</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p>No recommendations available at the moment.</p>
                        <p>Try adding more friends to get recommendations!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h3>💡 How Recommendations Work</h3>
                <p>Friend recommendations are based on <strong>mutual friends count</strong> (Hash Map/Count approach).</p>
                <p>The more mutual friends you share with someone, the higher they appear in recommendations!</p>
                <p><strong>Stack Implementation:</strong> Your last 5 friend requests are stored and can be undone (LIFO).</p>
            </div>
        </div>
    </div>
</body>
</html>