<?php
class ReportGenerator {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function generateFinancialReport($startDate, $endDate) {
        $stmt = $this->pdo->prepare("
            SELECT 
                DATE(created_at) AS date,
                COUNT(*) AS total_bookings,
                SUM(final_amount) AS total_revenue,
                AVG(final_amount) AS average_booking_value,
                SUM(CASE WHEN payment_status = 'completed' THEN final_amount ELSE 0 END) AS collected_amount,
                SUM(CASE WHEN payment_status = 'pending' THEN final_amount ELSE 0 END) AS pending_amount
            FROM bookings
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generateVendorPerformanceReport($vendorId) {
        return $this->pdo->prepare("
            SELECT 
                MONTH(service_date) AS month,
                COUNT(*) AS total_bookings,
                AVG(rating) AS average_rating,
                SUM(final_amount) AS total_earnings,
                AVG(DATEDIFF(service_date, created_at)) AS avg_lead_time,
                (SELECT COUNT(*) FROM reviews 
                 WHERE reviewed_id = ? AND created_at BETWEEN ? AND ?) AS total_reviews
            FROM bookings
            WHERE vendor_id = ? 
                AND service_date BETWEEN ? AND ?
            GROUP BY MONTH(service_date)
        ")->execute([$vendorId, $startDate, $endDate]);
    }

    public function generateUserActivityReport($userId) {
        return $this->pdo->prepare("
            SELECT 
                event_type,
                COUNT(*) AS total_events,
                AVG(budget_max - budget_min) AS avg_budget_range,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_events,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_events,
                (SELECT COUNT(*) FROM chat_messages 
                 WHERE sender_id = ?) AS total_messages
            FROM events
            WHERE user_id = ?
            GROUP BY event_type
        ")->execute([$userId]);
    }

    public function exportToCSV($data, $filename) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys($data[0]));

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}