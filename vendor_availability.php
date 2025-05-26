<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/classes/Vendor.class.php';

$vendor = new Vendor();
$vendor->verifyVendorAccess(); // Your existing auth check

// Handle calendar updates
if ($_POST) {
    $vendor->updateAvailability($_SESSION['vendor_id'], $_POST['dates']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Availability Manager</title>
    <link rel="stylesheet" href="assets/css/vendor.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div id="calendar"></div>
    
    <script>
    // Calendar initialization logic
    </script>
</body>
</html>