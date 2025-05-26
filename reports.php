
<?php
session_start();
require_once '../includes/config.php';
require_once '../classes/ReportGenerator.class.php';
require_once '../classes/User.class.php'; // Required for isAdmin check

$reportGenerator = new ReportGenerator($pdo); // Pass PDO

// Ensure user is an admin or vendor for specific reports
$user = new User($pdo);
$is_admin = $user->isAdmin($_SESSION['user_id'] ?? null);
$is_vendor = $user->isVendor($_SESSION['user_id'] ?? null); // Assuming isVendor method exists

// Set default dates if not provided
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

$financialData = [];
$vendorReport = [];
$userActivityReport = [];

// Generate financial report (typically for admin)
if ($is_admin) { // Only allow admin to generate financial reports
    $financialData = $reportGenerator->generateFinancialReport($startDate, $endDate);
}

// Generate vendor performance report
if ($is_vendor && isset($_SESSION['vendor_id'])) { // Only allow vendor to generate their own reports
    $vendorReport = $reportGenerator->generateVendorPerformanceReport(
        $_SESSION['vendor_id'],
        $startDate,
        $endDate
    );
} else if ($is_admin && isset($_GET['vendor_id'])) { // Admin can view specific vendor reports
     $vendorReport = $reportGenerator->generateVendorPerformanceReport(
        (int)$_GET['vendor_id'],
        $startDate,
        $endDate
    );
}

// Generate user activity report (for user or admin)
if (isset($_SESSION['user_id'])) {
    $userActivityReport = $reportGenerator->generateUserActivityReport(
        $_SESSION['user_id'],
        $startDate,
        $endDate
    );
}

// Export functionality
if (isset($_GET['export']) && !empty($financialData)) { // Only export if data is present
    $reportGenerator->exportToCSV($financialData, 'financial-report.csv');
    exit(); // Terminate script after export
}
// ... (rest of the HTML/display logic for reports) ...

// public/reports.php
$reportGenerator = new ReportGenerator($pdo);

// Generate financial report
$financialData = $reportGenerator->generateFinancialReport(
    $_GET['start_date'] ?? date('Y-m-01'),
    $_GET['end_date'] ?? date('Y-m-t')
);

// Generate vendor performance report
$vendorReport = $reportGenerator->generateVendorPerformanceReport(
    $_SESSION['vendor_id'],
    $_GET['start_date'],
    $_GET['end_date']
);

// Export functionality
if (isset($_GET['export'])) {
    $reportGenerator->exportToCSV($financialData, 'financial-report.csv');
}
?>
