<?php
//
require_once '../includes/config.php'; 
//
require_once '../classes/User.class.php'; 

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
    $user = new User($pdo); //
    
    if ($user->login($email, $password)) {
        //
        // Redirect based on user type
        $redirect_url = BASE_URL . 'public/dashboard.php'; // Default to customer dashboard
        
        if (isset($_SESSION['user_type'])) {
            switch ($_SESSION['user_type']) {
                case 1: // Customer
                    $redirect_url = BASE_URL . 'public/dashboard.php';
                    break;
                case 2: // Vendor
                    $redirect_url = BASE_URL . 'public/vendor_dashboard.php'; // Assuming you'll rename the current dashboard.php
                    break;
                case 3: // Admin
                    $redirect_url = BASE_URL . 'admin/dashboard.php';
                    break;
                default:
                    // Fallback for unknown user types
                    $redirect_url = BASE_URL . 'public/dashboard.php';
                    break;
            }
        }
        
        // Clear any specific login redirect URL
        if (isset($_SESSION['login_redirect'])) {
            unset($_SESSION['login_redirect']);
        }
        
        header("Location: " . $redirect_url);
        exit();
    } else {
        header("Location: login.php");
        exit();
    }
} catch (Exception $e) {
    // If an exception reaches here, it means the User class didn't handle it.
    // It's good to keep a generic fallback here, but ensure User.class.php
    // sets more specific errors when possible.
    $_SESSION['login_error'] = "An unexpected error occurred during login. " . $e->getMessage();
    error_log("Login Exception in process_login.php: " . $e->getMessage());
    header("Location: login.php");
    exit();
}
