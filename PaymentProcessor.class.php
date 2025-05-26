<?php
require_once __DIR__ . '/vendor/autoload.php'; // For Stripe SDK

class PaymentProcessor {
    private $stripe;
    private $pdo;
    
    public function __construct($pdo) {
        $this->stripe = new \Stripe\StripeClient(STRIPE_SECRET_KEY);
        $this->pdo = $pdo;
    }

    public function createPaymentIntent($amount, $metadata = []) {
        try {
            return $this->stripe->paymentIntents->create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => 'usd',
                'metadata' => $metadata,
                'payment_method_types' => ['card'],
                'capture_method' => 'manual' // For holding payments
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->logError($e);
            return false;
        }
    }

    public function confirmPayment($paymentIntentId) {
        try {
            return $this->stripe->paymentIntents->capture($paymentIntentId);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->logError($e);
            return false;
        }
    }

    public function createCustomer($userData) {
        try {
            return $this->stripe->customers->create([
                'email' => $userData['email'],
                'name' => $userData['name'],
                'metadata' => ['user_id' => $userData['id']]
            );
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->logError($e);
            return false;
        }
    }

    public function handleWebhook() {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, STRIPE_WEBHOOK_SECRET
            );
        } catch(\UnexpectedValueException $e) {
            http_response_code(400);
            exit;
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            exit;
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSuccess($event->data->object);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentFailure($event->data->object);
                break;
        }

        http_response_code(200);
    }

    private function handlePaymentSuccess($paymentIntent) {
        $this->pdo->prepare("
            UPDATE bookings SET 
                payment_status = 'completed',
                stripe_payment_id = ?,
                updated_at = NOW()
            WHERE id = ?
        ")->execute([$paymentIntent->id, $paymentIntent->metadata->booking_id]);
    }

    private function logError($e) {
        error_log("Payment Error: " . $e->getMessage());
        $this->pdo->prepare("
            INSERT INTO payment_errors 
            (error_code, message, metadata)
            VALUES (?, ?, ?)
        ")->execute([$e->getError()->code, $e->getMessage(), json_encode($e->getError())]);
    }
}