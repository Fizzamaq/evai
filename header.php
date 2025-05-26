<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // 1 day
        'read_and_close'  => false,
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventCraftAI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <?php if (file_exists("../assets/css/" . basename($_SERVER['PHP_SELF'], '.php') . '.css')): ?>
        <link rel="stylesheet" href="../assets/css/<?= basename($_SERVER['PHP_SELF'], '.php') ?>.css">
    <?php endif; ?>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <a href="index.php" class="logo">EventCraftAI</a>
            <nav class="main-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="events.php">My Events</a>
                    <a href="ai_chat.php">AI Assistant</a>
                    <a href="profile.php">Profile</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="index.php">Home</a>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="container">