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