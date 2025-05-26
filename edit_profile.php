<?php
require_once '../includes/config.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../classes/User.class.php';
$user = new User($pdo);
$profile = $user->getProfile($_SESSION['user_id']);

$error = $_SESSION['profile_error'] ?? null;
$success = $_SESSION['profile_success'] ?? null;
unset($_SESSION['profile_error'], $_SESSION['profile_success']);
?>
<div class="profile-container">
    <h1>Edit Profile</h1>
    
    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <form action="process_profile.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label>Profile Picture</label>
            <input type="file" name="profile_image" accept="image/*">
        </div>
        
        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" value="<?= htmlspecialchars($profile['first_name']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" value="<?= htmlspecialchars($profile['last_name']) ?>" required>
        </div>
        
        <button type="submit" class="btn">Save Changes</button>
    </form>
</div>
<?php include 'footer.php'; ?>