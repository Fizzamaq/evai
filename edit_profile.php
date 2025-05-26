<?php
require_once '../includes/config.php';
require_once '../classes/User.class.php'; // Include User class
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "public/login.php");
    exit();
}

$user = new User($pdo); // Pass PDO
$profile = $user->getProfile($_SESSION['user_id']); // getProfile is correct

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
            <?php if (!empty($profile['profile_image'])): ?>
                <p style="margin-top: 10px;">Current: <img src="<?= ASSETS_PATH ?>uploads/users/<?= htmlspecialchars($profile['profile_image']) ?>" alt="Profile Picture" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;"></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" value="<?= htmlspecialchars($profile['first_name']) ?>" required>
        </div>

        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" value="<?= htmlspecialchars($profile['last_name']) ?>" required>
        </div>

        <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" value="<?= htmlspecialchars($profile['address'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>City</label>
            <input type="text" name="city" value="<?= htmlspecialchars($profile['city'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>State</label>
            <input type="text" name="state" value="<?= htmlspecialchars($profile['state'] ?? '') ?>">
        </div>

        <button type="submit" class="btn">Save Changes</button>
    </form>
</div>
<?php include 'footer.php'; ?>
