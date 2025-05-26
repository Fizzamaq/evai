<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Vendor.class.php';

$vendor = new Vendor();
$vendor->verifyVendorAccess();

// Handle availability updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $vendor->updateAvailability(
            $_SESSION['vendor_id'],
            $data['start'],
            $data['end'],
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
$availability = $vendor->getAvailability(
    $_SESSION['vendor_id'],
    $_GET['start'] ?? date('Y-m-01'),
    $_GET['end'] ?? date('Y-m-t')
);

header('Content-Type: application/json');
echo json_encode($availability);