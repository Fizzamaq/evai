<?php
session_start();
require_once '../includes/config.php';
require_once '../classes/Vendor.class.php';
require_once '../classes/Booking.class.php';
require_once '../classes/PaymentProcessor.class.php'; // Include PaymentProcessor

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$booking = new Booking($pdo); // Pass PDO
$vendor = new Vendor($pdo); // Pass PDO
$paymentProcessor = new PaymentProcessor($pdo); // Instantiate PaymentProcessor

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

    $newBookingId = $booking->createBooking($booking_data, $_SESSION['user_id']);

    if ($newBookingId) {
        // Now, create a payment intent using the PaymentProcessor
        // The 'payment_token' from the form is likely a client-side token (e.g., Stripe Token)
        // This script should instead initiate a PaymentIntent and confirm it.
        // For this example, we'll simulate success for payment intent creation.
        $paymentIntent = $paymentProcessor->createPaymentIntent(
            $booking_data['final_amount'],
            ['booking_id' => $newBookingId, 'user_id' => $_SESSION['user_id']]
        );

        if ($paymentIntent) {
            // In a real scenario, you would redirect to a payment confirmation page
            // or return client_secret for client-side confirmation.
            // For now, let's assume direct confirmation.
            // $confirmedPayment = $paymentProcessor->confirmPayment($paymentIntent->id); // This would capture funds

            // For now, update booking status directly after a simulated successful payment intent creation
            $booking->updateBookingStatus($newBookingId, 'pending_payment', $paymentIntent->id); // Set status to pending payment
            $_SESSION['success'] = "Booking initiated. Complete payment!";
            // Redirect to a page where payment can be completed (e.g., Stripe Checkout)
            header('Location: booking_confirmation.php?booking_id=' . $newBookingId . '&client_secret=' . $paymentIntent->client_secret);
            exit();
        } else {
            throw new Exception("Payment intent creation failed.");
        }
    } else {
        throw new Exception("Failed to create booking.");
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    // Redirect back to the previous page or a specific error page
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/')); // Fallback to homepage
    exit();
}
