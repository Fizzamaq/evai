<?php
// public/process_payment.php
session_start();
require_once '../includes/config.php';
require_once '../classes/PaymentProcessor.class.php';
require_once '../classes/Booking.class.php'; // Include Booking class

$processor = new PaymentProcessor($pdo); // Pass PDO
$bookingSystem = new Booking($pdo); // Pass PDO

try {
    $bookingId = $_POST['booking_id'];
    $booking = $bookingSystem->getBooking($bookingId); // Get booking by ID

    if (!$booking) {
        throw new Exception("Booking not found.");
    }
    if ($booking['user_id'] != $_SESSION['user_id']) { // Basic security check
         throw new Exception("Access denied to this booking.");
    }

    // Create payment intent
    $paymentIntent = $processor->createPaymentIntent(
        $booking['final_amount'],
        ['booking_id' => $bookingId, 'user_id' => $_SESSION['user_id']] // Pass user_id to metadata
    );

    if ($paymentIntent) {
        echo json_encode([
            'clientSecret' => $paymentIntent->client_secret,
            'booking' => $booking
        ]);
    } else {
        throw new Exception("Failed to create payment intent.");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
