<?php
require_once '../includes/config.php';
require_once '../classes/User.class.php'; // ADD THIS LINE

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['login_error'] = "Invalid request method";
    header("Location: login.php");
    exit();
}

$email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
$password = $_POST['password'];

if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = "Email and password are required";
    header("Location: login.php");
    exit();
}

try {
    $user = new User($pdo);
    
    if ($user->login($email, $password)) {
        // Check if there's a redirect URL
        $redirect = $_SESSION['login_redirect'] ?? 'dashboard.php';
        unset($_SESSION['login_redirect']);
        header("Location: $redirect");
        exit();
    } else {
        $_SESSION['login_error'] = "Invalid email or password";
        header("Location: login.php");
        exit();
    }
} catch (Exception $e) {
    $_SESSION['login_error'] = "Login failed. Please try again.";
    header("Location: login.php");
    exit();
}