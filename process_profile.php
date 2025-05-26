<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

try {
    $user = new User($pdo);
    $uploader = new UploadHandler();
    
    $data = [
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'address' => $_POST['address'] ?? null,
        'city' => $_POST['city'] ?? null,
        'state' => $_POST['state'] ?? null
    ];
    
    // Handle profile picture upload
    if (!empty($_FILES['profile_image']['name'])) {
        $filename = $uploader->handleUpload($_FILES['profile_image'], 'users/');
        $user->updateProfileImage($userId, $filename);
    }
    
    $user->updateProfile($userId, $data);
    
    $_SESSION['profile_success'] = "Profile updated successfully";
    header("Location: profile.php");
    exit();
    
} catch (Exception $e) {
    $_SESSION['profile_error'] = $e->getMessage();
    header("Location: edit_profile.php");
    exit();
}