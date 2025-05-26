<?php
require_once '../includes/config.php';
include 'header.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventCraftAI - Smart Event Planning</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/landing.css">
</head>
<body>
    <section class="hero">
        <div class="container">
            <h1>Plan Perfect Events with AI Assistance</h1>
            <p class="subtitle">From weddings to corporate gatherings</p>
            <div class="cta-buttons">
                <a href="register.php" class="btn primary">Get Started</a>
                <a href="login.php" class="btn secondary">Login</a>
            </div>
        </div>
    </section>
    <?php include 'footer.php'; ?>
</body>
</html>