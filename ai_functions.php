<?php
require_once __DIR__ . '/config.php';

class AI_Assistant {
    private $apiKey;
    private $pdo;
    private $lastPrompt;

    public function __construct($pdo) {
        $this->apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
        $this->pdo = $pdo;
    }

    public function generateEventRecommendation($prompt) {
        try {
            $this->lastPrompt = $prompt;
            
            if (empty($this->apiKey)) {
                throw new Exception('OpenAI API key not configured');
            }

            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are an event planning assistant. Help users create event details based on their description. ' .
                                'Respond with JSON format containing: event_title, event_type, description, event_date (YYYY-MM-DD), ' .
                                'budget_range (min and max), required_services (array of service names with priorities), ' .
                                'and reasoning (brief explanation of choices).'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ];

            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey
                ],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => 'gpt-3.5-turbo',
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'response_format' => ['type' => 'json_object']
                ])
            ]);

            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) throw new Exception('CURL error: ' . $error);

            $data = json_decode($response, true);
            
            if (isset($data['error'])) {
                throw new Exception('OpenAI API error: ' . $data['error']['message']);
            }

            $content = json_decode($data['choices'][0]['message']['content'], true);
            return $this->formatEventData($content);

        } catch (Exception $e) {
            error_log("AI Assistant Error: " . $e->getMessage());
            throw $e;
        }
    }

    private function formatEventData($aiData) {
        $eventTypes = $this->dbFetchAll("SELECT id, type_name FROM event_types");
        $services = $this->dbFetchAll("SELECT id, service_name FROM vendor_services");
        
        $typeMap = array_column($eventTypes, 'id', 'type_name');
        $serviceMap = array_column($services, 'id', 'service_name');
        
        $formatted = [
            'event' => [
                'event_type_id' => $typeMap[$aiData['event_type']] ?? null,
                'title' => $aiData['event_title'] ?? 'Untitled Event',
                'description' => $aiData['description'] ?? '',
                'event_date' => $aiData['event_date'] ?? date('Y-m-d', strtotime('+1 month')),
                'budget_min' => $aiData['budget_range']['min'] ?? 0,
                'budget_max' => $aiData['budget_range']['max'] ?? 0,
                'ai_preferences' => json_encode([
                    'generated_prompt' => $this->lastPrompt,
                    'decision_factors' => $aiData['reasoning'] ?? null
                ])
            ],
            'services' => []
        ];

        foreach ($aiData['required_services'] ?? [] as $service) {
            if (isset($serviceMap[$service['name']])) {
                $formatted['services'][] = [
                    'service_id' => $serviceMap[$service['name']],
                    'priority' => $service['priority'] ?? 'medium',
                    'budget' => $service['budget_allocation'] ?? null
                ];
            }
        }
        
        return $formatted;
    }

    public function getVendorRecommendations($eventId) {
        try {
            $event = $this->dbFetch(
                "SELECT *, ST_X(venue_location) AS lng, ST_Y(venue_location) AS lat 
                 FROM events WHERE id = ?", 
                [$eventId]
            );
            
            $services = $this->dbFetchAll(
                "SELECT service_id FROM event_service_requirements WHERE event_id = ?", 
                [$eventId]
            );
            
            $serviceIds = array_column($services, 'service_id');
            if (empty($serviceIds)) return [];

            $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
            
            $query = "SELECT v.*, 
                     COUNT(vso.service_id) AS matched_services,
                     AVG(vso.price_range_min) AS avg_min_price,
                     AVG(vso.price_range_max) AS avg_max_price,
                     ST_X(business_location) AS business_lng,
                     ST_Y(business_location) AS business_lat,
                     (SELECT COUNT(*) FROM vendor_availability 
                      WHERE vendor_id = v.id 
                      AND date = ? 
                      AND status = 'available') AS availability_score
                     FROM vendor_profiles v
                     JOIN vendor_service_offerings vso ON v.id = vso.vendor_id
                     WHERE vso.service_id IN ($placeholders)
                     GROUP BY v.id
                     ORDER BY matched_services DESC, 
                              availability_score DESC,
                              (avg_min_price + avg_max_price) / 2 ASC
                     LIMIT 10";
            
            $params = array_merge([$event['event_date']], $serviceIds);
            $vendors = $this->dbFetchAll($query, $params);

            return array_map(function($vendor) use ($event) {
                $vendor['score'] = $this->calculateVendorScore($vendor, $event);
                return $vendor;
            }, $vendors);

        } catch (Exception $e) {
            error_log("Vendor Recommendation Error: " . $e->getMessage());
            return [];
        }
    }

    private function calculateVendorScore($vendor, $event) {
        $scores = [
            'location' => $this->calculateLocationScore($vendor, $event),
            'availability' => $vendor['availability_score'] * 0.3,
            'price' => $this->calculatePriceScore($vendor, $event),
            'reviews' => ($vendor['rating'] ?? 0) * 0.2
        ];
        
        return array_sum($scores);
    }

    private function calculateLocationScore($vendor, $event) {
        if (!isset($vendor['business_lat']) || !isset($event['lat'])) {
            return 0.5; // Default score if location data missing
        }
        
        $distance = $this->calculateDistance(
            $vendor['business_lat'], $vendor['business_lng'],
            $event['lat'], $event['lng']
        );
        
        return ($vendor['service_radius'] >= $distance) ? 1 : 0;
    }

    private function calculatePriceScore($vendor, $event) {
        $avgPrice = ($vendor['avg_min_price'] + $vendor['avg_max_price']) / 2;
        $eventBudget = ($event['budget_min'] + $event['budget_max']) / 2;
        
        if ($avgPrice <= $event['budget_min']) return 1;
        if ($avgPrice <= $event['budget_max']) return 0.8;
        return 0.2;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        // Haversine formula implementation
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }

    private function dbFetch($query, $params = []) {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function dbFetchAll($query, $params = []) {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}