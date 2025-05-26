<?php
// classes/SystemSettings.class.php
class SystemSettings {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getSetting($key) {
        try {
            $stmt = $this->pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['setting_value'] ?? null;
        } catch (PDOException $e) {
            error_log("Get setting error: " . $e->getMessage());
            return null;
        }
    }

    public function updateSetting($key, $value) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            return $stmt->execute([$key, $value]);
        } catch (PDOException $e) {
            error_log("Update setting error: " . $e->getMessage());
            return false;
        }
    }

    public function getAllSettings() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM system_settings ORDER BY setting_key ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get all settings error: " . $e->getMessage());
            return [];
        }
    }

    // Example: Add a setting for AI API key (if not already in config.php)
    public function ensureDefaultSettings() {
        $settings = [
            'site_name' => ['EventCraftAI', 'Website Name', 'string'],
            'contact_email' => ['info@eventcraftai.com', 'Contact Email Address', 'string'],
            'maintenance_mode' => ['0', 'Enable maintenance mode', 'boolean'],
            'reviews_enabled' => ['1', 'Allow users to leave reviews', 'boolean'],
            'stripe_publishable_key' => ['', 'Stripe Publishable Key (for frontend)', 'string'],
            // Add more settings as needed
        ];

        foreach ($settings as $key => $details) {
            $this->updateSetting($key, $details[0], $details[1], $details[2]); // Assuming updateSetting can take description and type
        }
    }

    // Modified updateSetting to include description and data_type for initial population
    public function updateSetting($key, $value, $description = null, $dataType = null) {
        try {
            $sql = "INSERT INTO system_settings (setting_key, setting_value, description, data_type) VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
            $params = [$key, $value, $description, $dataType];
            if ($description && $dataType) {
                $sql = "INSERT INTO system_settings (setting_key, setting_value, description, data_type) VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), description = VALUES(description), data_type = VALUES(data_type)";
                $params = [$key, $value, $description, $dataType];
            }

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Update setting error: " . $e->getMessage());
            return false;
        }
    }
}
?>