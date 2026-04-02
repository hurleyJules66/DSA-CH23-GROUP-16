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

// Create post
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_post'])) {
    $content = trim($_POST['content']);
    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        $stmt->execute([$current_user['id'], $content]);
        header("Location: dashboard.php");
        exit();
    }
}

// Like/Unlike post
if (isset($_GET['like'])) {
    $post_id = $_GET['like'];
    $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$current_user['id'], $post_id]);
    
    if ($stmt->rowCount() > 0) {
        // Unlike
        $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$current_user['id'], $post_id]);
    } else {
        // Like
        $stmt = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$current_user['id'], $post_id]);
    }
    header("Location: dashboard.php");
    exit();
}

// Get friends' posts
$stmt = $conn->prepare("
    SELECT p.*, u.full_name, u.username, 
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.user_id IN (
        SELECT friend_id FROM friends WHERE user_id = ? AND status = 'accepted'
        UNION
        SELECT user_id FROM friends WHERE friend_id = ? AND status = 'accepted'
        UNION
        SELECT ? 
    )
    ORDER BY p.created_at DESC
");
$stmt->execute([$current_user['id'], $current_user['id'], $current_user['id'], $current_user['id']]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$stmt = $conn->prepare("SELECT COUNT(*) as friend_count FROM friends WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted'");
$stmt->execute([$current_user['id'], $current_user['id']]);
$friend_count = $stmt->fetch(PDO::FETCH_ASSOC)['friend_count'];

$stmt = $conn->prepare("SELECT COUNT(*) as post_count FROM posts WHERE user_id = ?");
$stmt->execute([$current_user['id']]);
$post_count = $stmt->fetch(PDO::FETCH_ASSOC)['post_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Social Network</title>
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
                <div class="stats-card" style="margin: 15px 0;">
                    <div class="stats-number"><?php echo $friend_count; ?></div>
                    <div>Friends</div>
                </div>
                <div class="stats-card" style="margin: 15px 0;">
                    <div class="stats-number"><?php echo $post_count; ?></div>
                    <div>Posts</div>
                </div>
                <a href="profile.php" class="btn btn-small btn-primary"><i class="fas fa-user"></i> My Profile</a>
                <a href="recommend_friends.php" class="btn btn-small btn-secondary"><i class="fas fa-users"></i> Find Friends</a>
                <a href="search.php" class="btn btn-small btn-info"><i class="fas fa-search"></i> Search</a>
                <a href="logout.php" class="btn btn-small btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="create-post">
                <h2><i class="fas fa-pen-alt"></i> Create Post</h2>
                <form method="POST" action="">
                    <textarea name="content" rows="3" placeholder="What's on your mind, <?php echo htmlspecialchars($current_user['full_name']); ?>?" required></textarea>
                    <button type="submit" name="create_post" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Post</button>
                </form>
            </div>
            
            <div class="posts">
                <h2><i class="fas fa-newspaper"></i> Your Feed</h2>
                <?php if (count($posts) > 0): ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post">
                            <div class="post-header">
                                <strong><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($post['full_name']); ?></strong>
                                <small><i class="far fa-clock"></i> <?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?></small>
                            </div>
                            <div class="post-content">
                                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                            </div>
                            <div class="post-footer">
                                <a href="?like=<?php echo $post['id']; ?>" class="like-btn">
                                    <?php echo $post['user_liked'] ? '<i class="fas fa-heart" style="color: #ff6b6b;"></i>' : '<i class="far fa-heart"></i>'; ?> 
                                    <span><?php echo $post['like_count']; ?> likes</span>
                                </a>
                                <span><i class="far fa-comment"></i> 0 comments</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No posts yet. Add some friends to see their posts!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>