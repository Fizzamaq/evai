<?php
require_once '../includes/config.php';
require_once '../classes/User.class.php'; // Include User class
require_once '../classes/UploadHandler.class.php'; // Include UploadHandler

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "public/login.php");
    exit();
}

$userId = $_SESSION['user_id'];

try {
    $user = new User($pdo); // Pass PDO
    $uploader = new UploadHandler(); // Instantiate UploadHandler

    $data = [
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'address' => $_POST['address'] ?? null,
        'city' => $_POST['city'] ?? null,
        'state' => $_POST['state'] ?? null
        // country and postal_code are in user_profiles but not in this form
    ];

    // Handle profile picture upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $filename = $uploader->handleUpload($_FILES['profile_image'], 'users/');
        $user->updateProfileImage($userId, $filename); // Call updateProfileImage
    } else if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other file upload errors
        throw new Exception("File upload error: " . $_FILES['profile_image']['error']);
    }

    if ($user->updateProfile($userId, $data)) {
        $_SESSION['profile_success'] = "Profile updated successfully";
    } else {
        throw new Exception("Failed to update profile data.");
    }

    header("Location: " . BASE_URL . "public/profile.php");
    exit();

} catch (Exception $e) {
    $_SESSION['profile_error'] = $e->getMessage();
    header("Location: " . BASE_URL . "public/edit_profile.php");
    exit();
}
