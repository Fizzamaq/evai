<?php
// Start session and include necessary files for database connection and classes
session_start(); 
// [cite: fizzamaq/evai/evai-270c475187253adadaf42cfe122a431191cf1f80/config.php]
require_once '../includes/config.php'; 
// [cite: fizzamaq/evai/evai-270c475187253adadaf42cfe122a431191cf1f80/User.class.php]
require_once '../classes/User.class.php'; 
// [cite: fizzamaq/evai/evai-270c475187253adadaf42cfe122a431191cf1f80/Event.class.php]
require_once '../classes/Event.class.php'; 
// [cite: fizzamaq/evai/evai-270c475187253adadaf42cfe122a431191cf1f80/Booking.class.php]
require_once '../classes/Booking.class.php'; 

// Redirect to login page if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'public/login.php');
    exit();
}

// Instantiate necessary classes with the PDO connection
$user = new User($pdo); 
$event = new Event($pdo); 
$booking = new Booking($pdo); 

// Fetch current user's data
$user_data = $user->getUserById($_SESSION['user_id']); 

// Ensure that only customer type users remain on this dashboard.
// Redirect vendors and admins to their respective dashboards if they somehow land here directly.
if (isset($_SESSION['user_type'])) {
    switch ($_SESSION['user_type']) {
        case 2: // Vendor
            header('Location: ' . BASE_URL . 'public/vendor_dashboard.php');
            exit();
        case 3: // Admin
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
            exit();
        // User type 1 (Customer) will fall through and continue
    }
}

// Fetch customer-specific dashboard data
// Get overall event statistics for the user
$event_stats = $event->getUserEventStats($_SESSION['user_id']);
// Get a limited number of upcoming events for display
$upcoming_events = $event->getUpcomingEvents($_SESSION['user_id'], 5); 
// Get a limited number of recent bookings for display (assuming getUserBookings exists and can be limited)
// Note: The provided Booking.class.php does not have a limit parameter for getUserBookings.
// For this to work, you might need to add a $limit parameter to the getUserBookings method in Booking.class.php.
$recent_bookings = $booking->getUserBookings($_SESSION['user_id']); 
// Manually limit if the method doesn't support it directly
$recent_bookings = array_slice($recent_bookings, 0, 5);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EventCraftAI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; // Include the main site header ?>

    <div class="customer-dashboard-container">
        <div class="dashboard-header">
            <div>
                <h1>Welcome, <?= htmlspecialchars($user_data['first_name']) ?>!</h1>
                <p>Your event planning journey starts here.</p>
            </div>
            <div>
                <a href="create_event.php" class="btn btn-primary">Create New Event</a>
                <a href="ai_chat.php" class="btn btn-secondary">AI Assistant</a>
            </div>
        </div>

        <div class="customer-stats-grid">
            <div class="stat-card">
                <div class="metric-value"><?= $event_stats['total_events'] ?? 0 ?></div>
                <div class="metric-label">Total Events</div>
            </div>
            <div class="stat-card">
                <div class="metric-value"><?= $event_stats['upcoming_events'] ?? 0 ?></div>
                <div class="metric-label">Upcoming Events</div>
            </div>
            <div class="stat-card">
                <div class="metric-value"><?= $event_stats['planning_events'] ?? 0 ?></div>
                <div class="metric-label">Events in Planning</div>
            </div>
            <div class="stat-card">
                <div class="metric-value">$<?= number_format($event_stats['avg_budget'] ?? 0, 2) ?></div>
                <div class="metric-label">Avg. Event Budget</div>
            </div>
        </div>

        <div class="dashboard-sections">
            <div class="section-card">
                <h2>Upcoming Events</h2>
                <?php if (!empty($upcoming_events)): ?>
                    <?php foreach ($upcoming_events as $event_item): ?>
                        <div class="list-item">
                            <div>
                                <div class="list-item-title"><?= htmlspecialchars($event_item['title']) ?></div>
                                <div class="list-item-meta"><?= date('M j, Y', strtotime($event_item['event_date'])) ?> | <?= htmlspecialchars($event_item['type_name']) ?></div>
                            </div>
                            <a href="<?= BASE_URL ?>public/event.php?id=<?= $event_item['id'] ?>" class="btn-link">View</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">No upcoming events. <a href="create_event.php" class="btn-link">Create one now!</a></div>
                <?php endif; ?>
            </div>

            <div class="section-card">
                <h2>Recent Bookings</h2>
                <?php if (!empty($recent_bookings)): ?>
                    <?php foreach ($recent_bookings as $booking_item): ?>
                        <div class="list-item">
                            <div>
                                <div class="list-item-title">Booking for <?= htmlspecialchars($booking_item['event_title']) ?></div>
                                <div class="list-item-meta"><?= htmlspecialchars($booking_item['business_name']) ?> | Status: <?= ucfirst(htmlspecialchars($booking_item['status'])) ?></div>
                            </div>
                            <a href="<?= BASE_URL ?>public/booking.php?id=<?= $booking_item['id'] ?>" class="btn-link">View</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">No recent bookings. <a href="events.php" class="btn-link">Find vendors for your events!</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; // Include the main site footer ?>
</body>
</html>
