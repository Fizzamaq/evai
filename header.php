<?php
// Removed session_start() as it's handled by config.php
// if (session_status() === PHP_SESSION_NONE) {
//     session_start([
//         'cookie_lifetime' => 86400, // 1 day
//         'read_and_close'  => false,
//     ]);
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventCraftAI</title>
    <link rel="stylesheet" href="<?= ASSETS_PATH ?>css/style.css"> <?php if (file_exists("../assets/css/" . basename($_SERVER['PHP_SELF'], '.php') . '.css')): ?>
        <link rel="stylesheet" href="../assets/css/<?= basename($_SERVER['PHP_SELF'], '.php') ?>.css">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <a href="<?= BASE_URL ?>public/index.php" class="logo">EventCraftAI</a>
            <nav class="main-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= BASE_URL ?>public/dashboard.php">Dashboard</a>
                    <a href="<?= BASE_URL ?>public/events.php">My Events</a>
                    <a href="<?= BASE_URL ?>public/ai_chat.php">AI Assistant</a>
                    <a href="<?= BASE_URL ?>public/profile.php">Profile</a>
                    <a href="<?= BASE_URL ?>public/logout.php">Logout</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>public/index.php">Home</a>
                    <a href="<?= BASE_URL ?>public/login.php">Login</a>
                    <a href="<?= BASE_URL ?>public/register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="container">
