// public/process_payment.php
session_start();
require_once '../includes/config.php';
require_once '../classes/PaymentProcessor.class.php';
require_once '../classes/Booking.class.php';

$processor = new PaymentProcessor($pdo);
$bookingSystem = new Booking($pdo);

try {
    $bookingId = $_POST['booking_id'];
    $booking = $bookingSystem->getBooking($bookingId);
    
    // Create payment intent
    $paymentIntent = $processor->createPaymentIntent(
        $booking['final_amount'],
        ['booking_id' => $bookingId]
    );
    
    echo json_encode([
        'clientSecret' => $paymentIntent->client_secret,
        'booking' => $booking
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}