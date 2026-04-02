<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

if ($conn === null) {
    die("Database connection failed. Please check your database setup.");
}

requireLogin();

$current_user = getCurrentUser($conn);

// Get user's friends
$stmt = $conn->prepare("
    SELECT u.*
    FROM users u
    WHERE u.id IN (
        SELECT friend_id FROM friends WHERE user_id = ? AND status = 'accepted'
        UNION
        SELECT user_id FROM friends WHERE friend_id = ? AND status = 'accepted'
    )
");
$stmt->execute([$current_user['id'], $current_user['id']]);
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's posts
$stmt = $conn->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$current_user['id']]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$stmt = $conn->prepare("SELECT COUNT(*) as like_count FROM likes WHERE post_id IN (SELECT id FROM posts WHERE user_id = ?)");
$stmt->execute([$current_user['id']]);
$like_count = $stmt->fetch(PDO::FETCH_ASSOC)['like_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Social Network</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="profile-card">
                <i class="fas fa-user-circle" style="font-size: 80px; color: var(--primary); margin-bottom: 15px;"></i>
                <h3><?php echo htmlspecialchars($current_user['full_name']); ?></h3>
                <p><i class="fas fa-at"></i> @<?php echo htmlspecialchars($current_user['username']); ?></p>
                <a href="dashboard.php" class="btn btn-small btn-primary"><i class="fas fa-home"></i> Dashboard</a>
                <a href="recommend_friends.php" class="btn btn-small btn-secondary"><i class="fas fa-users"></i> Find Friends</a>
                <a href="search.php" class="btn btn-small btn-info"><i class="fas fa-search"></i> Search</a>
                <a href="logout.php" class="btn btn-small btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="profile-info">
                <h2><i class="fas fa-user"></i> My Profile</h2>
                <div style="display: grid; gap: 15px;">
                    <p><strong><i class="fas fa-user"></i> Full Name:</strong> <?php echo htmlspecialchars($current_user['full_name']); ?></p>
                    <p><strong><i class="fas fa-at"></i> Username:</strong> @<?php echo htmlspecialchars($current_user['username']); ?></p>
                    <p><strong><i class="fas fa-envelope"></i> Email:</strong> <?php echo htmlspecialchars($current_user['email']); ?></p>
                    <p><strong><i class="fas fa-info-circle"></i> Bio:</strong> <?php echo htmlspecialchars($current_user['bio'] ?: 'No bio yet'); ?></p>
                    <p><strong><i class="fas fa-calendar"></i> Member since:</strong> <?php echo date('F d, Y', strtotime($current_user['created_at'])); ?></p>
                    <div style="display: flex; gap: 20px; margin-top: 20px;">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo count($friends); ?></div>
                            <div>Friends</div>
                        </div>
                        <div class="stats-card">
                            <div class="stats-number"><?php echo count($posts); ?></div>
                            <div>Posts</div>
                        </div>
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $like_count; ?></div>
                            <div>Likes Received</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="friends-list">
                <h2><i class="fas fa-users"></i> My Friends (<?php echo count($friends); ?>)</h2>
                <?php if (count($friends) > 0): ?>
                    <div class="friends-grid">
                        <?php foreach ($friends as $friend): ?>
                            <div class="friend-card">
                                <i class="fas fa-user-circle" style="font-size: 40px; color: var(--primary); margin-bottom: 10px;"></i>
                                <strong><?php echo htmlspecialchars($friend['full_name']); ?></strong>
                                <small>@<?php echo htmlspecialchars($friend['username']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> You haven't added any friends yet. <a href="recommend_friends.php">Find friends</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="my-posts">
                <h2><i class="fas fa-pen-alt"></i> My Posts (<?php echo count($posts); ?>)</h2>
                <?php if (count($posts) > 0): ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post">
                            <div class="post-content">
                                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                            </div>
                            <div class="post-footer">
                                <small><i class="far fa-clock"></i> <?php echo date('F d, Y \a\t H:i', strtotime($post['created_at'])); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> You haven't created any posts yet. Go to <a href="dashboard.php">dashboard</a> to create one!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>