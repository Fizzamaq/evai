<?php
class Event {
    private $conn;

    public function __construct($pdo) { // Changed to accept PDO
        $this->conn = $pdo;
    }
    /**
     * Create a new event
     */
    public function createEvent($data) {
        try {
            $sql = "INSERT INTO events (
                user_id, title, description, event_type, event_date, event_time,
                duration, location, guest_count, budget, status, services_needed,
                special_requirements, created_at, updated_at
            ) VALUES (
                :user_id, :title, :description, :event_type, :event_date, :event_time,
                :duration, :location, :guest_count, :budget, :status, :services_needed,
                :special_requirements, :created_at, :updated_at
            )";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':event_type', $data['event_type']);
            $stmt->bindParam(':event_date', $data['event_date']);
            $stmt->bindParam(':event_time', $data['event_time']);
            $stmt->bindParam(':duration', $data['duration']);
            $stmt->bindParam(':location', $data['location']);
            $stmt->bindParam(':guest_count', $data['guest_count']);
            $stmt->bindParam(':budget', $data['budget']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':services_needed', $data['services_needed']);
            $stmt->bindParam(':special_requirements', $data['special_requirements']);
            $stmt->bindParam(':created_at', $data['created_at']);
            $stmt->bindParam(':updated_at', $data['updated_at']);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
            
        } catch (PDOException $e) {
            error_log("Event creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all events for a specific user
     */
    public function getUserEvents($user_id) {
        try {
            $sql = "SELECT * FROM events WHERE user_id = :user_id ORDER BY event_date ASC, created_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get user events error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a specific event by ID
     */
    public function getEventById($event_id, $user_id = null) {
        try {
            $sql = "SELECT * FROM events WHERE id = :event_id";
            if ($user_id) {
                $sql .= " AND user_id = :user_id";
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':event_id', $event_id);
            if ($user_id) {
                $stmt->bindParam(':user_id', $user_id);
            }
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get event by ID error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update an existing event
     */
    public function updateEvent($event_id, $data, $user_id) {
        try {
            $sql = "UPDATE events SET 
                title = :title,
                description = :description,
                event_type = :event_type,
                event_date = :event_date,
                event_time = :event_time,
                duration = :duration,
                location = :location,
                guest_count = :guest_count,
                budget = :budget,
                status = :status,
                services_needed = :services_needed,
                special_requirements = :special_requirements,
                updated_at = :updated_at
                WHERE id = :event_id AND user_id = :user_id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':event_id', $event_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':event_type', $data['event_type']);
            $stmt->bindParam(':event_date', $data['event_date']);
            $stmt->bindParam(':event_time', $data['event_time']);
            $stmt->bindParam(':duration', $data['duration']);
            $stmt->bindParam(':location', $data['location']);
            $stmt->bindParam(':guest_count', $data['guest_count']);
            $stmt->bindParam(':budget', $data['budget']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':services_needed', $data['services_needed']);
            $stmt->bindParam(':special_requirements', $data['special_requirements']);
            $stmt->bindParam(':updated_at', $data['updated_at']);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Event update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete an event (soft delete by changing status)
     */
    public function deleteEvent($event_id, $user_id) {
        try {
            // Option 1: Soft delete (recommended)
            $sql = "UPDATE events SET status = 'deleted', updated_at = NOW() 
                    WHERE id = :event_id AND user_id = :user_id";
            
            // Option 2: Hard delete (uncomment if preferred)
            // $sql = "DELETE FROM events WHERE id = :event_id AND user_id = :user_id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':event_id', $event_id);
            $stmt->bindParam(':user_id', $user_id);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Event deletion error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Search events by criteria
     */
    public function searchEvents($user_id, $criteria = []) {
        try {
            $sql = "SELECT * FROM events WHERE user_id = :user_id";
            $params = [':user_id' => $user_id];
            
            // Add search conditions
            if (!empty($criteria['title'])) {
                $sql .= " AND title LIKE :title";
                $params[':title'] = '%' . $criteria['title'] . '%';
            }
            
            if (!empty($criteria['event_type'])) {
                $sql .= " AND event_type = :event_type";
                $params[':event_type'] = $criteria['event_type'];
            }
            
            if (!empty($criteria['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $criteria['status'];
            }
            
            if (!empty($criteria['date_from'])) {
                $sql .= " AND event_date >= :date_from";
                $params[':date_from'] = $criteria['date_from'];
            }
            
            if (!empty($criteria['date_to'])) {
                $sql .= " AND event_date <= :date_to";
                $params[':date_to'] = $criteria['date_to'];
            }
            
            $sql .= " ORDER BY event_date ASC";
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Event search error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get upcoming events for a user
     */
    public function getUpcomingEvents($user_id, $limit = 5) {
        try {
            $sql = "SELECT * FROM events 
                    WHERE user_id = :user_id 
                    AND event_date >= CURDATE() 
                    AND status != 'deleted'
                    ORDER BY event_date ASC 
                    LIMIT :limit";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get upcoming events error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get event statistics for a user
     */
    public function getUserEventStats($user_id) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_events,
                        SUM(CASE WHEN status = 'planning' THEN 1 ELSE 0 END) as planning_events,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_events,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_events,
                        SUM(CASE WHEN event_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_events,
                        AVG(budget) as avg_budget,
                        SUM(budget) as total_budget
                    FROM events 
                    WHERE user_id = :user_id AND status != 'deleted'";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get user event stats error: " . $e->getMessage());
            return [
                'total_events' => 0,
                'planning_events' => 0,
                'active_events' => 0,
                'completed_events' => 0,
                'upcoming_events' => 0,
                'avg_budget' => 0,
                'total_budget' => 0
            ];
        }
    }
    
    /**
     * Get events that need specific services (for vendor matching)
     */
    public function getEventsByService($service_type, $location = null) {
        try {
            $sql = "SELECT * FROM events 
                    WHERE JSON_CONTAINS(services_needed, JSON_QUOTE(:service_type))
                    AND status IN ('planning', 'active')
                    AND event_date >= CURDATE()";
            
            $params = [':service_type' => $service_type];
            
            if ($location) {
                $sql .= " AND location LIKE :location";
                $params[':location'] = '%' . $location . '%';
            }
            
            $sql .= " ORDER BY event_date ASC";
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get events by service error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Duplicate an event (for recurring events)
     */
    public function duplicateEvent($event_id, $user_id, $new_date = null) {
        try {
            // Get original event
            $original_event = $this->getEventById($event_id, $user_id);
            if (!$original_event) {
                return false;
            }
            
            // Prepare new event data
            $new_event_data = $original_event;
            unset($new_event_data['id']);
            $new_event_data['title'] .= ' (Copy)';
            $new_event_data['status'] = 'planning';
            $new_event_data['created_at'] = date('Y-m-d H:i:s');
            $new_event_data['updated_at'] = date('Y-m-d H:i:s');
            
            if ($new_date) {
                $new_event_data['event_date'] = $new_date;
            }
            
            return $this->createEvent($new_event_data);
            
        } catch (Exception $e) {
            error_log("Event duplication error: " . $e->getMessage());
            return false;
        }
    }
}
?>
