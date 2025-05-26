<?php
session_start();
require_once '../../includes/config.php';
require_once '../../classes/Vendor.class.php';

$vendor = new Vendor();
$user = new User();

// Verify vendor access
$vendor_data = $vendor->getVendorByUserId($_SESSION['user_id']);
if (!$vendor_data) {
    header('Location: /login.php');
    exit();
}

// Get vendor statistics
$stats = [
    'total_bookings' => $vendor->getBookingCount($vendor_data['id']),
    'upcoming_events' => $vendor->getUpcomingEvents($vendor_data['id']),
    'average_rating' => $vendor_data['rating'],
    'response_rate' => $vendor->getResponseRate($vendor_data['id'])
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard - EventCraftAI</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .vendor-dashboard {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .vendor-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .metric-value {
            font-size: 2em;
            font-weight: 700;
            color: #2d3436;
        }
        
        .metric-label {
            color: #636e72;
            font-size: 0.9em;
        }
        
        .dashboard-sections {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .upcoming-bookings {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .booking-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .calendar-widget {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <?php include '../includes/vendor_header.php'; ?>
    
    <div class="vendor-dashboard">
        <div class="vendor-header">
            <h1>Welcome, <?= htmlspecialchars($vendor_data['business_name']) ?></h1>
            <div class="rating">
                ★ <?= number_format($stats['average_rating'], 1) ?> (<?= $vendor_data['total_reviews'] ?> reviews)
            </div>
        </div>
        
        <div class="vendor-stats">
            <div class="stat-card">
                <div class="metric-value"><?= $stats['total_bookings'] ?></div>
                <div class="metric-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="metric-value"><?= $stats['upcoming_events'] ?></div>
                <div class="metric-label">Upcoming Events</div>
            </div>
            <div class="stat-card">
                <div class="metric-value"><?= ($stats['response_rate'] * 100) ?>%</div>
                <div class="metric-label">Response Rate</div>
            </div>
        </div>
        
        <div class="dashboard-sections">
            <div class="upcoming-bookings">
                <h2>Upcoming Bookings</h2>
                <?php foreach ($vendor->getUpcomingBookings($vendor_data['id']) as $booking): ?>
                    <div class="booking-item">
                        <div>
                            <h3><?= htmlspecialchars($booking['event_title']) ?></h3>
                            <div class="booking-date">
                                <?= date('M j, Y', strtotime($booking['service_date'])) ?>
                            </div>
                        </div>
                        <a href="booking.php?id=<?= $booking['id'] ?>" class="btn">View Details</a>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="calendar-widget">
                <h2>Availability Calendar</h2>
                <div id="availability-calendar"></div>
            </div>
        </div>
    </div>

    <script>
        // Initialize calendar
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('availability-calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: '/api/vendor/availability?vendor_id=<?= $vendor_data['id'] ?>',
                eventClick: function(info) {
                    // Handle date click for availability management
                }
            });
            calendar.render();
        });
    </script>
</body>
</html>