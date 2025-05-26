<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/classes/User.class.php';

session_start();
$user = new User();

// Verify token validity
$token = $_GET['token'] ?? null;
$validToken = $user->validateResetToken($token);

if (!$validToken) {
    $_SESSION['error'] = "Invalid or expired reset link";
    header('Location: forgot_password.php');
    exit();
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($password !== $confirmPassword) {
        $_SESSION['error'] = "Passwords do not match";
    } elseif ($user->resetPassword($token, $password)) {
        $_SESSION['success'] = "Password updated successfully!";
        header('Location: login.php');
        exit();
    } else {
        $_SESSION['error'] = "Failed to reset password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - EventCraftAI</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="auth-container">
        <div class="auth-card">
            <h1>Set New Password</h1>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn primary">Reset Password</button>
            </form>
        </div>
    </div>
</body>
</html>