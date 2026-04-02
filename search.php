<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

$database = new Database();
$conn = $database->getConnection();
$current_user = getCurrentUser($conn);

$search_results = [];
$search_term = '';

// Search users (Binary search simulation with SQL LIKE)
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search_term = $_GET['q'];
    $stmt = $conn->prepare("
        SELECT * FROM users 
        WHERE (username LIKE ? OR full_name LIKE ?) 
        AND id != ?
        LIMIT 20
    ");
    $search_pattern = "%$search_term%";
    $stmt->execute([$search_pattern, $search_pattern, $current_user['id']]);
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Friends - Social Network</title>
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
                <a href="recommend_friends.php" class="btn btn-small">Recommendations</a>
                <a href="logout.php" class="btn btn-small btn-danger">Logout</a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="search-box">
                <h2>Search Friends</h2>
                <form method="GET" action="">
                    <input type="text" name="q" placeholder="Search by name or username..." 
                           value="<?php echo htmlspecialchars($search_term); ?>" required>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
            
            <?php if ($search_term): ?>
                <div class="search-results">
                    <h3>Search Results (<?php echo count($search_results); ?> found)</h3>
                    
                    <?php foreach ($search_results as $user): ?>
                        <div class="user-card">
                            <div>
                                <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                <small>@<?php echo htmlspecialchars($user['username']); ?></small>
                            </div>
                            <a href="add_friend.php?id=<?php echo $user['id']; ?>" class="btn btn-small">Add Friend</a>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($search_results) == 0): ?>
                        <p>No users found matching "<?php echo htmlspecialchars($search_term); ?>"</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>