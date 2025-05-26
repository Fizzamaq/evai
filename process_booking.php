<?php
session_start();
require_once '../includes/config.php';
require_once '../classes/Vendor.class.php';
require_once '../classes/Booking.class.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$booking = new Booking();
$vendor = new Vendor();

try {
    $booking_data = [
        'event_id' => $_POST['event_id'],
        'vendor_id' => $_POST['vendor_id'],
        'service_id' => $_POST['service_id'],
        'service_date' => $_POST['service_date'],
        'final_amount' => $_POST['final_amount'],
        'deposit_amount' => $_POST['deposit_amount'],
        'special_instructions' => $_POST['instructions']
    ];
    
    if ($booking->createBooking($booking_data, $_SESSION['user_id'])) {
        // Handle payment
        if ($this->processPayment($_POST['payment_token'])) {
            $_SESSION['success'] = "Booking confirmed!";
            header('Location: /booking_confirmed.php');
        } else {
            throw new Exception("Payment processing failed");
        }
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

function processPayment($token) {
    // Integration with payment gateway
    return true; // Simulated success
}