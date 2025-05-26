<?php
session_start();
require_once '../includes/config.php';
require_once '../classes/Booking.class.php';
require_once '../classes/Review.class.php';

$booking = new Booking();
$review = new Review();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$booking_id = $_GET['booking_id'] ?? null;
$booking_details = $booking->getUserBooking($_SESSION['user_id'], $booking_id);

// Verify valid booking
if (!$booking_details || $booking_details['status'] !== 'completed') {
    header('Location: /dashboard.php');
    exit();
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_data = [
        'booking_id' => $booking_id,
        'reviewer_id' => $_SESSION['user_id'],
        'reviewed_id' => $booking_details['vendor_id'],
        'rating' => $_POST['rating'],
        'review_title' => $_POST['title'],
        'review_content' => $_POST['content'],
        'service_quality' => $_POST['service_quality'],
        'communication' => $_POST['communication'],
        'value_for_money' => $_POST['value_for_money'],
        'would_recommend' => isset($_POST['recommend']) ? 1 : 0
    ];

    if ($review->submitReview($review_data)) {
        $_SESSION['success'] = "Review submitted successfully!";
        header('Location: /booking.php?id=' . $booking_id);
        exit();
    } else {
        $_SESSION['error'] = "Failed to submit review";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Review - EventCraftAI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .review-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .rating-stars {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .star-input {
            display: none;
        }
        
        .star-label {
            cursor: pointer;
            font-size: 2em;
            color: #ddd;
            transition: color 0.2s;
        }
        
        .star-input:checked ~ .star-label,
        .star-label:hover,
        .star-label:hover ~ .star-label {
            color: #ffd700;
        }
        
        .rating-category {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="review-container">
        <h1>Review <?php echo htmlspecialchars($booking_details['business_name']); ?></h1>
        
        <form method="POST">
            <div class="form-group">
                <label>Overall Rating</label>
                <div class="rating-stars">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" class="star-input" required>
                        <label for="star<?php echo $i; ?>" class="star-label">★</label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="rating-category">
                <h3>Service Quality</h3>
                <div class="rating-stars">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" id="sq<?php echo $i; ?>" name="service_quality" value="<?php echo $i; ?>" required>
                        <label for="sq<?php echo $i; ?>" class="star-label">★</label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="rating-category">
                <h3>Communication</h3>
                <div class="rating-stars">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" id="com<?php echo $i; ?>" name="communication" value="<?php echo $i; ?>" required>
                        <label for="com<?php echo $i; ?>" class="star-label">★</label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="rating-category">
                <h3>Value for Money</h3>
                <div class="rating-stars">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" id="vfm<?php echo $i; ?>" name="value_for_money" value="<?php echo $i; ?>" required>
                        <label for="vfm<?php echo $i; ?>" class="star-label">★</label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label>Review Title</label>
                <input type="text" name="title" required maxlength="200">
            </div>
            
            <div class="form-group">
                <label>Detailed Review</label>
                <textarea name="content" rows="5" required></textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="recommend"> Would you recommend this vendor?
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">Submit Review</button>
        </form>
    </div>

    <script>
        // Star rating interaction
        document.querySelectorAll('.star-label').forEach(label => {
            label.addEventListener('click', (e) => {
                const input = e.target.previousElementSibling;
                const group = input.name;
                document.querySelectorAll(`input[name="${group}"]`).forEach(radio => {
                    radio.nextElementSibling.style.color = radio.checked ? '#ffd700' : '#ddd';
                });
            });
        });
    </script>
</body>
</html>