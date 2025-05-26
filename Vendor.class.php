<?php
// classes/Vendor.class.php
class Vendor {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    // Register a new vendor
    public function registerVendor($user_id, $data) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO vendor_profiles 
                (user_id, business_name, business_license, tax_id, website, 
                 business_address, business_city, business_state, business_country, 
                 business_postal_code, service_radius, min_budget, max_budget, 
                 experience_years) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $user_id,
                $data['business_name'],
                $data['business_license'] ?? null,
                $data['tax_id'] ?? null,
                $data['website'] ?? null,
                $data['business_address'],
                $data['business_city'],
                $data['business_state'],
                $data['business_country'],
                $data['business_postal_code'],
                $data['service_radius'] ?? 50,
                $data['min_budget'] ?? null,
                $data['max_budget'] ?? null,
                $data['experience_years'] ?? null
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Vendor registration error: " . $e->getMessage());
            return false;
        }
    }

    // Get vendor by user ID
    public function getVendorByUserId($user_id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM vendor_profiles WHERE user_id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get vendor error: " . $e->getMessage());
            return false;
        }
    }

    // Update vendor profile
    public function updateVendor($vendor_id, $data) {
        try {
            $query = "UPDATE vendor_profiles SET 
                business_name = ?,
                business_license = ?,
                tax_id = ?,
                website = ?,
                business_address = ?,
                business_city = ?,
                business_state = ?,
                business_country = ?,
                business_postal_code = ?,
                service_radius = ?,
                min_budget = ?,
                max_budget = ?,
                experience_years = ?
                WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                $data['business_name'],
                $data['business_license'] ?? null,
                $data['tax_id'] ?? null,
                $data['website'] ?? null,
                $data['business_address'],
                $data['business_city'],
                $data['business_state'],
                $data['business_country'],
                $data['business_postal_code'],
                $data['service_radius'] ?? 50,
                $data['min_budget'] ?? null,
                $data['max_budget'] ?? null,
                $data['experience_years'] ?? null,
                $vendor_id
            ]);
        } catch (PDOException $e) {
            error_log("Update vendor error: " . $e->getMessage());
            return false;
        }
    }

    // Add vendor service offering
    public function addServiceOffering($vendor_id, $service_id, $data) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO vendor_service_offerings 
                (vendor_id, service_id, price_range_min, price_range_max, description)
                VALUES (?, ?, ?, ?, ?)");
            
            return $stmt->execute([
                $vendor_id,
                $service_id,
                $data['price_min'] ?? null,
                $data['price_max'] ?? null,
                $data['description'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Add service offering error: " . $e->getMessage());
            return false;
        }
    }

    // Get vendor services
    public function getVendorServices($vendor_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT vso.*, vs.service_name, vc.category_name 
                FROM vendor_service_offerings vso
                JOIN vendor_services vs ON vso.service_id = vs.id
                JOIN vendor_categories vc ON vs.category_id = vc.id
                WHERE vso.vendor_id = ? AND vso.is_active = TRUE
            ");
            $stmt->execute([$vendor_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get vendor services error: " . $e->getMessage());
            return false;
        }
    }

    // Add portfolio item
    public function addPortfolioItem($vendor_id, $data) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO vendor_portfolios 
                (vendor_id, title, description, event_type_id, image_url, 
                 video_url, project_date, client_testimonial, is_featured)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            return $stmt->execute([
                $vendor_id,
                $data['title'],
                $data['description'] ?? null,
                $data['event_type_id'] ?? null,
                $data['image_url'] ?? null,
                $data['video_url'] ?? null,
                $data['project_date'] ?? null,
                $data['client_testimonial'] ?? null,
                $data['is_featured'] ?? false
            ]);
        } catch (PDOException $e) {
            error_log("Add portfolio item error: " . $e->getMessage());
            return false;
        }
    }

    // Get vendor portfolio
    public function getVendorPortfolio($vendor_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT vp.*, et.type_name as event_type_name
                FROM vendor_portfolios vp
                LEFT JOIN event_types et ON vp.event_type_id = et.id
                WHERE vp.vendor_id = ?
                ORDER BY vp.display_order, vp.created_at DESC
            ");
            $stmt->execute([$vendor_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get portfolio error: " . $e->getMessage());
            return false;
        }
    }

    // Set vendor availability
    public function setAvailability($vendor_id, $date, $start_time, $end_time, $status = 'available') {
        try {
            $stmt = $this->conn->prepare("INSERT INTO vendor_availability 
                (vendor_id, date, start_time, end_time, status)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                start_time = VALUES(start_time),
                end_time = VALUES(end_time),
                status = VALUES(status)");
            
            return $stmt->execute([
                $vendor_id,
                $date,
                $start_time,
                $end_time,
                $status
            ]);
        } catch (PDOException $e) {
            error_log("Set availability error: " . $e->getMessage());
            return false;
        }
    }

    // Get vendor availability
    public function getAvailability($vendor_id, $start_date, $end_date) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM vendor_availability 
                WHERE vendor_id = ? AND date BETWEEN ? AND ?
                ORDER BY date, start_time
            ");
            $stmt->execute([$vendor_id, $start_date, $end_date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get availability error: " . $e->getMessage());
            return false;
        }
    }

    // Get recommended vendors for an event
    public function getRecommendedVendors($event_id, $service_id, $limit = 5) {
        try {
            $stmt = $this->conn->prepare("
                SELECT vp.*, u.first_name, u.last_name, u.profile_image,
                       ar.confidence_score, ar.total_score
                FROM ai_recommendations ar
                JOIN vendor_profiles vp ON ar.vendor_id = vp.id
                JOIN users u ON vp.user_id = u.id
                WHERE ar.event_id = ? AND ar.service_id = ?
                ORDER BY ar.total_score DESC
                LIMIT ?
            ");
            $stmt->execute([$event_id, $service_id, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get recommended vendors error: " . $e->getMessage());
            return false;
        }
    }
 public function updateAvailability($vendorId, $start, $end, $status) {
        $stmt = $this->pdo->prepare("
            INSERT INTO vendor_availability 
            (vendor_id, start_time, end_time, status)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                updated_at = NOW()
        ");
        
        return $stmt->execute([
            $vendorId,
            $start,
            $end,
            $status
        ]);
    }

    public function getAvailability($vendorId, $startDate, $endDate) {
        $stmt = $this->pdo->prepare("
            SELECT 
                id,
                start_time as start,
                end_time as end,
                status
            FROM vendor_availability
            WHERE 
                vendor_id = ? AND
                start_time >= ? AND
                end_time <= ?
            ORDER BY start_time
        ");
        
        $stmt->execute([$vendorId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function verifyVendorAccess() {
        session_start();
        if (!isset($_SESSION['user_id']) || !$this->isVendor($_SESSION['user_id'])) {
            header('Location: /login.php');
            exit();
        }
    }
}
?>