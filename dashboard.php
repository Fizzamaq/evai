<?php
session_start(); //
require_once '../includes/config.php'; //
require_once '../classes/User.class.php'; //
require_once '../classes/Event.class.php'; // Include Event class for customer events
require_once '../classes/Booking.class.php'; // Include Booking class for customer bookings

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'public/login.php');
    exit();
}

$user = new User($pdo); //
$event = new Event($pdo); // Instantiate Event class
$booking = new Booking($pdo); // Instantiate Booking class

$user_data = $user->getUserById($_SESSION['user_id']); //

// Redirect based on user type if they land here incorrectly (e.g., direct access)
if (isset($_SESSION['user_type'])) {
    switch ($_SESSION['user_type']) {
        case 2: // Vendor
            header('Location: ' . BASE_URL . 'public/vendor_dashboard.php');
            exit();
        case 3: // Admin
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
            exit();
        // user_type 1 (Customer) will continue here
    }
}

// Get customer-specific dashboard data
$event_stats = $event->getUserEventStats($_SESSION['user_id']);
$upcoming_events = $event->getUpcomingEvents($_SESSION['user_id'], 5); // Limit to 5 upcoming
$recent_bookings = $booking->getUserBookings($_SESSION['user_id'], 5); // Assuming a method to get recent bookings

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
    <style>
        /* Add specific styles for customer dashboard if needed */
        .customer-dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e1e5e9;
        }
        .customer-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .metric-value {
            font-size: 2em;
            font-weight: 700;
            color: #2d3436;
        }
        .metric-label {
            color: #636e72;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .dashboard-sections {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Two columns for content */
            gap: 30px;
        }
        .section-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .section-card h2 {
            margin-top: 0;
            color: #2d3436;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .list-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .list-item:last-child {
            border-bottom: none;
        }
        .list-item-title {
            font-weight: 600;
            color: #2d3436;
        }
        .list-item-meta {
            font-size: 0.9em;
            color: #636e72;
        }
        .empty-state {
            text-align: center;
            padding: 20px;
            color: #636e72;
        }
        .btn-link {
            text-decoration: none;
            color: #667eea;
            font-weight: 600;
            transition: color 0.2s;
        }
        .btn-link:hover {
            color: #764ba2;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

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

    <?php include 'footer.php'; ?>
</body>
</html>
