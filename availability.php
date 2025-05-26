<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Vendor.class.php';

$vendor = new Vendor($pdo); // Pass PDO to constructor
$vendor->verifyVendorAccess(); // Ensure vendor_id is set in session here

// Handle availability updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($_SESSION['vendor_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Vendor not authenticated or ID not found.']);
        exit();
    }

    try {
        // Assuming $data['start'] and $data['end'] are full datetime strings
        $date = date('Y-m-d', strtotime($data['start']));
        $startTime = date('H:i:s', strtotime($data['start']));
        $endTime = date('H:i:s', strtotime($data['end']));

        $vendor->updateAvailability(
            $_SESSION['vendor_id'],
            $date,
            $startTime,
            $endTime,
            $data['status']
        );
        http_response_code(200);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// Load existing availability
// Ensure vendor_id is set in session for this GET request as well
if (!isset($_SESSION['vendor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Vendor not authenticated or ID not found for fetching availability.']);
    exit();
}

$availability = $vendor->getAvailability(
    $_SESSION['vendor_id'],
    $_GET['start'] ?? date('Y-m-01'),
    $_GET['end'] ?? date('Y-m-t')
);

header('Content-Type: application/json');
echo json_encode($availability);
?>
